<?php

declare(strict_types=1);

namespace Modules\Payment\Gateways;

use Illuminate\Http\Request;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Models\Payment;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    /**
     * AbstractPaymentGateway constructor.
     *
     * @param array<string, mixed> $config Configuration for the payment gateway
     */
    public function __construct(
        protected array $config
    ) {}

    /**
     * Check if the payment gateway is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? false;
    }

    /**
     * Get the icon/logo of the payment gateway
     */
    public function getIcon(): ?string
    {
        return $this->config['icon'] ?? null;
    }

    /**
     * Get frontend scripts required by this gateway
     */
    public function getScripts(): array
    {
        return $this->config['scripts'] ?? [];
    }

    /**
     * Get frontend styles required by this gateway
     */
    public function getStyles(): array
    {
        return $this->config['styles'] ?? [];
    }

    /**
     * Get the supported countries for this gateway
     */
    public function getSupportedCountries(): array
    {
        return $this->config['countries'] ?? [];
    }

    /**
     * Check if the gateway supports a specific country
     */
    public function supportsCountry(string $countryCode): bool
    {
        $supportedCountries = $this->getSupportedCountries();
        
        // If no countries are specified, assume it supports all countries
        if (empty($supportedCountries)) {
            return true;
        }
        
        return in_array(strtoupper($countryCode), array_map('strtoupper', $supportedCountries));
    }

    /**
     * Handle webhook notifications from the payment gateway
     */
    public function handleWebhook(Request $request): array
    {
        return [
            'success' => false,
            'message' => 'Webhook not implemented for this gateway',
        ];
    }

    protected function mapStatus(string $status): string
    {
        // Map the status from the payment gateway to a more generic status
        return match ($status) {
            'paid', 'SUCCESS', 'success', 'COMPLETE', 'PAID' => Payment::STATUS_COMPLETED,
            'unpaid', 'processing', 'PENDING' => Payment::STATUS_PENDING,
            'failed', 'cancelled', 'FAILED', 'CANCELLED' => Payment::STATUS_FAILED,
            default => $status,
        };
    }
}
