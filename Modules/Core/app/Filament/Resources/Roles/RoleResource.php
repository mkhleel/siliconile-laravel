<?php

namespace Modules\Core\Filament\Resources\Roles;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Filament\Resources\Roles\Pages\CreateRole;
use Modules\Core\Filament\Resources\Roles\Pages\EditRole;
use Modules\Core\Filament\Resources\Roles\Pages\ListRoles;
use Modules\Core\Filament\Resources\Roles\Pages\ViewRole;
use Modules\Core\Filament\Resources\Roles\RelationManager\PermissionRelationManager;
use Modules\Core\Filament\Resources\Roles\RelationManager\UserRelationManager;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    public static function getModel(): string
    {
        return config('permission.models.role', Role::class);
    }

    public static function getLabel(): string
    {
        return __('Role');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Access');
    }

    public static function getPluralLabel(): string
    {
        return __('Roles');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Role Details'))
                    ->description(__('Configure the role name and guard'))
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('Role Name'))
                                    ->placeholder(__('e.g., Editor, Manager'))
                                    ->required()
                                    ->maxLength(255),

                                Select::make('guard_name')
                                    ->label(__('Guard'))
                                    ->options([
                                        'web' => 'Web (Users)',
                                        'admin' => 'Admin (Staff)',
                                    ])
                                    ->default('web')
                                    ->native(false)
                                    ->required(),
                            ]),
                    ]),

                Section::make(__('Permissions'))
                    ->description(__('Assign permissions to this role'))
                    ->icon('heroicon-o-key')
                    ->collapsible()
                    ->schema([
                        Select::make('permissions')
                            ->label(__('Assigned Permissions'))
                            ->multiple()
                            ->relationship(
                                name: 'permissions',
                                modifyQueryUsing: fn (Builder $query) => $query->orderBy('name'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} ({$record->guard_name})")
                            ->searchable(['name', 'guard_name'])
                            ->preload()
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Team Assignment'))
                    ->description(__('Assign this role to a specific team (optional)'))
                    ->icon('heroicon-o-user-group')
                    ->hidden(fn () => ! config('permission.teams', false) || Filament::hasTenancy())
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Select::make(config('permission.column_names.team_foreign_key', 'team_id'))
                            ->label(__('Team'))
                            ->dehydrated(fn ($state) => (int) $state <= 0)
                            ->placeholder(__('Select a Team'))
                            ->hint(__('Leave blank for a global role'))
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')

                    ->searchable(),
                TextColumn::make('name')

                    ->searchable(),
                TextColumn::make('permissions_count')
                    ->counts('permissions')

                    ->toggleable(),
                TextColumn::make('guard_name')
                    ->toggleable()

                    ->searchable(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PermissionRelationManager::class,
            UserRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
            'view' => ViewRole::route('/{record}'),
        ];
    }
}
