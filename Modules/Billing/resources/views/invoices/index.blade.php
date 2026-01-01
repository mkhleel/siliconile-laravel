@extends('billing::layouts.app')

@section('content')
<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('My Invoices') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('View and manage your invoices') }}</p>
    </div>

    <livewire:billing::user-invoices />
</div>
@endsection
