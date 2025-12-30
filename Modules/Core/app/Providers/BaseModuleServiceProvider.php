<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name;

    protected string $nameLower;

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        try {
            $this->registerCommands();
            $this->registerCommandSchedules();
            $this->registerTranslations();
            $this->registerConfig();
            $this->registerViews();
            $this->loadModuleMigrations();

            // Allow modules to add custom boot logic
            $this->bootModule();
        } catch (Throwable $exception) {
            Log::error("Failed to boot module {$this->name}: ".$exception->getMessage(), [
                'exception' => $exception,
                'module' => $this->name,
            ]);

            if (app()->environment('local', 'testing')) {
                throw $exception;
            }
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        try {
            $this->registerEventServiceProvider();
            $this->registerRouteServiceProvider();

            // Allow modules to add custom registration logic
            $this->registerModule();
        } catch (Throwable $exception) {
            Log::error("Failed to register module {$this->name}: ".$exception->getMessage(), [
                'exception' => $exception,
                'module' => $this->name,
            ]);

            if (app()->environment('local', 'testing')) {
                throw $exception;
            }
        }
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $commands = $this->getModuleCommands();
        if (! empty($commands)) {
            if ($this->app->runningInConsole()) {
                $this->commands($commands);
            }
        }
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // Modules can override this method to add custom schedules
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));

        }
    }

    /**
     * Register config files.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path', 'config');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (! is_dir($configPath)) {
            return;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($configPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                $configKey = $this->nameLower.'.'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                $this->mergeConfigFrom($file->getPathname(), $key);
            }
        } catch (Throwable $exception) {
            Log::warning("Failed to register config for module {$this->name}: ".$exception->getMessage());
        }
    }

    /**
     * Register views and Blade components.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path("views/modules/{$this->nameLower}");
        $sourcePath = module_path($this->name, 'resources/views');

        if (! is_dir($sourcePath)) {
            return;
        }

        $this->publishes([$sourcePath => $viewPath], ['views', "{$this->nameLower}-module-views"]);
        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $this->registerBladeComponents();
    }

    /**
     * Register Blade components for the module.
     */
    protected function registerBladeComponents(): void
    {
        try {
            $componentNamespace = $this->module_namespace(
                $this->name,
                $this->app_path(config('modules.paths.generator.component-class.path', 'View/Components'))
            );

            Blade::componentNamespace($componentNamespace, $this->nameLower);
        } catch (Throwable $exception) {
            Log::warning("Failed to register Blade components for module {$this->name}: ".$exception->getMessage());
        }
    }

    /**
     * Load module migrations.
     */
    protected function loadModuleMigrations(): void
    {
        $migrationPath = module_path($this->name, 'database/migrations');

        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * Get publishable view paths.
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths', []) as $path) {
            $modulePath = $path."/modules/{$this->nameLower}";
            if (is_dir($modulePath)) {
                $paths[] = $modulePath;
            }
        }

        return $paths;
    }

    /**
     * Register the EventServiceProvider for the module.
     */
    protected function registerEventServiceProvider(): void
    {
        $eventServiceProvider = $this->getModuleNamespace().'\\Providers\\EventServiceProvider';
        if (class_exists($eventServiceProvider)) {
            $this->app->register($eventServiceProvider);
        }
    }

    /**
     * Register the RouteServiceProvider for the module.
     */
    protected function registerRouteServiceProvider(): void
    {
        $routeServiceProvider = $this->getModuleNamespace().'\\Providers\\RouteServiceProvider';
        if (class_exists($routeServiceProvider)) {
            $this->app->register($routeServiceProvider);
        }
    }

    /**
     * Get the module namespace.
     */
    protected function getModuleNamespace(): string
    {
        return "Modules\\{$this->name}";
    }

    /**
     * Get module-specific commands. Override in child classes.
     *
     * @return array<class-string>
     */
    protected function getModuleCommands(): array
    {
        return [];
    }

    /**
     * Module-specific boot logic. Override in child classes.
     */
    protected function bootModule(): void
    {
        // Override in child classes
    }

    /**
     * Module-specific registration logic. Override in child classes.
     */
    protected function registerModule(): void
    {
        // Override in child classes
    }
}
