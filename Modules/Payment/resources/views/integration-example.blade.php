<x-layouts.app>

{{--@section('content')--}}
<div class="container mx-auto py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Payment Module Integration Example</h1>


        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Live Demo</h2>

                <div class="bg-gray-50 p-4 rounded-md mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-medium">Demo Order #12345</span>
                            <p class="text-sm text-gray-500">2 items</p>
                        </div>
                        <span class="text-lg font-bold">$99.99</span>
                    </div>
                </div>

                {{-- This would normally be populated with actual data from your application --}}
                <div>
                    @php
                        // This is just dummy data for demonstration purposes
                        $demoOrder = (object)[
                            'id' => 12345,
                            'total_amount' => 99.99,
                            'currency' => 'USD'
                        ];
                    @endphp

                    {{-- Include the payment form component --}}
                    @livewire('payment::payment-method-selector', [
                        'payable' => $demoOrder,
                        'amount' => $demoOrder->total_amount,
                        'currency' => $demoOrder->currency,
                        'redirectUrl' => route('payment.success')
                    ])

{{--                    <livewire:payment::payment-method-selector :payable="$demoOrder" :amount="$demoOrder->total_amount" :currency="$demoOrder->currency" :redirectUrl="route('payment.success')" />--}}

                </div>
            </div>
        </div>
    </div>
</div>
{{--@endsection--}}
</x-layouts.app>
