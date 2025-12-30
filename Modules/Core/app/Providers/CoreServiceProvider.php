<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Console\Commands\BackupDatabase;
use Modules\Core\Console\Commands\ExtractLangStrings;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Core\Http\Middleware\SetLocaleMiddleware;
use Modules\Core\Models\Localization\Language;
use Modules\Core\Policies\LanguagePolicy;
use Modules\Core\Policies\PermissionPolicy;
use Modules\Core\Policies\RolePolicy;
use Modules\Core\Services\ConfigSync;
use Modules\Core\Services\HookService;
use Modules\Core\Settings\GeneralSettings;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

final class CoreServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Core';

    protected string $nameLower = 'core';

    /**
     * Module-specific boot logic.
     */
    protected function bootModule(): void
    {
        $this->syncConfigurations();
        $this->handleModuleActivation();
        $this->registerMiddleware();
        $this->registerStorageMacros();
        $this->registerBladeDirectives();
        $this->registerPolicies();

        // register seeders
        if (app()->runningInConsole()) {
            $this->app->afterResolving(DatabaseSeeder::class, function ($seeder) {
                $seeder->call(CoreDatabaseSeeder::class);
            });
        }
    }

    /**
     * Module-specific registration logic.
     */
    protected function registerModule(): void
    {
        $this->app->register(PluginServiceProvider::class);
        $this->registerHookService();
        $this->registerHooks();
        $this->registerConfigSync();
    }

    /**
     * Get module-specific commands.
     *
     * @return array<class-string>
     */
    protected function getModuleCommands(): array
    {
        return [
            BackupDatabase::class,
            ExtractLangStrings::class,
            // \Modules\Core\Console\Commands\DeleteUnusedMediaCommand::class,
        ];
    }

    /**
     * Register middleware for the application.
     */
    private function registerMiddleware(): void
    {
        // don't use prependMiddlewareToGroup because we need to load sessions before this MW
        $this->app['router']->pushMiddlewareToGroup('web', SetLocaleMiddleware::class);
    }

    /**
     * Register storage macros.
     */
    private function registerStorageMacros(): void
    {
        Storage::macro('urlOrPlaceholder', function (?string $path = null, string $placeholder = 'images/placeholder.jpg'): string {
            return $path && Storage::exists($path)
                ? Storage::url($path)
                : asset($placeholder);
        });
    }

    /**
     * Register Blade directives.
     */
    private function registerBladeDirectives(): void
    {
        Blade::directive('price', function (string $expression): string {
            return "<?php echo app(\Modules\Core\Services\CurrencyService::class)->formatPrice($expression); ?>";
        });

        Blade::directive('currency', function (string $expression): string {
            return "<?php echo formatCurrency($expression); ?>";
        });

        Blade::directive('currencySymbol', function (): string {
            return "<?php echo currencySymbol(); ?>";
        });

        Blade::directive('locale', function (): string {
            return "<?php echo currentLocale(); ?>";
        });

        Blade::directive('wirenav', function (?string $expression = null): string {
            return "<?php echo config('core.enable_wire_navigate', true) ? 'wire:navigate' : ''; ?>";
        });

        Blade::directive('wirenavhover', function (?string $expression = null): string {
            return "<?php echo config('core.enable_wire_navigate', true) ? 'wire:navigate.hover' : ''; ?>";
        });
    }

    /**
     * Register authorization policies.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Language::class, LanguagePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
    }

    /**
     * Register the HookService singleton.
     */
    private function registerHookService(): void
    {
        $this->app->singleton('hook', fn (): HookService => new HookService);
    }

    /**
     * Register application hooks.
     */
    private function registerHooks(): void
    {
        // Register core hooks
        app('hook')->register('navItems', fn (array $items): array => $items);
        app('hook')->register('payments-gateways', fn (array $items): array => $items);

        // Examples:
        // app('hook')->register('navItems', fn(array $items): array => $items + ['test' => 'test']);
        // $menus = app('hook')->apply('navItems', ['sada' => 'sada']); // in MenuResources
    }

    /**
     * Handle module activation/deactivation based on settings.
     */
    private function handleModuleActivation(): void
    {
        try {
            $disabledModules = $this->getDisabledModules();

            Module::toCollection()->each(function ($module) use ($disabledModules): void {
                if (in_array($module->getName(), $disabledModules, true)) {
                    $module->disable();
                } else {
                    $module->enable();
                }
            });
        } catch (Throwable $exception) {
            Log::error('Error handling module activation: '.$exception->getMessage(), [
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Get disabled modules from settings.
     *
     * @return array<string>
     */
    private function getDisabledModules(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return app(GeneralSettings::class)->disabled_modules ?? [];
        } catch (Throwable $exception) {
            Log::warning('Failed to retrieve disabled modules from settings: '.$exception->getMessage());

            return [];
        }
    }

    /**
     * Register and configure the ConfigSync service.
     */
    private function registerConfigSync(): void
    {
        $this->app->singleton(ConfigSync::class, fn (): ConfigSync => new ConfigSync);
    }

    /**
     * Sync configurations during boot phase.
     */
    private function syncConfigurations(): void
    {
        if (! $this->app->runningInConsole()) {
            try {
                app(ConfigSync::class)->sync();
            } catch (Throwable $exception) {
                Log::error('Error syncing configurations during boot: '.$exception->getMessage(), [
                    'exception' => $exception,
                ]);
            }
        }
    }
}
