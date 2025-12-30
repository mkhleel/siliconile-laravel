<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Illuminate\Contracts\Container\Container;
use Modules\Payment\Contracts\PaymentGatewayInterface;

class PaymentGatewayManager
{
    /**
     * The registered gateways.
     *
     * @var array<string, PaymentGatewayInterface>
     */
    protected array $gateways = [];

    /**
     * Create a new payment gateway manager instance.
     */
    public function __construct(
        protected Container $container
    ) {
        $this->registerTaggedGateways();
    }

    /**
     * Register the payment gateways from the container that were tagged.
     *
     * @return void
     */
    protected function registerTaggedGateways()
    {
        if (! $this->container->tagged('payment.gateway')) {
            return;
        }

        foreach ($this->container->tagged('payment.gateway') as $gateway) {
            $this->addGateway($gateway);
        }
    }

    /**
     * Add a payment gateway to the manager.
     *
     * @return void
     */
    public function addGateway(PaymentGatewayInterface $gateway)
    {
        $this->gateways[$gateway->getIdentifier()] = $gateway;
    }

    /**
     * Get a payment gateway instance.
     *
     * @param  string|null  $name
     * @return PaymentGatewayInterface|null
     */
    public function gateway($name = null)
    {
        if (is_null($name)) {
            return $this->getDefaultGateway();
        }

        return $this->gateways[$name] ?? null;
    }

    /**
     * Get the default payment gateway.
     *
     * @return PaymentGatewayInterface|null
     */
    public function getDefaultGateway()
    {
        $defaultGateway = config('payment.default_gateway');

        return $this->gateway($defaultGateway);
    }

    /**
     * Get all registered payment gateways.
     *
     * @param  bool  $onlyEnabled  Get only enabled gateways
     * @return array
     */
    public function getGateways($onlyEnabled = true)
    {
        if (! $onlyEnabled) {
            return $this->gateways;
        }

        return array_filter($this->gateways, function ($gateway) {
            return $gateway->isEnabled();
        });
    }
}
