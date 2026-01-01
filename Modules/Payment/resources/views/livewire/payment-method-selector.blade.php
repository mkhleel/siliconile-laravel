<div class="payment-method-selector">
    {{-- Include required scripts for the gateway --}}
    @foreach($gatewayScripts as $script)
        <script src="{{ $script }}"></script>
    @endforeach

    {{-- Include required styles for the gateway --}}
    @foreach($gatewayStyles as $style)
        <link href="{{ $style }}" rel="stylesheet">
    @endforeach

    <div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">{{ __('Payment Details') }}</h2>

        @if($isProcessing)
            <div class="flex items-center justify-center py-8">
                <svg class="animate-spin -ml-1 mr-3 h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-lg font-medium text-gray-700">{{ $message }}</span>
            </div>
        @elseif($success)
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>{{ $message }}</p>
            </div>
        @elseif($message)
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p>{{ $message }}</p>
            </div>
        @endif

        <div class="bg-gray-50 p-4 rounded-md mb-6">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-500">{{ __('Amount') }}</span>
                <span class="text-lg font-bold">{!! formatCurrency($amount, 2) !!}</span>
            </div>
        </div>

        <form wire:submit="processPayment">
            @if(count(app(\Modules\Payment\Services\PaymentService::class)->getAvailableGateways()) > 0)
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Select Payment Method') }}</label>

                    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2">
                        @foreach(app(\Modules\Payment\Services\PaymentService::class)->getAvailableGateways() as $identifier => $gateway)
                            <label class="relative flex items-start p-4 border rounded-lg cursor-pointer
                                {{ $selectedGateway === $identifier ? 'border-primary bg-primary bg-opacity-5' : 'border-gray-200' }}">
                                <div class="flex items-center h-5">
                                    <input wire:model.live="selectedGateway"
                                        type="radio"
                                        name="paymentGateway"
                                        value="{{ $identifier }}"
                                        class="h-4 w-4 text-primary border-gray-300 focus:ring-primary">
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="font-medium text-gray-900">{{ $gateway->getName() }}</span>
                                    @if($gateway->getIcon())
                                        <img src="{{ $gateway->getIcon() }}" alt="{{ $gateway->getName() }}" class="h-8 mt-1">
                                    @endif
                                    <p class="text-gray-500 mt-1">{{ $gateway->getDescription() }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('selectedGateway') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>
            @else
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
                    <p>{{ __('No payment methods are currently available.') }}</p>
                </div>
            @endif

            {{-- Dynamic gateway-specific fields will appear here via JavaScript --}}
            <div id="gateway-specific-fields" class="mb-6">
                <!-- Gateway-specific form fields will be loaded here -->
            </div>

            <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-opacity-50 transition"
                {{ count($availableGateways) === 0 ? 'disabled' : '' }}
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75">
                <span wire:loading.remove>{{ __('Pay Now') }}</span>
                <span wire:loading>Processing...</span>
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('payment-gateway-changed', (gateway) => {
                // This event is triggered when the user changes payment gateway
                // Here you can initialize gateway-specific JS if needed
                console.log('Payment gateway changed to:', gateway);

                // Example of how to handle different gateways
                const gatewaySpecificFields = document.getElementById('gateway-specific-fields');

                // Clear previous fields
                gatewaySpecificFields.innerHTML = '';

                // Add gateway-specific fields based on the selected gateway
                if (gateway === 'stripe') {
                    // Initialize Stripe Elements if needed
                    if (typeof Stripe !== 'undefined') {
                        const stripe = Stripe('{{ config("payment.gateways.stripe.publishable_key", "") }}');
                        const elements = stripe.elements();

                        // Create a card element
                        const cardElement = document.createElement('div');
                        cardElement.id = 'card-element';
                        cardElement.className = 'p-3 border rounded-md';

                        const cardLabel = document.createElement('label');
                        cardLabel.className = 'block text-sm font-medium text-gray-700 mb-2';
                        cardLabel.textContent = 'Card Details';

                        gatewaySpecificFields.appendChild(cardLabel);
                        gatewaySpecificFields.appendChild(cardElement);

                        const card = elements.create('card');
                        card.mount('#card-element');

                        card.addEventListener('change', event => {
                            const displayError = document.getElementById('card-errors');
                            if (event.error) {
                                displayError.textContent = event.error.message;
                            } else {
                                displayError.textContent = '';
                            }
                        });
                    }
                } else if (gateway === 'paypal') {
                    // PayPal specific fields if needed
                }
            });
        });
    </script>
</div>
