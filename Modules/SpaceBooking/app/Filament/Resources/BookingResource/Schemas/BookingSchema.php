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
            ->components([
                Grid::make(3)
                    ->schema([
                        // Main booking details - 2 columns
                        Section::make('Booking Details')
                            ->schema(self::getBookingDetailsSchema())
                            ->columnSpan(2),

                        // Status sidebar - 1 column
                        Section::make('Status & Payment')
                            ->schema(self::getStatusSchema())
                            ->columnSpan(1),
                    ]),

                Section::make('Pricing')
                    ->schema(self::getPricingSchema())
                    ->columns(4)
                    ->collapsible(),

                Section::make('Notes')
                    ->schema(self::getNotesSchema())
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    private static function getBookingDetailsSchema(): array
    {
        return [
            Forms\Components\Select::make('space_resource_id')
                ->label('Resource')
                ->options(SpaceResource::active()->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('unit_price', null)),

            Forms\Components\Select::make('bookable_type')
                ->label('Booker Type')
                ->options([
                    'App\\Models\\User' => 'User (Guest)',
                    'Modules\\Membership\\Models\\Member' => 'Member',
                ])
                ->required()
                ->live()
                ->native(false),

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
                ->visible(fn (Get $get): bool => filled($get('bookable_type'))),

            Forms\Components\DateTimePicker::make('start_time')
                ->label('Start Time')
                ->required()
                ->seconds(false)
                ->minutesStep(15)
                ->live()
                ->native(false),

            Forms\Components\DateTimePicker::make('end_time')
                ->label('End Time')
                ->required()
                ->seconds(false)
                ->minutesStep(15)
                ->after('start_time')
                ->native(false),

            Forms\Components\TextInput::make('attendees_count')
                ->label('Number of Attendees')
                ->numeric()
                ->minValue(1)
                ->placeholder('Optional'),
        ];
    }

    private static function getStatusSchema(): array
    {
        return [
            Forms\Components\Select::make('status')
                ->options(BookingStatus::options())
                ->required()
                ->native(false)
                ->default(BookingStatus::PENDING->value),

            Forms\Components\Select::make('payment_status')
                ->options(PaymentStatus::options())
                ->required()
                ->native(false)
                ->default(PaymentStatus::UNPAID->value),

            Forms\Components\Placeholder::make('booking_code')
                ->label('Booking Code')
                ->content(fn ($record) => $record?->booking_code ?? 'Will be generated')
                ->visibleOn('edit'),

            Forms\Components\DateTimePicker::make('checked_in_at')
                ->label('Checked In')
                ->seconds(false)
                ->disabled()
                ->visibleOn('edit'),

            Forms\Components\DateTimePicker::make('checked_out_at')
                ->label('Checked Out')
                ->seconds(false)
                ->disabled()
                ->visibleOn('edit'),
        ];
    }

    private static function getPricingSchema(): array
    {
        return [
            Forms\Components\TextInput::make('unit_price')
                ->numeric()
                ->prefix('SDG')
                ->required(),

            Forms\Components\Select::make('price_unit')
                ->options(\Modules\SpaceBooking\Enums\PriceUnit::options())
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('quantity')
                ->numeric()
                ->required()
                ->minValue(1),

            Forms\Components\TextInput::make('discount_amount')
                ->numeric()
                ->prefix('SDG')
                ->default(0),

            Forms\Components\TextInput::make('credits_used')
                ->numeric()
                ->default(0)
                ->helperText('Credits deducted from member plan'),

            Forms\Components\TextInput::make('total_price')
                ->numeric()
                ->prefix('SDG')
                ->required(),

            Forms\Components\Select::make('currency')
                ->options([
                    'SDG' => 'SDG',
                    'USD' => 'USD',
                    'EUR' => 'EUR',
                ])
                ->default('SDG')
                ->native(false),
        ];
    }

    private static function getNotesSchema(): array
    {
        return [
            Forms\Components\Textarea::make('notes')
                ->label('Customer Notes')
                ->rows(3)
                ->maxLength(1000),

            Forms\Components\Textarea::make('admin_notes')
                ->label('Admin Notes')
                ->rows(3)
                ->maxLength(1000),

            Forms\Components\Textarea::make('cancellation_reason')
                ->label('Cancellation Reason')
                ->rows(2)
                ->maxLength(500)
                ->visible(fn (Get $get): bool => $get('status') === BookingStatus::CANCELLED->value),
        ];
    }
}
