<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Enums\InvoiceStatus;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    /**
     * Get invoices for the current user.
     */
    public function with(): array
    {
        $user = Auth::user();
        
        // Get the member associated with this user
        $member = $user->member ?? null;
        
        $invoicesQuery = Invoice::query()
            ->with('items')
            ->latest('created_at');
        
        // Filter by billable (could be User or Member)
        if ($member) {
            $invoicesQuery->where(function ($query) use ($user, $member) {
                $query->where(function ($q) use ($member) {
                    $q->where('billable_type', get_class($member))
                      ->where('billable_id', $member->id);
                })->orWhere(function ($q) use ($user) {
                    $q->where('billable_type', get_class($user))
                      ->where('billable_id', $user->id);
                });
            });
        } else {
            $invoicesQuery->where('billable_type', get_class($user))
                         ->where('billable_id', $user->id);
        }
        
        return [
            'invoices' => $invoicesQuery->take(10)->get(),
            'unpaidCount' => (clone $invoicesQuery)->unpaid()->count(),
            'overdueCount' => (clone $invoicesQuery)->overdue()->count(),
        ];
    }
};
?>

<div class="space-y-6">
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Total Invoices --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <x-heroicon-o-document-text class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Invoices</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $invoices->count() }}</p>
                </div>
            </div>
        </div>

        {{-- Unpaid --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <x-heroicon-o-clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Awaiting Payment</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $unpaidCount }}</p>
                </div>
            </div>
        </div>

        {{-- Overdue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Overdue</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $overdueCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Invoices List --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Invoices</h3>
        </div>

        @if($invoices->isEmpty())
            <div class="p-8 text-center">
                <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600" />
                <p class="mt-4 text-gray-500 dark:text-gray-400">No invoices found.</p>
            </div>
        @else
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($invoices as $invoice)
                    <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-3">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $invoice->display_number }}
                                    </p>
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $invoice->status === InvoiceStatus::DRAFT,
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $invoice->status === InvoiceStatus::SENT,
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $invoice->status === InvoiceStatus::PAID,
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' => $invoice->status === InvoiceStatus::OVERDUE,
                                        'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => $invoice->status === InvoiceStatus::VOID,
                                    ])>
                                        {{ $invoice->status->getLabel() }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Issued: {{ $invoice->issue_date?->format('M d, Y') ?? 'Draft' }}
                                    @if($invoice->due_date)
                                        • Due: {{ $invoice->due_date->format('M d, Y') }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ number_format((float)$invoice->total, 2) }} {{ $invoice->currency }}
                                </p>
                                <a 
                                    href="{{ route('billing.invoice.show', $invoice) }}"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                >
                                    <x-heroicon-o-arrow-right class="w-5 h-5" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
