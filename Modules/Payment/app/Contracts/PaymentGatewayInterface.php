<?php

namespace Modules\Payment\Contracts;

use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Get the display name of the payment gateway
     */
    public function getName(): string;

    /**
     * Get the unique identifier of the payment gateway
     */
    public function getIdentifier(): string;

    /**
     * Get the description of the payment gateway
     */
    public function getDescription(): string;

    /**
     * Get the icon/logo of the payment gateway
     */
    public function getIcon(): ?string;

    /**
     * Check if the payment gateway is enabled
     */
    public function isEnabled(): bool;

    /**
     * Process the payment
     */
    public function processPayment(array $paymentData): array;

    /**
     * Handle webhook notifications from the payment gateway
     */
    public function handleWebhook(Request $request): array;

    /**
     * Verify the payment status
     */
    public function verifyPayment(string $paymentId): array;

    /**
     * Get frontend scripts required by this gateway
     */
    public function getScripts(): array;

    /**
     * Get frontend styles required by this gateway
     */
    public function getStyles(): array;

    /**
     * Get the supported countries for this gateway
     */
    public function getSupportedCountries(): array;
}
