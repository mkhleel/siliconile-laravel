<?php

declare(strict_types=1);

namespace Modules\Payment\Providers;

use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Modules\Core\Providers\BaseModuleServiceProvider;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Livewire\PaymentMethodSelector;
use Modules\Payment\Services\PaymentGatewayManager;

final class PaymentServiceProvider extends BaseModuleServiceProvider
{
    protected string $name = 'Payment';

    protected string $nameLower = 'payment';

    /**
     * Custom boot logic for Payment module.
     */
    public function bootModule(): void
    {
        $this->registerPaymentGateways();
        $this->registerPaymentGatewayPlugins();

        Livewire::component('payment::payment-method-selector', PaymentMethodSelector::class);
    }

    /**
     * Register the service provider.
     */
    public function registerModule(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager($app);
        });

        $this->app->alias(PaymentGatewayManager::class, 'payment.gateway');

        // Register PaymentService for dependency injection
        $this->app->singleton(\Modules\Payment\Services\PaymentService::class);
    }

    /**
     * Get module commands.
     */
    protected function getModuleCommands(): array
    {
        return [];
    }

    /**
     * Override config registration to handle plugin configs.
     */
    protected function registerConfig(): void
    {
        parent::registerConfig();

        // Register configs in plugins that have a payment.php file inside config folder
        $pluginsPath = base_path('plugins');
        if (! File::exists($pluginsPath)) {
            return;
        }

        $directories = File::directories($pluginsPath);
        foreach ($directories as $directory) {
            $configPath = $directory.'/config/payment.php';
            if (! File::exists($configPath)) {
                continue;
            }
            $this->mergeConfigFrom($configPath, 'payment.gateways');
        }
    }

    /**
     * Register the built-in payment gateways.
     */
    protected function registerPaymentGateways(): void
    {
        $gatewayPath = module_path($this->name, 'app/Gateways');
        $namespace = 'Modules\\Payment\\Gateways\\';

        $this->registerGatewaysFromPath($gatewayPath, $namespace);
    }

    /**
     * Register payment gateway plugins.
     */
    protected function registerPaymentGatewayPlugins(): void
    {
        $pluginsPath = base_path('plugins');

        if (! File::exists($pluginsPath)) {
            return;
        }

        $directories = File::directories($pluginsPath);
        foreach ($directories as $directory) {
            $gatewayPath = $directory.'/src/Gateways';

            if (! File::exists($gatewayPath)) {
                continue;
            }

            $pluginName = str()->of(basename($directory))->studly()->toString();

            $namespace = "Plugins\\{$pluginName}\\Gateways\\";

            $this->registerGatewaysFromPath($gatewayPath, $namespace);
        }
    }

    /**
     * Register gateways from a specific path.
     */
    protected function registerGatewaysFromPath(string $path, string $namespace): void
    {
        if (! File::exists($path)) {
            return;
        }

        $files = File::files($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $namespace.$file->getFilenameWithoutExtension();

            // Exclude abstract classes
            if (str_starts_with($file->getFilenameWithoutExtension(), 'Abstract')) {
                continue;
            }

            if (class_exists($className) &&
                is_subclass_of($className, PaymentGatewayInterface::class) &&
                ! is_a($className, 'Abstract', true)) {
                $this->app->tag($className, ['payment.gateway']);
            }
        }
    }
}
