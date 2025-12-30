<?php

namespace Modules\Core\Services;

use Nwidart\Modules\Facades\Module;
use ReflectionClass;
use ReflectionProperty;
use Schema;
use Spatie\LaravelSettings\Settings;

class ConfigSync
{
    public function sync(): void
    {
        $mappings_core = config('core.settings_mapping', []);
        $mappings_default = config('settings_mapping', []);
        $mappings = array_merge($mappings_core, $mappings_default);

        if (! Schema::hasTable('settings')) {
            return;
        }

        $this->syncModuleSettings($mappings);
    }

    protected function syncModuleSettings($mappings = []): void
    {
        foreach (Module::allEnabled() as $module) {
            $settingsClasses = $this->findModuleSettingsClasses($module->getName());
            foreach ($settingsClasses as $settingsClass) {
                if (! class_exists($settingsClass)) {
                    continue;
                }

                $instance = app($settingsClass);
                $properties = (new ReflectionClass($settingsClass))->getProperties(ReflectionProperty::IS_PUBLIC);
                foreach ($properties as $property) {
                    if ($property->isStatic()) {
                        continue;
                    }

                    $propertyName = $property->getName();
                    $value = $instance->$propertyName;

                    if (is_null($value) || (is_string($value) && empty($value))) {
                        continue;
                    }

                    // Try to find mapping in settings_mapping.php
                    $configKey = $mappings[$propertyName] ?? null;

                    // If no explicit mapping, use module-based mapping
                    if (! $configKey) {
                        $configKey = $this->determineConfigKeyFromProperty(
                            $module->getName(),
                            $settingsClass,
                            $propertyName
                        );
                    }

                    if ($configKey) {
                        config([$configKey => $value]);
                    }
                }
            }
        }
    }

    protected function findModuleSettingsClasses(string $moduleName): array
    {
        $namespace = "Modules\\{$moduleName}\\Settings";
        $path = module_path($moduleName, 'app/Settings');
        if (! is_dir($path)) {
            return [];
        }

        $classes = [];
        $files = glob($path.'/*.php');

        foreach ($files as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullClassName = "{$namespace}\\{$className}";

            if (class_exists($fullClassName) && is_subclass_of($fullClassName, Settings::class)) {
                $classes[] = $fullClassName;
            }
        }

        return $classes;
    }

    protected function determineConfigKeyFromProperty(string $moduleName, string $settingsClass, string $property): ?string
    {
        // Get the settings group
        $group = null;
        if (method_exists($settingsClass, 'group')) {
            $group = $settingsClass::group();
        }

        // Default naming scheme for configs:
        // For AISettings->openai_api_key, create ai.openai_api_key
        $moduleSlug = strtolower($moduleName);

        if ($group) {
            // If there's a custom group defined, use it
            return "{$group}.{$property}";
        }

        return "{$moduleSlug}.{$property}";
    }
}
