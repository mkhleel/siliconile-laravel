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
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-lock-closed';
    }

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
                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('name')

                                    ->required(),

                                Select::make('guard_name')

                                    ->options([
                                        'web' => 'web',
                                        'admin' => 'admin',
                                    ])
                                    ->default('web')
                                    ->required(),

                                Select::make('permissions')
                                    ->columnSpanFull()
                                    ->multiple()

                                    ->relationship(
                                        name: 'permissions',
                                        modifyQueryUsing: fn (Builder $query) => $query->orderBy('name')->orderBy('name'),
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Model $record) => "{$record->name} ({$record->guard_name})")
                                    ->searchable(['name', 'guard_name']) // searchable on both name and guard_name
                                    ->preload(),

                                Select::make(config('permission.column_names.team_foreign_key', 'team_id'))

                                    ->hidden(fn () => ! config('permission.teams', false) || Filament::hasTenancy())
//                                    ->options(
//                                        fn () =>  Modules\Core\Models\Team::class::pluck('name', 'id')
//                                    )
                                    ->dehydrated(fn ($state) => (int) $state <= 0)
                                    ->placeholder(__('Select a Team'))
                                    ->hint(__('Leave blank for a global role')),
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
