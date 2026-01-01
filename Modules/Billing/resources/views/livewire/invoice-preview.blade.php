<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Modules\Billing\Models\Invoice;
use Modules\Billing\Enums\InvoiceStatus;

new class extends Component {
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice->load('items', 'billable');
    }

    public function downloadPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing::pdf.invoice', [
            'invoice' => $this->invoice,
        ]);

        $filename = $this->invoice->number
            ? "invoice-{$this->invoice->number}.pdf"
            : "invoice-draft-{$this->invoice->id}.pdf";

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }
};
?>

<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Header --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Invoice {{ $invoice->display_number }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Issued:') }} {{ $invoice->issue_date?->format('M d, Y') ?? 'Draft' }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                {{-- Status Badge --}}
                <span @class([
                    'inline-flex items-center px-3 py-1 rounded-full text-xs font-medium',
                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $invoice->status === InvoiceStatus::DRAFT,
                    'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' => $invoice->status === InvoiceStatus::SENT,
                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' => $invoice->status === InvoiceStatus::PAID,
                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' => $invoice->status === InvoiceStatus::OVERDUE,
                    'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 line-through' => $invoice->status === InvoiceStatus::VOID,
                ])>
                    {{ $invoice->status->getLabel() }}
                </span>
            </div>
        </div>
    </div>

    {{-- Invoice Details --}}
    <div class="px-6 py-4">
        {{-- {{ __('Bill To') }} --}}
        <div class="mb-6">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Bill To</h4>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $invoice->billable_name }}</p>
            @if($invoice->billable_email)
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->billable_email }}</p>
            @endif
        </div>

        {{-- Line Items --}}
        <div class="mb-6">
            <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Items</h4>
            <div class="space-y-2">
                @foreach($invoice->items as $item)
                    <div class="flex justify-between items-start py-2 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                        <div class="flex-1">
                            <p class="text-sm text-gray-900 dark:text-white">{{ $item->description }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $item->quantity }} × {{ number_format((float)$item->unit_price, 2) }} {{ $invoice->currency }}
                            </p>
                        </div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ number_format((float)$item->total, 2) }} {{ $invoice->currency }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- {{ __('Total') }}s --}}
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                <span class="text-gray-900 dark:text-white">{{ number_format((float)$invoice->subtotal, 2) }} {{ $invoice->currency }}</span>
            </div>
            
            @if((float)$invoice->discount_amount > 0)
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Discount</span>
                    <span class="text-red-600 dark:text-red-400">-{{ number_format((float)$invoice->discount_amount, 2) }} {{ $invoice->currency }}</span>
                </div>
            @endif
            
            <div class="flex justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-400">VAT ({{ $invoice->tax_rate }}%)</span>
                <span class="text-gray-900 dark:text-white">{{ number_format((float)$invoice->tax_amount, 2) }} {{ $invoice->currency }}</span>
            </div>
            
            <div class="flex justify-between text-lg font-semibold pt-2 border-t border-gray-200 dark:border-gray-700">
                <span class="text-gray-900 dark:text-white">Total</span>
                <span class="text-gray-900 dark:text-white">{{ number_format((float)$invoice->total, 2) }} {{ $invoice->currency }}</span>
            </div>
        </div>

        {{-- Due Date Warning --}}
        @if($invoice->isOverdue())
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-sm text-red-800 dark:text-red-300 flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                    Payment was due on {{ $invoice->due_date->format('M d, Y') }}
                </p>
            </div>
        @elseif($invoice->canBePaid() && $invoice->due_date)
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-300 flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5" />
                    Due on {{ $invoice->due_date->format('M d, Y') }}
                </p>
            </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-3">
        <button 
            wire:click="downloadPdf"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
        >
            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
            Download PDF
        </button>

        @if($invoice->canBePaid())
            <a 
                href="{{ route('billing.invoice.pay', $invoice) }}"
                class="inline-flex items-center gap-2 px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
            >
                <x-heroicon-o-credit-card class="w-4 h-4" />
                Pay Now
            </a>
        @endif

        @if($invoice->status === InvoiceStatus::PAID)
            <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 dark:text-green-300 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <x-heroicon-o-check-circle class="w-4 h-4" />
                Paid on {{ $invoice->paid_at->format('M d, Y') }}
            </span>
        @endif
    </div>
</div>
