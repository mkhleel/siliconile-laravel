<?php

namespace Modules\Core\Filament\Resources\Permissions;

use Filament\Actions\Action;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Filament\Resources\Permissions\Pages\CreatePermission;
use Modules\Core\Filament\Resources\Permissions\Pages\EditPermission;
use Modules\Core\Filament\Resources\Permissions\Pages\ListPermissions;
use Modules\Core\Filament\Resources\Permissions\Pages\ViewPermission;
use Modules\Core\Filament\Resources\Permissions\RelationManager\RoleRelationManager;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-lock-closed';
    }

    public static function getModel(): string
    {
        return config('permission.models.permission', Permission::class);
    }

    public static function getLabel(): string
    {
        return __('Permission');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Access');
    }

    public static function getPluralLabel(): string
    {
        return __('Permissions');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')

                                ->required(),
                            Select::make('guard_name')

                                ->options([
                                    'web' => 'web',
                                    'admin' => 'admin',
                                ])
                                ->default('web')
                                ->required(),
                            Select::make('roles')
                                ->multiple()

                                ->relationship(
                                    name: 'roles',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: function (Builder $query) {
                                        if (Filament::hasTenancy()) {
                                            return $query->where(config('permission.column_names.team_foreign_key'), Filament::getTenant());
                                        }

                                        return $query;
                                    }
                                )
                                ->preload(),
                        ]),
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
                TextColumn::make('guard_name')
                    ->toggleable()

                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                Action::make('Attach to roles')
                    ->action(function (Collection $records, array $data): void {
                        Role::whereIn('id', $data['roles'])->each(function (Role $role) use ($records): void {
                            $records->each(fn (Permission $permission) => $role->givePermissionTo($permission));
                        });
                    })
                    ->schema([
                        Select::make('roles')
                            ->multiple()

                            ->options(Role::query()->pluck('name', 'id')->toArray())
                            ->required(),
                    ])->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RoleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
            'create' => CreatePermission::route('/create'),
            'edit' => EditPermission::route('/{record}/edit'),
            'view' => ViewPermission::route('/{record}'),
        ];
    }
}
