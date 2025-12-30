<?php

namespace Modules\Core\Providers;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->registerPluginProviders();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // This method is intentionally left empty
    }

    /**
     * Register service providers from plugins
     */
    protected function registerPluginProviders(): void
    {
        $pluginsPath = base_path('plugins');

        // Check if plugins directory exists
        if (! is_dir($pluginsPath)) {
            Log::info('Plugins directory not found: '.$pluginsPath);

            return;
        }

        // Get all plugin directories
        $pluginDirs = array_filter(glob($pluginsPath.'/*'), 'is_dir');

        foreach ($pluginDirs as $pluginDir) {
            $pluginJsonPath = $pluginDir.'/plugin.json';

            // Check if plugin.json exists
            if (! file_exists($pluginJsonPath)) {
                Log::warning('Plugin manifest not found: '.$pluginJsonPath);

                continue;
            }

            try {
                // Parse plugin.json
                $pluginData = json_decode(file_get_contents($pluginJsonPath), true);

                if (! $pluginData) {
                    Log::warning('Invalid plugin manifest: '.$pluginJsonPath);

                    continue;
                }

                // Check if provider is specified
                if (! isset($pluginData['provider'])) {
                    // Log::info('No service provider specified for plugin: ' . ($pluginData['name'] ?? basename($pluginDir)));
                    continue;
                }

                $providerClass = $pluginData['provider'];

                // Check if the provider class exists
                if (! class_exists($providerClass)) {
                    Log::warning('Provider class not found: '.$providerClass);

                    continue;
                }

                // Register the provider
                $this->app->register($providerClass);

            } catch (Exception $e) {
                Log::error('Error registering plugin provider: '.$pluginJsonPath, [
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }
}
