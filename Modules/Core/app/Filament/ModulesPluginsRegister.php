<?php

namespace Modules\Core\Filament;

use Exception;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;

class ModulesPluginsRegister implements Plugin
{
    public function getId(): string
    {
        return 'modules';
    }

    public function register(Panel $panel): void
    {
        $plugins = $this->getModulePlugins();

        foreach ($plugins as $modulePlugin) {
            try {
                // Check if the class exists before instantiating
                if (class_exists($modulePlugin)) {
                    $panel->plugin(new $modulePlugin);
                }
            } catch (Exception $e) {
                // Log any exceptions that occur during plugin registration
                // \Illuminate\Support\Facades\Log::error("Error registering module plugin: {$modulePlugin}", [
                //     'exception' => $e->getMessage(),
                // ]);
            }
        }
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    protected function getModulePlugins(): array
    {
        try {
            $modulesPath = config('modules.paths.modules', base_path('Modules'));
            $basePath = str($modulesPath);
            $pattern = $basePath.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Filament'.DIRECTORY_SEPARATOR.'*Plugin.php';

            // Check if the modules directory exists
            if (! is_dir($modulesPath)) {
                Log::warning("Modules directory not found: {$modulesPath}");

                return [];
            }

            $pluginPaths = glob($pattern);

            if (empty($pluginPaths)) {
                Log::info("No module plugins found in pattern: {$pattern}");

                return [];
            }

            $plugins = collect($pluginPaths)
                ->map(function ($path) use ($modulesPath) {
                    try {
                        // Extract module name from path
                        $relativePath = str_replace($modulesPath.DIRECTORY_SEPARATOR, '', $path);
                        $moduleName = explode(DIRECTORY_SEPARATOR, $relativePath)[0];

                        // Debug information
                        // \Illuminate\Support\Facades\Log::debug("Found module plugin: {$path}", [
                        //     'module' => $moduleName,
                        //     'enabled' => Module::isEnabled($moduleName),
                        // ]);

                        // Check if module is enabled
                        if (Module::isEnabled($moduleName)) {
                            return $this->convertPathToNamespace($path);
                        }

                        return null;
                    } catch (Exception $e) {
                        Log::error("Error processing module plugin path: {$path}", [
                            'exception' => $e->getMessage(),
                        ]);

                        return null;
                    }
                })
                ->filter() // Remove null values
                ->toArray();

            // \Illuminate\Support\Facades\Log::info("Found " . count($plugins) . " enabled module plugins");
            return $plugins;
        } catch (Exception $e) {
            Log::error('Error getting module plugins', [
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function convertPathToNamespace(string $fullPath): string
    {
        try {
            $base = str(trim(config('modules.paths.modules', base_path('Modules')), '/\\'));
            $relative = str($fullPath)->afterLast($base)->replaceFirst(DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR);
            $namespace = str($relative)
                ->ltrim('/\\')
                ->prepend(DIRECTORY_SEPARATOR)
                ->prepend(config('modules.namespace', 'Modules'))
                ->replace(DIRECTORY_SEPARATOR, '\\')
                ->replace('\\\\', '\\')
                ->rtrim('.php')
                ->explode(DIRECTORY_SEPARATOR)
                ->map(fn ($piece) => str($piece)->studly()->toString())
                ->implode('\\');

            // Debug the namespace conversion
            // \Illuminate\Support\Facades\Log::debug("Converted path to namespace", [
            //     'path' => $fullPath,
            //     'namespace' => $namespace,
            // ]);

            return $namespace;
        } catch (Exception $e) {
            // \Illuminate\Support\Facades\Log::error("Error converting path to namespace: {$fullPath}", [
            //     'exception' => $e->getMessage(),
            // ]);

            // Return a fallback namespace based on the filename
            $filename = pathinfo($fullPath, PATHINFO_FILENAME);
            $moduleName = basename(dirname(dirname(dirname($fullPath))));
            $fallbackNamespace = "Modules\\{$moduleName}\\Filament\\{$filename}";

            Log::info("Using fallback namespace: {$fallbackNamespace}");

            return $fallbackNamespace;
        }
    }
}
