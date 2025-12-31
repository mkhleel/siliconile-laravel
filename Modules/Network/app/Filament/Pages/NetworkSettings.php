<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Modules\Network\Services\MikrotikService;
use Modules\Network\Settings\RouterSettings;
use UnitEnum;

/**
 * Filament settings page for Mikrotik Router configuration.
 */
class NetworkSettings extends SettingsPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wifi';

    protected static string $settings = RouterSettings::class;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('Network / WiFi');
    }

    public static function getModelLabel(): string
    {
        return __('Router Settings');
    }

    public function getTitle(): string
    {
        return __('Mikrotik Router Settings');
    }

    public function getSubheading(): ?string
    {
        return __('Configure connection to your Mikrotik router for hotspot user management.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_connection')
                ->label(__('Test Connection'))
                ->icon('heroicon-o-signal')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading(__('Test Router Connection'))
                ->modalDescription(__('This will attempt to connect to the Mikrotik router with the saved settings.'))
                ->action(function () {
                    /** @var MikrotikService $service */
                    $service = app(MikrotikService::class);

                    $result = $service->testConnection();

                    if ($result['success']) {
                        Notification::make()
                            ->title(__('Connection Successful'))
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('Connection Failed'))
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('sync_all')
                ->label(__('Sync All Members'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('Sync All Members'))
                ->modalDescription(__('This will sync all members to the Mikrotik router. Active members will be enabled, expired members will be disabled.'))
                ->action(function () {
                    // Dispatch the sync command via Artisan
                    Artisan::queue('network:sync-all', ['--sync-queue' => true]);

                    Notification::make()
                        ->title(__('Sync Started'))
                        ->body(__('Member sync has been queued. Check the logs for progress.'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Module Status'))
                    ->description(__('Enable or disable the Network module'))
                    ->aside()
                    ->schema([
                        Toggle::make('enabled')
                            ->label(__('Enable Network Module'))
                            ->helperText(__('When enabled, the system will automatically sync member WiFi access based on subscription status.'))
                            ->required(),
                    ]),

                Section::make(__('Router Connection'))
                    ->description(__('Mikrotik RouterOS API connection settings'))
                    ->aside()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('ip_address')
                                    ->label(__('Router IP Address'))
                                    ->placeholder('192.168.88.1')
                                    ->required()
                                    ->maxLength(45)
                                    ->rules(['ip']),

                                TextInput::make('port')
                                    ->label(__('API Port'))
                                    ->numeric()
                                    ->default(8728)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->helperText(__('Default: 8728, SSL: 8729')),

                                TextInput::make('admin_username')
                                    ->label(__('Admin Username'))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('admin_password')
                                    ->label(__('Admin Password'))
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                    ->dehydrated(fn ($state) => filled($state)),

                                TextInput::make('connection_timeout')
                                    ->label(__('Connection Timeout'))
                                    ->numeric()
                                    ->default(10)
                                    ->suffix(__('seconds'))
                                    ->minValue(5)
                                    ->maxValue(60),

                                Toggle::make('use_ssl')
                                    ->label(__('Use SSL'))
                                    ->helperText(__('Enable for secure API connection (port 8729)')),
                            ]),
                    ]),

                Section::make(__('Hotspot Configuration'))
                    ->description(__('Default hotspot settings for new users'))
                    ->aside()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('hotspot_profile')
                                    ->label(__('Default Profile'))
                                    ->placeholder('default')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText(__('Hotspot user profile to assign to new users')),

                                TextInput::make('hotspot_server')
                                    ->label(__('Hotspot Server'))
                                    ->placeholder('hotspot1')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText(__('Name of the hotspot server on the router')),
                            ]),
                    ]),

                Section::make(__('User Settings'))
                    ->description(__('Configure how member usernames and passwords are generated'))
                    ->aside()
                    ->schema([
                        Select::make('username_format')
                            ->label(__('Username Format'))
                            ->options([
                                '{phone}' => __('Phone Number'),
                                '{email}' => __('Email Address'),
                                '{member_code}' => __('Member Code'),
                                '{member_id}' => __('Member ID'),
                            ])
                            ->default('{phone}')
                            ->required()
                            ->helperText(__('Choose which member field to use as the hotspot username')),

                        Toggle::make('auto_generate_password')
                            ->label(__('Auto-Generate Passwords'))
                            ->helperText(__('Automatically generate random passwords for new users')),

                        TextInput::make('password_length')
                            ->label(__('Password Length'))
                            ->numeric()
                            ->default(8)
                            ->minValue(6)
                            ->maxValue(32)
                            ->visible(fn ($get) => $get('auto_generate_password')),
                    ]),
            ]);
    }

    /**
     * Mutate data before saving to encrypt password.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Password encryption is handled by dehydrateStateUsing
        return $data;
    }
}
