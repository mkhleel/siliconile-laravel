<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\SpaceBooking\Enums\ResourceType;

/**
 * Schema configuration for SpaceResource forms.
 */
class SpaceResourceSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Resource Details')
                    ->tabs([
                        Tabs\Tab::make('Basic Info')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('General Information')
                                    ->schema(self::getBasicInfoSchema())
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Pricing')
                            ->icon(Heroicon::OutlinedCurrencyDollar)
                            ->schema([
                                Section::make('Pricing Configuration')
                                    ->schema(self::getPricingSchema())
                                    ->columns(2),

                                Section::make('Plan-Based Pricing Rules')
                                    ->schema(self::getPricingRulesSchema())
                                    ->description('Define custom pricing for specific membership plans.')
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Scheduling')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Availability Settings')
                                    ->schema(self::getSchedulingSchema())
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('Amenities')
                            ->icon(Heroicon::OutlinedSparkles)
                            ->schema([
                                Section::make('Resource Amenities')
                                    ->schema(self::getAmenitiesSchema()),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private static function getBasicInfoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Set $set, ?string $state) {
                    $set('slug', \Str::slug($state ?? ''));
                }),

            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->alphaDash(),

            Forms\Components\Select::make('resource_type')
                ->options(ResourceType::options())
                ->required()
                ->live()
                ->native(false),

            Forms\Components\TextInput::make('capacity')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required()
                ->suffix('people'),

            Forms\Components\TextInput::make('location')
                ->maxLength(255)
                ->placeholder('e.g., Floor 2, Building A'),

            Forms\Components\FileUpload::make('image')
                ->image()
                ->directory('space-resources')
                ->imageEditor()
                ->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->maxLength(1000)
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true)
                ->helperText('Inactive resources cannot be booked.'),

            Forms\Components\Toggle::make('requires_approval')
                ->label('Requires Approval')
                ->default(false)
                ->helperText('Bookings will be pending until admin approval.'),

            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0)
                ->minValue(0),
        ];
    }

    private static function getPricingSchema(): array
    {
        return [
            Forms\Components\TextInput::make('hourly_rate')
                ->numeric()
                ->prefix('EGP')
                ->placeholder('0.00')
                ->visible(fn (Get $get): bool => $get('resource_type') === ResourceType::MEETING_ROOM->value),

            Forms\Components\TextInput::make('daily_rate')
                ->numeric()
                ->prefix('EGP')
                ->placeholder('0.00')
                ->visible(fn (Get $get): bool => in_array($get('resource_type'), [
                    ResourceType::HOT_DESK->value,
                    ResourceType::PRIVATE_OFFICE->value,
                ])),

            Forms\Components\TextInput::make('monthly_rate')
                ->numeric()
                ->prefix('EGP')
                ->placeholder('0.00')
                ->visible(fn (Get $get): bool => $get('resource_type') === ResourceType::PRIVATE_OFFICE->value),

            Forms\Components\Select::make('currency')
                ->options([
                    'EGP' => 'EGP - Sudanese Pound',
                    'USD' => 'USD - US Dollar',
                    'EUR' => 'EUR - Euro',
                ])
                ->default('EGP')
                ->native(false),
        ];
    }

    private static function getPricingRulesSchema(): array
    {
        return [
            Forms\Components\Repeater::make('pricing_rules')
                ->schema([
                    Forms\Components\Select::make('plan_id')
                        ->label('Membership Plan')
                        ->options(fn () => \Modules\Membership\Models\Plan::pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    Forms\Components\TextInput::make('discount_percent')
                        ->label('Discount %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'),

                    Forms\Components\TextInput::make('free_hours_monthly')
                        ->label('Free Hours/Month')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('For meeting rooms'),
                ])
                ->columns(3)
                ->defaultItems(0)
                ->addActionLabel('Add Pricing Rule')
                ->columnSpanFull(),
        ];
    }

    private static function getSchedulingSchema(): array
    {
        return [
            Forms\Components\TimePicker::make('available_from')
                ->label('Available From')
                ->seconds(false)
                ->helperText('Leave empty for 24/7 availability'),

            Forms\Components\TimePicker::make('available_until')
                ->label('Available Until')
                ->seconds(false)
                ->after('available_from'),

            Forms\Components\TextInput::make('buffer_minutes')
                ->label('Buffer Time')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->suffix('minutes')
                ->helperText('Time needed between bookings for cleaning/prep'),

            Forms\Components\TextInput::make('min_booking_minutes')
                ->label('Minimum Booking')
                ->numeric()
                ->default(30)
                ->minValue(1)
                ->suffix('minutes'),

            Forms\Components\TextInput::make('max_booking_minutes')
                ->label('Maximum Booking')
                ->numeric()
                ->minValue(1)
                ->suffix('minutes')
                ->helperText('Leave empty for no limit'),
        ];
    }

    private static function getAmenitiesSchema(): array
    {
        return [
            Forms\Components\CheckboxList::make('amenities')
                ->relationship('amenities', 'name')
                ->columns(3)
                ->searchable()
                ->bulkToggleable()
                ->helperText('Select amenities available in this resource'),
        ];
    }
}
