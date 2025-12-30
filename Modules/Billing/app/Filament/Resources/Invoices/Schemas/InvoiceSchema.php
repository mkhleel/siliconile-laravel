<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Membership\Models\Member;

class InvoiceSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        // Left Column - Main Details
                        Section::make('Invoice Details')
                            ->schema([
                                Forms\Components\Select::make('billable_type')
                                    ->label('Customer Type')
                                    ->options([
                                        'Modules\\Membership\\Models\\Member' => 'Member',
                                        'App\\Models\\User' => 'User',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('billable_id', null)),

                                Forms\Components\Select::make('billable_id')
                                    ->label('Customer')
                                    ->options(function (Get $get) {
                                        $type = $get('billable_type');

                                        if ($type === 'Modules\\Membership\\Models\\Member') {
                                            return Member::query()
                                                ->with('user')
                                                ->get()
                                                ->pluck('user.name', 'id')
                                                ->filter();
                                        }

                                        if ($type === 'App\\Models\\User') {
                                            return \App\Models\User::pluck('name', 'id');
                                        }

                                        return [];
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->visible(fn (Get $get) => filled($get('billable_type'))),

                                Forms\Components\TextInput::make('number')
                                    ->label('Invoice Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated on finalization')
                                    ->helperText('Generated automatically when invoice is finalized'),
                            ])
                            ->columns(1)
                            ->columnSpan(1),

                        // Right Column - Dates & Status
                        Section::make('Dates & Status')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options(InvoiceStatus::class)
                                    ->default(InvoiceStatus::DRAFT)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\DatePicker::make('issue_date')
                                    ->label('Issue Date')
                                    ->default(now())
                                    ->required(),

                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->default(now()->addDays(14))
                                    ->required()
                                    ->after('issue_date'),

                                Forms\Components\Select::make('currency')
                                    ->options([
                                        'SAR' => 'SAR - Saudi Riyal',
                                        'USD' => 'USD - US Dollar',
                                        'EUR' => 'EUR - Euro',
                                    ])
                                    ->default('SAR')
                                    ->required(),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ])
                    ->columns(2),

                // Line Items Section
                Section::make('Line Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->required()
                                    ->columnSpan(4),

                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->columnSpan(1)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemTotal($set, $get)),

                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('SAR')
                                    ->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemTotal($set, $get)),

                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Discount')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('SAR')
                                    ->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemTotal($set, $get)),

                                Forms\Components\TextInput::make('total')
                                    ->label('Line Total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->prefix('SAR')
                                    ->columnSpan(2),
                            ])
                            ->columns(11)
                            ->addActionLabel('Add Line Item')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                            ->defaultItems(1)
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['total'] = ($data['quantity'] ?? 1) * ($data['unit_price'] ?? 0) - ($data['discount_amount'] ?? 0);
                                return $data;
                            }),
                    ]),

                Grid::make()
                    ->schema([
                        // Totals Section
                        Section::make('Totals')
                            ->schema([
                                Forms\Components\TextInput::make('discount_amount')
                                    ->label('Invoice Discount')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('SAR')
                                    ->live(),

                                Forms\Components\Textarea::make('discount_description')
                                    ->label('Discount Reason')
                                    ->rows(2)
                                    ->visible(fn (Get $get) => (float) ($get('discount_amount') ?? 0) > 0),

                                Forms\Components\TextInput::make('tax_rate')
                                    ->label('VAT Rate (%)')
                                    ->numeric()
                                    ->default(15.00)
                                    ->suffix('%')
                                    ->live(),

                                Forms\Components\Placeholder::make('subtotal_display')
                                    ->label('Subtotal')
                                    ->content(fn (Get $get) => 'SAR ' . number_format(self::calculateSubtotal($get), 2)),

                                Forms\Components\Placeholder::make('tax_display')
                                    ->label('VAT')
                                    ->content(fn (Get $get) => 'SAR ' . number_format(self::calculateTax($get), 2)),

                                Forms\Components\Placeholder::make('total_display')
                                    ->label('Total')
                                    ->content(fn (Get $get) => 'SAR ' . number_format(self::calculateTotal($get), 2)),
                            ])
                            ->columnSpan(1),

                        // Notes Section
                        Section::make('Additional Information')
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notes to Customer')
                                    ->rows(3)
                                    ->placeholder('Any notes to display on the invoice...'),

                                Forms\Components\Textarea::make('terms')
                                    ->label('Terms & Conditions')
                                    ->rows(3)
                                    ->default(config('billing.default_invoice_terms')),
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Calculate line item total.
     */
    protected static function calculateItemTotal(Set $set, Get $get): void
    {
        $quantity = (int) ($get('quantity') ?? 1);
        $unitPrice = (float) ($get('unit_price') ?? 0);
        $discount = (float) ($get('discount_amount') ?? 0);

        $total = ($quantity * $unitPrice) - $discount;
        $set('total', round($total, 2));
    }

    /**
     * Calculate subtotal from items.
     */
    protected static function calculateSubtotal(Get $get): float
    {
        $items = $get('items') ?? [];

        return collect($items)->sum(function ($item) {
            return ((int) ($item['quantity'] ?? 1)) * ((float) ($item['unit_price'] ?? 0));
        });
    }

    /**
     * Calculate tax amount.
     */
    protected static function calculateTax(Get $get): float
    {
        $subtotal = self::calculateSubtotal($get);
        $invoiceDiscount = (float) ($get('discount_amount') ?? 0);
        $itemDiscounts = collect($get('items') ?? [])->sum(fn ($item) => (float) ($item['discount_amount'] ?? 0));
        $taxRate = (float) ($get('tax_rate') ?? 15);

        $taxableAmount = $subtotal - $invoiceDiscount - $itemDiscounts;

        return round($taxableAmount * ($taxRate / 100), 2);
    }

    /**
     * Calculate total.
     */
    protected static function calculateTotal(Get $get): float
    {
        $subtotal = self::calculateSubtotal($get);
        $invoiceDiscount = (float) ($get('discount_amount') ?? 0);
        $itemDiscounts = collect($get('items') ?? [])->sum(fn ($item) => (float) ($item['discount_amount'] ?? 0));
        $tax = self::calculateTax($get);

        return round($subtotal - $invoiceDiscount - $itemDiscounts + $tax, 2);
    }
}
