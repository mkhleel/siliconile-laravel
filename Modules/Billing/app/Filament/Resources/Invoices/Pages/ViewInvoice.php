<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Billing\Filament\Resources\Invoices\InvoiceResource;
use Modules\Billing\Mail\InvoiceMail;
use Modules\Billing\Services\InvoiceService;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice Information')
                    ->schema([
                        Components\TextEntry::make('display_number')
                            ->label('Invoice Number')
                            ->weight('bold')
                            ->size('lg'),

                        Components\TextEntry::make('status')
                            ->badge(),

                        Components\TextEntry::make('billable_name')
                            ->label('Customer'),

                        Components\TextEntry::make('billable_email')
                            ->label('Email')
                            ->copyable(),

                        Components\TextEntry::make('issue_date')
                            ->date('F j, Y'),

                        Components\TextEntry::make('due_date')
                            ->date('F j, Y')
                            ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),
                    ])
                    ->columns(3),

                Section::make('Line Items')
                    ->schema([
                        Components\RepeatableEntry::make('items')
                            ->schema([
                                Components\TextEntry::make('description')
                                    ->columnSpan(3),
                                Components\TextEntry::make('quantity')
                                    ->alignCenter(),
                                Components\TextEntry::make('unit_price')
                                    ->money(fn ($record) => $record->invoice?->currency ?? 'SAR')
                                    ->alignEnd(),
                                Components\TextEntry::make('discount_amount')
                                    ->money(fn ($record) => $record->invoice?->currency ?? 'SAR')
                                    ->alignEnd(),
                                Components\TextEntry::make('total')
                                    ->money(fn ($record) => $record->invoice?->currency ?? 'SAR')
                                    ->weight('bold')
                                    ->alignEnd(),
                            ])
                            ->columns(7),
                    ]),

                Section::make('Totals')
                    ->schema([
                        Components\TextEntry::make('subtotal')
                            ->money(fn ($record) => $record->currency)
                            ->alignEnd(),

                        Components\TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->money(fn ($record) => $record->currency)
                            ->visible(fn ($record) => (float) $record->discount_amount > 0)
                            ->alignEnd(),

                        Components\TextEntry::make('discount_description')
                            ->visible(fn ($record) => filled($record->discount_description)),

                        Components\TextEntry::make('tax_amount')
                            ->label(fn ($record) => "VAT ({$record->tax_rate}%)")
                            ->money(fn ($record) => $record->currency)
                            ->alignEnd(),

                        Components\TextEntry::make('total')
                            ->money(fn ($record) => $record->currency)
                            ->weight('bold')
                            ->size('lg')
                            ->alignEnd(),
                    ])
                    ->columns(1),

                Section::make('Payment Information')
                    ->schema([
                        Components\TextEntry::make('paid_at')
                            ->dateTime('F j, Y H:i')
                            ->placeholder('Not paid'),

                        Components\TextEntry::make('payment_reference')
                            ->placeholder('N/A')
                            ->copyable(),

                        Components\TextEntry::make('payment_method')
                            ->placeholder('N/A'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->status === InvoiceStatus::PAID),

                Section::make('Notes')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->markdown()
                            ->columnSpanFull(),

                        Components\TextEntry::make('terms')
                            ->label('Terms & Conditions')
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => filled($record->notes) || filled($record->terms))
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Edit Action (only for drafts)
            Actions\EditAction::make()
                ->visible(fn () => $this->record->isEditable()),

            // Finalize & Send Action
            Actions\Action::make('finalize')
                ->label('Finalize & Send')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Finalize Invoice')
                ->modalDescription('This will generate an invoice number and mark the invoice as sent. This action cannot be undone.')
                ->modalSubmitActionLabel('Finalize')
                ->visible(fn () => $this->record->status === InvoiceStatus::DRAFT)
                ->action(function () {
                    $invoiceService = app(InvoiceService::class);

                    try {
                        $invoiceService->finalize($this->record);

                        Notification::make()
                            ->title('Invoice Finalized')
                            ->body("Invoice {$this->record->number} has been finalized.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'number', 'sent_at']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Download PDF Action
            Actions\Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->action(function () {
                    $pdf = Pdf::loadView('billing::pdf.invoice', [
                        'invoice' => $this->record->load('items', 'billable'),
                    ]);

                    $filename = $this->record->number
                        ? "invoice-{$this->record->number}.pdf"
                        : "invoice-draft-{$this->record->id}.pdf";

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }),

            // Send Email Action
            Actions\Action::make('sendEmail')
                ->label('Send Email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('info')
                ->visible(fn () => $this->record->isFinalized())
                ->form([
                    Forms\Components\TextInput::make('email')
                        ->label('Recipient Email')
                        ->email()
                        ->required()
                        ->default(fn () => $this->record->billable_email),

                    Forms\Components\Textarea::make('message')
                        ->label('Additional Message')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        Mail::to($data['email'])->send(new InvoiceMail(
                            $this->record,
                            $data['message'] ?? null
                        ));

                        Notification::make()
                            ->title('Email Sent')
                            ->body("Invoice sent to {$data['email']}")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Send')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Mark as Paid Action
            Actions\Action::make('markPaid')
                ->label('Mark as Paid')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => $this->record->canBePaid())
                ->requiresConfirmation()
                ->form([
                    Forms\Components\TextInput::make('transaction_reference')
                        ->label('Transaction Reference')
                        ->placeholder('e.g., TXN-123456'),

                    Forms\Components\Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash' => 'Cash',
                            'bank_transfer' => 'Bank Transfer',
                            'credit_card' => 'Credit Card',
                            'online' => 'Online Payment',
                            'other' => 'Other',
                        ]),
                ])
                ->action(function (array $data) {
                    $invoiceService = app(InvoiceService::class);

                    try {
                        $invoiceService->markAsPaid(
                            $this->record,
                            $data['transaction_reference'] ?? null,
                            $data['payment_method'] ?? null
                        );

                        Notification::make()
                            ->title('Invoice Paid')
                            ->body("Invoice {$this->record->number} has been marked as paid.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'paid_at', 'payment_reference', 'payment_method']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Void Action
            Actions\Action::make('void')
                ->label('Void Invoice')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn () => $this->record->status->canBeVoided())
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Reason for Voiding')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $invoiceService = app(InvoiceService::class);

                    try {
                        $invoiceService->void($this->record, $data['reason']);

                        Notification::make()
                            ->title('Invoice Voided')
                            ->body('Invoice has been voided.')
                            ->warning()
                            ->send();

                        $this->refreshFormData(['status', 'voided_at']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            // Duplicate Action
            Actions\Action::make('duplicate')
                ->label('Duplicate')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->action(function () {
                    $invoiceService = app(InvoiceService::class);
                    $newInvoice = $invoiceService->duplicate($this->record);

                    Notification::make()
                        ->title('Invoice Duplicated')
                        ->body('A new draft invoice has been created.')
                        ->success()
                        ->send();

                    return redirect(InvoiceResource::getUrl('edit', ['record' => $newInvoice]));
                }),
        ];
    }
}
