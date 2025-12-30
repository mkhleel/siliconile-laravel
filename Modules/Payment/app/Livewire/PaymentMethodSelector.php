<?php

namespace Modules\Payment\Livewire;

use Exception;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Payment\Exceptions\PaymentGatewayException;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentGatewayManager;
use Modules\Payment\Services\PaymentService;

class PaymentMethodSelector extends Component
{
    /**
     * The payable entity.
     */
    public $payable;

    /**
     * The payment data needed for processing.
     */
    public array $paymentData = [];

    /**
     * Payment amount.
     */
    public float $amount = 0;

    /**
     * The currency to use for the payment.
     */
    public string $currency;

    /**
     * Customer information.
     */
    #[Validate('required|email')]
    public string $customerEmail = '';

    #[Validate('required|string|min:3')]
    public string $customerName = '';

    /**
     * The selected payment gateway.
     */
    #[Validate('required|string')]
    public string $selectedGateway = '';

    /**
     * Any additional data required by specific payment gateways.
     */
    public array $gatewayData = [];

    /**
     * The available payment gateways.
     */
    public array $availableGateways = [];

    /**
     * Payment processing status.
     */
    public bool $isProcessing = false;

    /**
     * Payment result message.
     */
    public string $message = '';

    /**
     * Whether the payment was successful.
     */
    public bool $success = false;

    /**
     * The created payment record.
     */
    public ?Payment $payment = null;

    /**
     * Custom redirect URL after payment completion.
     */
    public ?string $redirectUrl = null;

    /**
     * Livewire component mount.
     */
    public function mount($payable, $amount, $currency = null, $redirectUrl = null)
    {
        $this->payable = $payable;
        $this->amount = (float) $amount;
        $this->currency = $currency ?? config('payment.default_currency', 'USD');
        $this->redirectUrl = $redirectUrl;

        // Get all available gateways
        //        $this->loadAvailableGateways();

        // Pre-select the first gateway if available
        if (count(app(PaymentService::class)->getAvailableGateways()) > 0) {
            $this->selectedGateway = array_key_first(app(PaymentService::class)->getAvailableGateways());
        }
    }

    /**
     * Load all available payment gateways.
     */
    //    protected function loadAvailableGateways()
    //    {
    //        $paymentService = app(PaymentService::class);
    //        $this->availableGateways = $paymentService->getAvailableGateways();
    //    }

    /**
     * Process the payment with the selected gateway.
     */
    public function processPayment()
    {
        $this->validate();

        $this->isProcessing = true;
        $this->message = 'Processing payment...';

        try {
            // Prepare payment data
            $paymentData = [
                'amount' => $this->amount,
                'currency' => $this->currency,
                'customer_email' => $this->customerEmail,
                'customer_name' => $this->customerName,
                'gateway' => $this->selectedGateway,
                'metadata' => [
                    'payable_type' => get_class($this->payable),
                    'payable_id' => $this->payable->id,
                ],
            ];

            // Create a payment record
            $paymentService = app(PaymentService::class);
            $this->payment = $paymentService->createPayment($paymentData, $this->payable, $this->selectedGateway);

            // Process the payment with the gateway
            $result = $paymentService->processPayment($this->payment, $this->gatewayData);

            // Handle payment result
            if ($result['success']) {
                $this->success = true;
                $this->message = $result['message'] ?? 'Payment successful!';

                // Check if we need to redirect (for gateways that require redirect)
                if (isset($result['redirect_url'])) {
                    return redirect()->to($result['redirect_url']);
                }

                // Otherwise, redirect to success URL if provided
                if ($this->redirectUrl) {
                    return redirect()->to($this->redirectUrl);
                }
            } else {
                $this->success = false;
                $this->message = $result['message'] ?? 'Payment failed. Please try again.';
            }
        } catch (PaymentGatewayException $e) {
            Log::error('Payment Error: '.$e->getMessage(), [
                'payable_type' => get_class($this->payable),
                'payable_id' => $this->payable->id,
                'gateway' => $this->selectedGateway,
            ]);

            $this->success = false;
            $this->message = 'Payment processing error: '.$e->getMessage();
        } catch (Exception $e) {
            Log::error('Unexpected Payment Error: '.$e->getMessage(), [
                'payable_type' => get_class($this->payable),
                'payable_id' => $this->payable->id,
            ]);

            $this->success = false;
            $this->message = 'An unexpected error occurred. Please try again.';
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Update gateway-specific form data when the gateway selection changes.
     */
    public function updatedSelectedGateway()
    {
        // Reset gateway-specific data when switching gateways
        $this->gatewayData = [];
        $this->dispatch('payment-gateway-changed', gateway: $this->selectedGateway);
    }

    /**
     * Get scripts required by the selected payment gateway.
     */
    public function getGatewayScripts()
    {
        if (empty($this->selectedGateway)) {
            return [];
        }

        $gateway = app(PaymentGatewayManager::class)->gateway($this->selectedGateway);

        return $gateway ? $gateway->getScripts() : [];
    }

    /**
     * Get styles required by the selected payment gateway.
     */
    public function getGatewayStyles()
    {
        if (empty($this->selectedGateway)) {
            return [];
        }

        $gateway = app(PaymentGatewayManager::class)->gateway($this->selectedGateway);

        return $gateway ? $gateway->getStyles() : [];
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('payment::livewire.payment-method-selector', [
            'gatewayScripts' => $this->getGatewayScripts(),
            'gatewayStyles' => $this->getGatewayStyles(),
        ]);
    }
}
