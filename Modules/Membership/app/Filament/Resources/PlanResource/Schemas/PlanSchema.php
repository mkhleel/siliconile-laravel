<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\PlanResource\Schemas;

use App\Enums\PlanType;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PlanSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->afterStateUpdated(function ($set, ?string $state) {
                                if (filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(PlanType::class)
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Duration & Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('duration_days')
                            ->label('Duration (days)')
                            ->numeric()
                            ->required()
                            ->minValue(1),

                        Forms\Components\TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->required()
                            ->prefix(fn ($get) => $get('currency') ?? 'EGP')
                            ->step('0.01')
                            ->minValue(0),

                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->maxLength(3)
                            ->default('EGP')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Features')
                    ->schema([
                        Forms\Components\Toggle::make('wifi_access')
                            ->label('WiFi Access')
                            ->default(true),

                        Forms\Components\Toggle::make('meeting_room_access')
                            ->label('Meeting Room Access')
                            ->default(false),

                        Forms\Components\TextInput::make('meeting_hours_included')
                            ->label('Meeting Hours Included')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\Toggle::make('private_desk')
                            ->label('Private Desk')
                            ->default(false),

                        Forms\Components\Toggle::make('locker_access')
                            ->label('Locker Access')
                            ->default(false),

                        Forms\Components\TextInput::make('guest_passes')
                            ->label('Guest Passes')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(3),

                Section::make('Availability & Display')
                    ->schema([
                        Section::make('Capacity')
                            ->schema([
                                Forms\Components\TextInput::make('max_members')
                                    ->label('Max Members')
                                    ->numeric()
                                    ->minValue(1)
                                    ->helperText('Leave empty for unlimited'),

                                Forms\Components\TextInput::make('current_members')
                                    ->label('Current Members')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(2),

                        Section::make('Visibility')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),

                                Forms\Components\Toggle::make('is_featured')
                                    ->label('Featured')
                                    ->default(false),

                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Sort Order')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ])
                            ->columns(3),
                    ])
                    ->columns(2),
            ]);
    }
}
