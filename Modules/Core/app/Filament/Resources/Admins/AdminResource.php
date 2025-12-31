<?php

namespace Modules\Core\Filament\Resources\Admins;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Modules\Core\Filament\Resources\Admins\Pages\CreateAdmin;
use Modules\Core\Filament\Resources\Admins\Pages\EditAdmin;
use Modules\Core\Filament\Resources\Admins\Pages\ListAdmins;
use Modules\Core\Models\Admin;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::RectangleStack;

    public static function getPluralLabel(): ?string
    {
        return __('Admins');
    }

    public static function getLabel(): ?string
    {
        return __('Admin');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Access');
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ListAdmins::class,
            CreateAdmin::class,
            EditAdmin::class,
        ]);
    }

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Personal Information'))
                    ->description(__('Basic admin profile details'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        Group::make()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('Full Name'))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('username')
                                    ->label(__('Username'))
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(Admin::class, 'username', fn (?Admin $record) => $record),
                                TextInput::make('email')
                                    ->label(__('Email Address'))
                                    ->email()
                                    ->required()
                                    ->unique(Admin::class, 'email', fn (?Admin $record) => $record)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Section::make(__('Authentication'))
                    ->description(__('Password and security settings'))
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->rule(Password::default())
                            ->helperText(__('Leave empty to keep current password')),
                    ]),

                Section::make(__('Access Control'))
                    ->description(__('Assign roles to this admin'))
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Select::make('roles')
                            ->label(__('Assigned Roles'))
                            ->multiple()
                            ->relationship('roles', 'name', modifyQueryUsing: fn (Builder $query): Builder => $query->where('guard_name', Filament::getAuthGuard()))
                            ->preload()
                            ->searchable()
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('roles.name'),
                TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('changePassword')
                    ->action(function (Admin $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['new_password']),
                        ]);
                        Notification::make()->title('Password changed successfully.')->sendToDatabase($record);
                    })
                    ->schema([
                        TextInput::make('new_password')
                            ->password()

                            ->required()
                            ->rule(Password::default()),
                        TextInput::make('new_password_confirmation')
                            ->password()
                            ->label('Confirm New Password')
                            ->rule('required', fn ($get) => (bool) $get('new_password'))
                            ->same('new_password'),
                    ])
                    ->icon(Heroicon::OutlinedKey),
                Action::make('deactivate')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedTrash)
                    ->action(fn (Admin $record) => $record->delete()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}
