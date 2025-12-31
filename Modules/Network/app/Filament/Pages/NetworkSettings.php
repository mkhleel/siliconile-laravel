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
use Filament\Support\Icons\Heroicon;
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
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedWifi;

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
                ->icon(Heroicon::OutlinedSignal)
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
                ->icon(Heroicon::OutlinedArrowPath)
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
            ->columns([
                'sm' => 1,
                'lg' => 2,
            ])
            ->components([
                // Module Status Section (Full Width)
                Section::make(__('Module Status'))
                    ->description(__('Control the Network module functionality'))
                    ->icon(Heroicon::OutlinedPower)
                    ->columnSpan('full')
                    ->schema([
                        Toggle::make('enabled')
                            ->label(__('Enable Network Module'))
                            ->helperText(__('When enabled, the system will automatically sync member WiFi access based on subscription status.'))
                            ->inline(false)
                            ->required(),
                    ])
                    ->collapsible(),

                // Router Connection Section (Left Column)
                Section::make(__('Router Connection'))
                    ->description(__('Mikrotik RouterOS API connection settings'))
                    ->icon(Heroicon::OutlinedServerStack)
                    ->columnSpan([
                        'sm' => 2,
                        'lg' => 1,
                    ])
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('ip_address')
                                    ->label(__('IP Address'))
                                    ->placeholder('192.168.88.1')
                                    ->required()
                                    ->maxLength(45)
                                    ->rules(['ip'])
                                    ->columnSpan(1),

                                TextInput::make('port')
                                    ->label(__('API Port'))
                                    ->numeric()
                                    ->default(8728)
                                    ->required()
                                    ->minValue(1)
                                    ->maxValue(65535)
                                    ->hint(__('Default: 8728, SSL: 8729'))
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('admin_username')
                                    ->label(__('Username'))
                                    ->required()
                                    ->maxLength(255)
                                    ->autocomplete('off')
                                    ->columnSpan(1),

                                TextInput::make('admin_password')
                                    ->label(__('Password'))
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->maxLength(255)
                                    ->autocomplete('new-password')
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Crypt::encryptString($state) : null)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('connection_timeout')
                                    ->label(__('Timeout'))
                                    ->numeric()
                                    ->default(10)
                                    ->suffix(__('seconds'))
                                    ->minValue(5)
                                    ->maxValue(60)
                                    ->columnSpan(1),

                                Toggle::make('use_ssl')
                                    ->label(__('Use SSL'))
                                    ->helperText(__('Enable for port 8729'))
                                    ->inline(false)
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible(),

                // Hotspot Configuration Section (Right Column)
                Section::make(__('Hotspot Configuration'))
                    ->description(__('Default settings for hotspot users'))
                    ->icon(Heroicon::OutlinedWifi)
                    ->columnSpan([
                        'sm' => 2,
                        'lg' => 1,
                    ])
                    ->schema([
                        TextInput::make('hotspot_profile')
                            ->label(__('Default Profile'))
                            ->placeholder('default')
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('Hotspot user profile name from router'))
                            ->columnSpanFull(),

                        TextInput::make('hotspot_server')
                            ->label(__('Hotspot Server'))
                            ->placeholder('hotspot1')
                            ->required()
                            ->maxLength(255)
                            ->helperText(__('Hotspot server name from router'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // User Generation Settings (Full Width)
                Section::make(__('Username & Password Generation'))
                    ->description(__('Configure automatic credential generation'))
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->columnSpan('full')
                    ->schema([
                        Grid::make([
                            'sm' => 1,
                            'lg' => 3,
                        ])
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
                                    ->native(false)
                                    ->helperText(__('Field to use as hotspot username'))
                                    ->columnSpan([
                                        'sm' => 1,
                                        'lg' => 1,
                                    ]),

                                Toggle::make('auto_generate_password')
                                    ->label(__('Auto-Generate Passwords'))
                                    ->helperText(__('Generate random passwords automatically'))
                                    ->inline(false)
                                    ->live()
                                    ->columnSpan([
                                        'sm' => 1,
                                        'lg' => 1,
                                    ]),

                                TextInput::make('password_length')
                                    ->label(__('Password Length'))
                                    ->numeric()
                                    ->default(8)
                                    ->minValue(6)
                                    ->maxValue(32)
                                    ->helperText(__('Characters in generated passwords'))
                                    ->visible(fn ($get) => $get('auto_generate_password'))
                                    ->columnSpan([
                                        'sm' => 1,
                                        'lg' => 1,
                                    ]),
                            ]),
                    ])
                    ->collapsible(),
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
