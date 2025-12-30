<?php

declare(strict_types=1);

namespace Modules\Cms\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Modules\Cms\Models\Page;
use Modules\Core\Providers\BaseModuleServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

final class CmsServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Cms';

    protected string $nameLower = 'cms';

    protected function bootModule(): void
    {
        $this->registerNavigationLinks();
    }

    /**
     * Register navigation links in the hook system.
     */
    private function registerNavigationLinks(): void
    {
        $this->app->booted(function (): void {
            if (app()->runningInConsole()) {
                return;
            }
            try {
                // after spatie/laravel-translatable is loaded
                $this->cacheAndRegisterLinks();
            } catch (Throwable $exception) {
                Log::warning('Failed to register navigation links for Tour module: '.$exception->getMessage());
            }
        });
    }

    /**
     * Cache and register tour-related navigation links.
     */
    private function cacheAndRegisterLinks(): void
    {
        $cacheTime = 60 * 60 * 24; // 24 hours

        $pages = cache()->remember('pages_links', $cacheTime, function (): array {
            return Page::query()
                ->select(['id', 'title', 'slug'])
                ->get()
                ->mapWithKeys(fn (Page $page): array => [
                    route('page.show', ['page' => $page]) => $page->title,
                ])
                ->toArray();
        });

        $allLinks = array_merge($pages);

        app('hook')->register('navItems', fn (array $items): array => array_merge($items, $allLinks));
    }

    /**
     * Override config registration to use custom merge logic.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path', 'config'));

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

                $this->processConfigFile($file, $configPath);
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to register config for CMS module: '.$exception->getMessage());
        }
    }

    /**
     * Process a single config file.
     */
    private function processConfigFile(SplFileInfo $file, string $configPath): void
    {
        $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
        $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
        $segments = explode('.', $this->nameLower.'.'.$configKey);

        // Remove duplicated adjacent segments
        $normalized = $this->normalizeDuplicateSegments($segments);
        $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

        $this->publishes([$file->getPathname() => config_path($config)], 'config');
        $this->mergeConfigFromFile($file->getPathname(), $key);
    }

    /**
     * Remove duplicated adjacent segments from config key.
     *
     * @param  array<string>  $segments
     * @return array<string>
     */
    private function normalizeDuplicateSegments(array $segments): array
    {
        $normalized = [];
        foreach ($segments as $segment) {
            if (end($normalized) !== $segment) {
                $normalized[] = $segment;
            }
        }

        return $normalized;
    }

    /**
     * Merge config from the given path recursively.
     */
    private function mergeConfigFromFile(string $path, string $key): void
    {
        try {
            $existing = config($key, []);
            $moduleConfig = require $path;

            if (! is_array($moduleConfig)) {
                Log::warning("Config file {$path} does not return an array");

                return;
            }

            config([$key => array_replace_recursive($existing, $moduleConfig)]);
        } catch (Throwable $exception) {
            Log::warning("Failed to merge config from {$path}: ".$exception->getMessage());
        }
    }

    /**
     * Override view registration for custom component namespace.
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

        $this->registerCmsBladeComponents();
    }

    /**
     * Register CMS-specific Blade components.
     */
    private function registerCmsBladeComponents(): void
    {
        try {
            $componentNamespace = config('modules.namespace').'\\'.$this->name.'\\View\\Components';
            Blade::componentNamespace($componentNamespace, $this->nameLower);
        } catch (Throwable $exception) {
            Log::warning('Failed to register Blade components for CMS module: '.$exception->getMessage());
        }
    }

    /**
     * Get publishable view paths.
     *
     * @return array<string>
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
}
