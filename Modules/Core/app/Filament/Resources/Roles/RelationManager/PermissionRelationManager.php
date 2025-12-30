<?php

namespace Modules\Core\Filament\Resources\Roles\RelationManager;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\PermissionRegistrar;

class PermissionRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    protected static ?string $recordTitleAttribute = 'name';

    /*
     * Support changing tab title by translations in RelationManager.
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Permissions') ?? (string) str(static::getRelationshipName())
            ->kebab()
            ->replace('-', ' ')
            ->headline();
    }

    protected static function getModelLabel(): string
    {
        return __('Permissions');
    }

    protected static function getPluralModelLabel(): string
    {
        return __('Permissions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name'),
                TextInput::make('guard_name'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            // Support changing table heading by translations.
            ->heading(__('Permissions'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->searchable(),

            ])
            ->filters([

            ])->headerActions([
                AttachAction::make('Attach Permission')->preloadRecordSelect()->after(fn () => app()
                    ->make(PermissionRegistrar::class)
                    ->forgetCachedPermissions()),
            ])->recordActions([
                DetachAction::make()->after(fn () => app()->make(PermissionRegistrar::class)->forgetCachedPermissions()),
            ])->toolbarActions([
                DetachBulkAction::make()->after(fn () => app()->make(PermissionRegistrar::class)->forgetCachedPermissions()),
            ]);
    }
}
