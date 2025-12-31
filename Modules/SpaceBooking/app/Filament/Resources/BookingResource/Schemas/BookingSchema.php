<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\BookingResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Modules\Membership\Models\Member;
use Modules\SpaceBooking\Enums\BookingStatus;
use Modules\SpaceBooking\Enums\PaymentStatus;
use Modules\SpaceBooking\Models\SpaceResource;

/**
 * Schema configuration for Booking forms.
 */
class BookingSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'lg' => 3,
            ])
            ->components([
                // Main booking details - Left side (2 columns)
                Section::make('Booking Details')
                    ->description('Select resource, booker, and time slot')
                    ->icon('heroicon-o-calendar')
                    ->columnSpan([
                        'sm' => 1,
                        'lg' => 2,
                    ])
                    ->schema(self::getBookingDetailsSchema())
                    ->collapsible(),

                // Status sidebar - Right side (1 column)
                Section::make('Status & Payment')
                    ->description('Booking and payment status')
                    ->icon('heroicon-o-check-badge')
                    ->columnSpan([
                        'sm' => 1,
                        'lg' => 1,
                    ])
                    ->schema(self::getStatusSchema())
                    ->collapsible(),

                // Pricing section - Full width
                Section::make('Pricing & Billing')
                    ->description('Configure pricing, discounts, and credits')
                    ->icon('heroicon-o-currency-dollar')
                    ->columnSpan('full')
                    ->schema(self::getPricingSchema())
                    ->collapsible()
                    ->collapsed(),

                // Notes section - Full width
                Section::make('Additional Information')
                    ->description('Notes and remarks about this booking')
                    ->icon('heroicon-o-document-text')
                    ->columnSpan('full')
                    ->schema(self::getNotesSchema())
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    private static function getBookingDetailsSchema(): array
    {
        return [
            // Resource selection
            Forms\Components\Select::make('space_resource_id')
                ->label('Space Resource')
                ->options(SpaceResource::active()->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('unit_price', null))
                ->helperText('Select the room, desk, or meeting space to book')
                ->columnSpanFull(),

            // Booker information
            Grid::make(2)
                ->schema([
                    Forms\Components\Select::make('bookable_type')
                        ->label('Booker Type')
                        ->options([
                            'App\\Models\\User' => 'User (Guest)',
                            'Modules\\Membership\\Models\\Member' => 'Member',
                        ])
                        ->required()
                        ->live()
                        ->native(false)
                        ->columnSpan(1),

                    Forms\Components\Select::make('bookable_id')
                        ->label('Booker')
                        ->options(function (Get $get) {
                            $type = $get('bookable_type');
                            if ($type === 'App\\Models\\User') {
                                return \App\Models\User::pluck('name', 'id');
                            }
                            if ($type === 'Modules\\Membership\\Models\\Member') {
                                return Member::with('user')
                                    ->get()
                                    ->mapWithKeys(fn ($m) => [$m->id => $m->user?->name ?? "Member #{$m->id}"]);
                            }
                            return [];
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => filled($get('bookable_type')))
                        ->columnSpan(1),
                ]),

            // Time slot
            Grid::make(2)
                ->schema([
                    Forms\Components\DateTimePicker::make('start_time')
                        ->label('Start Time')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15)
                        ->live()
                        ->native(false)
                        ->columnSpan(1),

                    Forms\Components\DateTimePicker::make('end_time')
                        ->label('End Time')
                        ->required()
                        ->seconds(false)
                        ->minutesStep(15)
                        ->after('start_time')
                        ->native(false)
                        ->columnSpan(1),
                ]),

            // Attendees
            Forms\Components\TextInput::make('attendees_count')
                ->label('Number of Attendees')
                ->numeric()
                ->minValue(1)
                ->placeholder('Optional')
                ->helperText('Leave empty if not applicable')
                ->columnSpanFull(),
        ];
    }

    private static function getStatusSchema(): array
    {
        return [
            Forms\Components\Select::make('status')
                ->label('Booking Status')
                ->options(BookingStatus::options())
                ->required()
                ->native(false)
                ->default(BookingStatus::PENDING->value)
                ->columnSpanFull(),

            Forms\Components\Select::make('payment_status')
                ->label('Payment Status')
                ->options(PaymentStatus::options())
                ->required()
                ->native(false)
                ->default(PaymentStatus::UNPAID->value)
                ->columnSpanFull(),

            Forms\Components\Placeholder::make('booking_code')
                ->label('Booking Code')
                ->content(fn ($record) => $record?->booking_code ?? 'Will be auto-generated')
                ->visibleOn('edit')
                ->columnSpanFull(),

            Forms\Components\Placeholder::make('check_in_status')
                ->label('Check-in Status')
                ->content(fn ($record) => $record?->checked_in_at 
                    ? 'Checked in at ' . $record->checked_in_at->format('M d, Y H:i')
                    : 'Not checked in yet'
                )
                ->visibleOn('edit')
                ->columnSpanFull(),

            Forms\Components\Placeholder::make('check_out_status')
                ->label('Check-out Status')
                ->content(fn ($record) => $record?->checked_out_at 
                    ? 'Checked out at ' . $record->checked_out_at->format('M d, Y H:i')
                    : 'Not checked out yet'
                )
                ->visibleOn('edit')
                ->columnSpanFull(),
        ];
    }

    private static function getPricingSchema(): array
    {
        return [
            Grid::make([
                'sm' => 1,
                'md' => 2,
                'lg' => 4,
            ])
                ->schema([
                    // Base pricing
                    Forms\Components\TextInput::make('unit_price')
                        ->label('Unit Price')
                        ->numeric()
                        ->prefix('SDG')
                        ->required()
                        ->columnSpan(1),

                    Forms\Components\Select::make('price_unit')
                        ->label('Price Unit')
                        ->options(\Modules\SpaceBooking\Enums\PriceUnit::options())
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->columnSpan(1),

                    Forms\Components\Select::make('currency')
                        ->label('Currency')
                        ->options([
                            'SDG' => 'SDG (Sudanese Pound)',
                            'USD' => 'USD (US Dollar)',
                            'EUR' => 'EUR (Euro)',
                        ])
                        ->default('SDG')
                        ->native(false)
                        ->columnSpan(1),
                ]),

            Grid::make([
                'sm' => 1,
                'md' => 3,
            ])
                ->schema([
                    // Discounts and credits
                    Forms\Components\TextInput::make('discount_amount')
                        ->label('Discount Amount')
                        ->numeric()
                        ->prefix('SDG')
                        ->default(0)
                        ->helperText('Manual discount applied')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('credits_used')
                        ->label('Credits Used')
                        ->numeric()
                        ->default(0)
                        ->helperText('Member plan credits deducted')
                        ->columnSpan(1),

                    Forms\Components\TextInput::make('total_price')
                        ->label('Total Price')
                        ->numeric()
                        ->prefix('SDG')
                        ->required()
                        ->helperText('Final amount to be paid')
                        ->columnSpan(1),
                ]),
        ];
    }

    private static function getNotesSchema(): array
    {
        return [
            Grid::make([
                'sm' => 1,
                'lg' => 2,
            ])
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Customer Notes')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Notes from the customer')
                        ->columnSpan(1),

                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Admin Notes')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Internal notes (not visible to customer)')
                        ->columnSpan(1),
                ]),

            Forms\Components\Textarea::make('cancellation_reason')
                ->label('Cancellation Reason')
                ->rows(3)
                ->maxLength(500)
                ->helperText('Reason for cancellation (required when status is Cancelled)')
                ->visible(fn (Get $get): bool => $get('status') === BookingStatus::CANCELLED->value)
                ->columnSpanFull(),
        ];
    }
}
