@extends('billing::layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('billing.invoice.show', $invoice) }}" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
            Back to Invoice
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Pay Invoice {{ $invoice->display_number }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Total Amount: <span class="font-bold text-gray-900 dark:text-white">{{ number_format((float)$invoice->total, 2) }} {{ $invoice->currency }}</span>
            </p>
        </div>

        <div class="p-6">
            {{-- This form would integrate with your Payment module --}}
            <form action="{{ route('payment.checkout') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="payable_type" value="{{ get_class($invoice) }}">
                <input type="hidden" name="payable_id" value="{{ $invoice->id }}">
                <input type="hidden" name="amount" value="{{ $invoice->total }}">
                <input type="hidden" name="currency" value="{{ $invoice->currency }}">
                <input type="hidden" name="description" value="Invoice {{ $invoice->display_number }}">

                {{-- Payment Method Selection --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Select Payment Method
                    </label>
                    <div class="space-y-3">
                        @foreach(app(\Modules\Payment\Services\PaymentGatewayManager::class)->getGateways(true) as $gateway => $config)
                            <label class="relative flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <input type="radio" name="gateway" value="{{ $gateway }}" class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500" required>
                                <span class="ml-3 flex-1">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $config['name'] ?? ucfirst($gateway) }}
                                    </span>
                                    @if(isset($config['description']))
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                                            {{ $config['description'] }}
                                        </span>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Summary --}}
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="text-sm text-gray-900 dark:text-white">{{ number_format((float)$invoice->subtotal, 2) }} {{ $invoice->currency }}</span>
                    </div>
                    @if((float)$invoice->discount_amount > 0)
                        <div class="flex justify-between mb-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Discount</span>
                            <span class="text-sm text-red-600">-{{ number_format((float)$invoice->discount_amount, 2) }} {{ $invoice->currency }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">VAT ({{ $invoice->tax_rate }}%)</span>
                        <span class="text-sm text-gray-900 dark:text-white">{{ number_format((float)$invoice->tax_amount, 2) }} {{ $invoice->currency }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">Total</span>
                        <span class="text-base font-semibold text-gray-900 dark:text-white">{{ number_format((float)$invoice->total, 2) }} {{ $invoice->currency }}</span>
                    </div>
                </div>

                <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <x-heroicon-o-credit-card class="w-5 h-5" />
                    Pay {{ number_format((float)$invoice->total, 2) }} {{ $invoice->currency }}
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
