<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Resources\Users\Schemas;

use App\Enums\UserType;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Modules\Core\Models\Localization\Area;
use Modules\Core\Models\Localization\City;
use Modules\Core\Models\Localization\Country;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Personal Information'))
                    ->description(__('Basic user profile details'))
                    ->icon('heroicon-o-user')
                    ->aside()
                    ->schema([
                        Group::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('username')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email', fn (?User $record) => $record),
                                TextInput::make('phone')
                                    ->required()
                                    ->label(__('Contact No.'))
                                    ->tel()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->label(__('User Type'))
                                    ->options(UserType::class)
                                    ->native(false),
                                Select::make('gender')
                                    ->label(__('Gender'))
                                    ->options(['male' => __('Male'), 'female' => __('Female')])
                                    ->native(false)
                                    ->required(),
                            ]),
                        TextInput::make('about_me')
                            ->label(__('About Me'))
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Authentication'))
                    ->description(__('Password and security settings'))
                    ->icon('heroicon-o-lock-closed')
                    ->aside()
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->rule(Password::default())
                            ->helperText(__('Leave empty to keep current password')),
                    ]),

                Section::make(__('Location'))
                    ->description(__('Address and location information'))
                    ->icon('heroicon-o-map-pin')
                    ->aside()
                    ->collapsible()
                    ->schema([
                        Group::make()
                            ->columns(3)
                            ->schema([
                                Select::make('country')
                                    ->label(__('Country'))
                                    ->live()
                                    ->dehydrated()
                                    ->options(Country::all()->pluck('name', 'id')->toArray())
                                    ->afterStateUpdated(fn (callable $set) => $set('state_id', null))
                                    ->searchable()
                                    ->native(false),

                                Select::make('state_id')
                                    ->label(__('State/Province'))
                                    ->live()
                                    ->dehydrated()
                                    ->options(fn (callable $get) => City::whereCountryId($get('country'))?->pluck('name', 'id')->toArray() ?? [])
                                    ->disabled(fn (callable $get) => ! $get('country'))
                                    ->searchable()
                                    ->native(false),

                                Select::make('city_id')
                                    ->label(__('City/Area'))
                                    ->live()
                                    ->options(fn (callable $get) => Area::whereCityId($get('state_id'))?->pluck('name', 'id')->toArray() ?? [])
                                    ->disabled(fn (callable $get) => ! $get('state_id'))
                                    ->searchable()
                                    ->native(false),
                            ]),
                    ]),
            ]);
    }
}
