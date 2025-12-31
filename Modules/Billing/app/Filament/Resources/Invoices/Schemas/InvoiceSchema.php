<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Billing\Enums\InvoiceStatus;
use Modules\Membership\Models\Member;

class InvoiceSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(12)
                    ->columnSpanFull()
                    ->schema([
                        // Main Content Area (2/3 width)
                        Group::make()
                            ->columnSpan(9)
                            ->schema([
                                // Customer & Invoice Details
                                Section::make(__('Invoice Details'))
                                    ->description(__('Customer and invoice identification'))
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\Select::make('billable_type')
                                                ->label(__('Customer Type'))
                                                ->options([
                                                    'Modules\\Membership\\Models\\Member' => 'Member',
                                                    'App\\Models\\User' => 'User',
                                                ])
                                                ->required()
                                                ->native(false)
                                                ->live()
                                                ->afterStateUpdated(fn (Set $set) => $set('billable_id', null)),

                                            Forms\Components\Select::make('billable_id')
                                                ->label(__('Customer'))
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
                                                ->native(false)
                                                ->visible(fn (Get $get) => filled($get('billable_type'))),
                                        ]),

                                        Forms\Components\TextInput::make('number')
                                            ->label(__('Invoice Number'))
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder(__('Auto-generated on finalization'))
                                            ->helperText(__('Generated automatically when invoice is finalized'))
                                            ->prefixIcon('heroicon-o-hashtag'),
                                    ]),

                                // Line Items Section
                                Section::make(__('Line Items'))
                                    ->description(__('Products or services being invoiced'))
                                    ->icon('heroicon-o-queue-list')
                                    ->schema([
                                        Forms\Components\Repeater::make('items')
                                            ->relationship()
                                            ->schema([
                                                Forms\Components\TextInput::make('description')
                                                    ->label(__('Description'))
                                                    ->required()
                                                    ->columnSpan(4),

                                                Forms\Components\TextInput::make('quantity')
                                                    ->label(__('Qty'))
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1)
                                                    ->required()
                                                    ->columnSpan(1)
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemTotal($set, $get)),

                                                Forms\Components\TextInput::make('unit_price')
                                                    ->label(__('Unit Price'))
                                                    ->numeric()
                                                    ->required()
                                                    ->prefix('SAR')
                                                    ->columnSpan(2)
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemTotal($set, $get)),

                                                Forms\Components\TextInput::make('discount_amount')
                                                    ->label(__('Discount'))
                                                    ->numeric()
                                                    ->default(0)
                                                    ->prefix('SAR')
                                                    ->columnSpan(2)
                                                    ->live()
                                                    ->afterStateUpdated(fn (Set $set, Get $get) => self::calculateItemTotal($set, $get)),

                                                Forms\Components\TextInput::make('total')
                                                    ->label(__('Total'))
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->prefix('SAR')
                                                    ->columnSpan(2),
                                            ])
                                            ->columns(11)
                                            ->addActionLabel(__('Add Line Item'))
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

                                // Notes Section (collapsible)
                                Section::make(__('Additional Information'))
                                    ->description(__('Notes and terms for the invoice'))
                                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label(__('Notes to Customer'))
                                            ->rows(3)
                                            ->placeholder(__('Any notes to display on the invoice...')),

                                        Forms\Components\Textarea::make('terms')
                                            ->label(__('Terms & Conditions'))
                                            ->rows(3)
                                            ->default(config('billing.default_invoice_terms')),
                                    ]),
                            ]),

                        // Sidebar (1/3 width)
                        Group::make()
                            ->columnSpan(3)
                            ->schema([
                                // Status & Dates
                                Section::make(__('Status & Dates'))
                                    ->icon('heroicon-o-calendar')
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->label(__('Status'))
                                            ->options(InvoiceStatus::class)
                                            ->default(InvoiceStatus::DRAFT)
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->native(false),

                                        Forms\Components\DatePicker::make('issue_date')
                                            ->label(__('Issue Date'))
                                            ->default(now())
                                            ->required()
                                            ->native(false),

                                        Forms\Components\DatePicker::make('due_date')
                                            ->label(__('Due Date'))
                                            ->default(now()->addDays(14))
                                            ->required()
                                            ->after('issue_date')
                                            ->native(false),

                                        Forms\Components\Select::make('currency')
                                            ->label(__('Currency'))
                                            ->options([
                                                'SAR' => 'SAR - Saudi Riyal',
                                                'USD' => 'USD - US Dollar',
                                                'EUR' => 'EUR - Euro',
                                            ])
                                            ->default('SAR')
                                            ->required()
                                            ->native(false),
                                    ]),

                                // Totals Section
                                Section::make(__('Totals'))
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Forms\Components\TextInput::make('discount_amount')
                                            ->label(__('Invoice Discount'))
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('SAR')
                                            ->live(),

                                        Forms\Components\Textarea::make('discount_description')
                                            ->label(__('Discount Reason'))
                                            ->rows(2)
                                            ->visible(fn (Get $get) => (float) ($get('discount_amount') ?? 0) > 0),

                                        Forms\Components\TextInput::make('tax_rate')
                                            ->label(__('VAT Rate'))
                                            ->numeric()
                                            ->default(15.00)
                                            ->suffix('%')
                                            ->live(),

                                        Forms\Components\Placeholder::make('subtotal_display')
                                            ->label(__('Subtotal'))
                                            ->content(fn (Get $get) => 'SAR ' . number_format(self::calculateSubtotal($get), 2)),

                                        Forms\Components\Placeholder::make('tax_display')
                                            ->label(__('VAT'))
                                            ->content(fn (Get $get) => 'SAR ' . number_format(self::calculateTax($get), 2)),

                                        Forms\Components\Placeholder::make('total_display')
                                            ->label(__('Total'))
                                            ->content(fn (Get $get) => 'SAR ' . number_format(self::calculateTotal($get), 2)),
                                    ]),
                            ]),
                    ]),
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
