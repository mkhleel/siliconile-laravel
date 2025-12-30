<?php

namespace Modules\Billing\Filament\Resources\Orders;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Billing\Enums\OrderStatus;
use Modules\Billing\Filament\Resources\Orders\Pages\CreateOrder;
use Modules\Billing\Filament\Resources\Orders\Pages\EditOrder;
use Modules\Billing\Filament\Resources\Orders\Pages\ListOrders;
use Modules\Billing\Filament\Resources\Orders\Pages\ViewOrder;
use Modules\Billing\Models\Order;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $slug = 'billing/orders';

    protected static ?string $recordTitleAttribute = 'order_number';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    public static function getNavigationLabel(): string
    {
        return __('Orders');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Billing');
    }

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        // Main Content Area (2/3 width)
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                // Order Information Section
                                Section::make(__('Order Information'))
                                    ->description(__('Basic order details and customer information'))
                                    ->icon(Heroicon::ShoppingCart)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('order_number')
                                                    ->label('Order Number')
                                                    ->required()
                                                    ->disabled()
                                                    ->unique(ignoreRecord: true)
                                                    ->columnSpan(1),

                                                Select::make('status')
                                                    ->label('Order Status')
                                                    ->options(OrderStatus::class)
                                                    ->required()
                                                    ->columnSpan(1),
                                            ]),

                                        Select::make('user_id')
                                            ->label('Customer')
                                            ->relationship('user', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('currency')
                                                    ->label('Currency')
                                                    ->required()
                                                    ->length(3)
                                                    ->columnSpan(1),

                                                TextInput::make('payment_gateway')
                                                    ->label('Payment Method')
                                                    ->columnSpan(1),
                                            ]),

                                        DateTimePicker::make('paid_at')
                                            ->label('Payment Date')
                                            ->columnSpanFull(),
                                    ]),

                                // Financial Details Section
                                Section::make(__('Financial Details'))
                                    ->description(__('Order pricing and financial breakdown'))
                                    ->icon(Heroicon::CurrencyDollar)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->columnSpan(1),

                                                TextInput::make('discount_total')
                                                    ->label('Discount Total')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('tax')
                                                    ->label('Tax Amount')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->columnSpan(1),

                                                TextInput::make('total')
                                                    ->label('Total Amount')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // Sidebar (1/3 width)
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                // Notes Section
                                Section::make(__('Order Notes'))
                                    ->description(__('Additional notes and comments'))
                                    ->icon(Heroicon::DocumentText)
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('note')
                                            ->label('Internal Notes')
                                            ->helperText(__('Private notes for internal use'))
                                            ->columnSpanFull(),
                                    ]),

                                // Addresses Section
                                Section::make(__('Addresses'))
                                    ->description(__('Billing and shipping addresses'))
                                    ->icon(Heroicon::MapPin)
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('billing_address')
                                            ->label('Billing Address')
                                            ->columnSpanFull(),

                                        Textarea::make('shipping_address')
                                            ->label('Shipping Address')
                                            ->columnSpanFull(),
                                    ]),

                                // Metadata Section
                                Section::make(__('Metadata'))
                                    ->description(__('Additional order metadata'))
                                    ->icon(Heroicon::Cog6Tooth)
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('meta')
                                            ->label('Order Metadata')
                                            ->helperText(__('JSON or additional order data'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tracking_number')
                    ->searchable()
                    ->sortable()
                    ->placeholder(__('Not shipped'))
                    ->toggleable(),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->sortable()
                    ->badge(),
                TextColumn::make('total')
                    ->sortable()
                    ->money(fn ($record) => $record->currency),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(OrderStatus::class),
                Filter::make('paid')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('paid_at')),
                Filter::make('unpaid')
                    ->query(fn (Builder $query): Builder => $query->whereNull('paid_at')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    Action::make('mark_as_processing')
                        ->icon(Heroicon::ArrowPath)
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading(__('Mark as Processing'))
                        ->modalDescription(__('Are you sure you want to mark this order as processing?'))
                        ->action(fn (Order $record) => $record->setStatus(OrderStatus::PROCESSING, __('Order is being prepared')))
                        ->visible(fn (Order $record) => $record->status === OrderStatus::PENDING),

                    Action::make('mark_as_shipped')
                        ->icon(Heroicon::Truck)
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading(__('Mark as Shipped'))
                        ->modalDescription(__('Are you sure you want to mark this order as shipped?'))
                        ->form([
                            Textarea::make('shipping_notes')
                                ->label('Shipping Notes')
                                ->placeholder(__('Tracking number, carrier name, etc.'))
                                ->rows(3),
                        ])
                        ->action(function (Order $record, array $data) {
                            $description = __('Order has been handed to shipping company');
                            if (! empty($data['shipping_notes'])) {
                                $description .= ' - '.$data['shipping_notes'];
                            }
                            $record->setStatus(OrderStatus::SHIPPED, $description);
                        })
                        ->visible(fn (Order $record) => $record->status === OrderStatus::PROCESSING),

                    Action::make('mark_as_out_for_delivery')
                        ->icon(Heroicon::MapPin)
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(__('Mark as Out for Delivery'))
                        ->modalDescription(__('Are you sure this order is out for delivery?'))
                        ->action(fn (Order $record) => $record->setStatus(OrderStatus::OUT_FOR_DELIVERY, __('Order is out for delivery')))
                        ->visible(fn (Order $record) => $record->status === OrderStatus::SHIPPED),

                    Action::make('mark_as_delivered')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('Mark as Delivered'))
                        ->modalDescription(__('Confirm that this order has been delivered?'))
                        ->action(fn (Order $record) => $record->setStatus(OrderStatus::DELIVERED, __('Order has been delivered')))
                        ->visible(fn (Order $record) => $record->status === OrderStatus::OUT_FOR_DELIVERY),

                    Action::make('mark_as_completed')
                        ->icon(Heroicon::CheckBadge)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading(__('Mark as Completed'))
                        ->modalDescription(__('Are you sure you want to mark this order as completed?'))
                        ->action(fn (Order $record) => $record->setStatus(OrderStatus::COMPLETED, __('Order is complete')))
                        ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::PENDING, OrderStatus::DELIVERED])),

                    Action::make('cancel_order')
                        ->icon(Heroicon::XCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('Cancel Order'))
                        ->modalDescription(__('Are you sure you want to cancel this order?'))
                        ->form([
                            Textarea::make('cancellation_reason')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Order $record, array $data) {
                            $record->setStatus(OrderStatus::CANCELLED, $data['cancellation_reason']);
                        })
                        ->visible(fn (Order $record) => ! in_array($record->status, [OrderStatus::COMPLETED, OrderStatus::CANCELLED, OrderStatus::REFUNDED])),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Order Information'))
                    ->schema([
                        TextEntry::make('order_number')
                            ->label('Order Number'),
                        TextEntry::make('tracking_number')
                            ->label('Tracking Number')
                            ->placeholder(__('Not assigned'))
                            ->copyable()
                            ->copyMessage(__('Tracking number copied'))
                            ->copyMessageDuration(1500),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('user.name')
                            ->label('Customer'),
                        TextEntry::make('payment_gateway')
                            ->label('Payment Gateway')
                            ->placeholder(__('N/A'))
                            ->badge()
                            ->color('info'),
                        TextEntry::make('total')
                            ->money(fn ($record) => $record->currency),
                        TextEntry::make('paid_at')
                            ->label('Payment Date')
                            ->dateTime(),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime(),
                    ])
                    ->columns(2),

                Section::make(__('Customer Details'))
                    ->description(__('Customer information and contact details'))
                    ->icon(Heroicon::UserCircle)
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('user.name')
                                    ->label('Full Name')
                                    ->icon(Heroicon::User)
                                    ->default('—'),
                                TextEntry::make('user.email')
                                    ->label('Email Address')
                                    ->icon(Heroicon::Envelope)
                                    ->copyable()
                                    ->copyMessage('Email copied')
                                    ->default('—'),
                                TextEntry::make('shipping_address.phone')
                                    ->label('Phone Number')
                                    ->icon(Heroicon::Phone)
                                    ->copyable()
                                    ->copyMessage('Phone number copied')
                                    ->default('—'),
                                TextEntry::make('shipping_address.ip_address')
                                    ->label('IP Address')
                                    ->icon(Heroicon::GlobeAlt)
                                    ->copyable()
                                    ->copyMessage('IP address copied')
                                    ->default('—')
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make(__('Status History'))
                    ->description(__('Track all status changes for this order'))
                    ->icon(Heroicon::Clock)
                    ->schema([
                        RepeatableEntry::make('statusHistories')
                            ->label('')
                            ->schema([
                                TextEntry::make('status')
                                    ->badge()
                                    ->label('Status'),
                                TextEntry::make('description')
                                    ->label('Description'),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime()
                                    ->since(),
                            ])
                            ->columns(3)
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make(__('Order Items'))
                    ->description(__('Products and services in this order'))
                    ->icon(Heroicon::ShoppingBag)
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Product Name')
                                    ->weight('semibold')
                                    ->icon(Heroicon::CubeTransparent),
                                TextEntry::make('quantity')
                                    ->label('Quantity')
                                    ->badge()
                                    ->color('info'),
                                TextEntry::make('price')
                                    ->label('Unit Price')
                                    ->money(fn ($record) => $record->order->currency),
                                TextEntry::make('total')
                                    ->label('Total')
                                    ->state(fn ($record) => $record->price * $record->quantity)
                                    ->money(fn ($record) => $record->order->currency)
                                    ->weight('semibold'),
                            ])
                            ->columns(4)
                            ->contained(false),
                    ])
                    ->collapsible()
                    ->collapsed(false),

                Section::make(__('Financial Details'))
                    ->schema([
                        TextEntry::make('subtotal')
                            ->money(fn ($record) => $record->currency),
                        TextEntry::make('discount_total')
                            ->money(fn ($record) => $record->currency),
                        TextEntry::make('tax')
                            ->money(fn ($record) => $record->currency),
                        TextEntry::make('total')
                            ->money(fn ($record) => $record->currency)
                            ->weight('bold'),
                    ])
                    ->columns(4)
                    ->collapsible(),

                // Section::make(__('Billing Address'))
                //     ->schema([
                //         TextEntry::make('billing_address')
                //             ->label('')
                //             ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                //             ->columnSpanFull(),
                //     ])
                //     ->collapsible()
                //     ->collapsed(),

                Section::make(__('Shipping Address'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('shipping_address.first_name')
                                    ->label('First Name')
                                    ->default('—'),
                                TextEntry::make('shipping_address.last_name')
                                    ->label('Last Name')
                                    ->default('—'),
                                TextEntry::make('shipping_address.phone')
                                    ->label('Phone Number')
                                    ->icon(Heroicon::Phone)
                                    ->copyable()
                                    ->default('—')
                                    ->columnSpan(2),
                                TextEntry::make('shipping_address.address')
                                    ->label('Street Address')
                                    ->default('—'),
                                TextEntry::make('shipping_address.property_number')
                                    ->label('Property Number')
                                    ->default('—'),
                                TextEntry::make('shipping_address.area')
                                    ->label('Area')
                                    ->default('—'),
                                TextEntry::make('shipping_address.postal_code')
                                    ->label('Postal Code')
                                    ->default('—'),
                                TextEntry::make('shipping_address.country')
                                    ->label('Country')
                                    ->formatStateUsing(fn ($state) => $state ? strtoupper($state) : '—')
                                    ->badge()
                                    ->color('primary')
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
