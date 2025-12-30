@extends('billing::layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('billing.invoice.index') }}" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
            <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
            Back to Invoices
        </a>
    </div>

    <livewire:billing::invoice-preview :invoice="$invoice" />
</div>
@endsection
