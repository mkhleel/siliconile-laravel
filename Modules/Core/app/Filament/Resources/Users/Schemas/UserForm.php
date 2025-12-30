<?php

namespace Modules\Core\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Modules\Core\Models\Localization\Area;
use Modules\Core\Models\Localization\City;
use Modules\Core\Models\Localization\Country;
use Modules\Institute\Concerns\UserTypes;
use Modules\Institute\Models\Institute;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->label(__('Personal Information'))
                    ->aside()
                    ->schema([
                        Group::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('username')
                                    ->required(),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email', fn (?User $record) => $record),
                                Select::make('type')->options(UserTypes::class),
                                TextInput::make('phone')
                                    ->required()
                                    ->label('Contact No.')
                                    ->tel()
                                    ->maxLength(255),
                                Select::make('institute_id')
                                    ->placeholder('Not associated with any institute')
                                    ->options(Institute::pluck('name', 'id')),
                            ]),
                    ]),
                Section::make('Authentication')
                    ->aside()
                    ->schema([
                        Group::make()
                            ->columns(2)

                            // ->help('Account Details')
                            ->schema([
                                TextInput::make('password')
                                    ->password()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->maxLength(255)
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->rule(Password::default()),
                            ]),
                    ]),
                Section::make('Address')
                    ->aside()
                    ->schema([
                        Group::make()
                            ->columns(3)
                            // ->label('Account Details')
                            ->schema([
                                Select::make('country')
                                    ->reactive()
                                    ->dehydrated()
                                    ->options(Country::all()->pluck('name', 'id')->toArray())
                                    ->afterStateUpdated(fn (callable $set) => $set('parent_id', null))
                                    ->searchable(),

                                Select::make('state_id')
                                    ->reactive()
                                    ->dehydrated()
                                    ->options(fn (callable $get) => City::whereCountryId($get('country'))?->pluck('name', 'id')->toArray())
                                    ->disabled(fn (callable $get) => ! $get('country')),

                                Select::make('city_id')
                                    ->reactive()
                                    ->options(fn (callable $get) => Area::whereCityId($get('state_id'))?->pluck('name', 'id')->toArray())
                                    ->disabled(fn (callable $get) => ! $get('state_id')),
                                TextInput::make('about_me')
                                    ->columnSpan(2)
                                    ->required(),
                                Select::make('gender')
                                    ->options(['male' => __('Male'), 'female' => __('Female')])
                                    ->columnSpan(2)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
