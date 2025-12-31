<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Incubation\Filament\Resources\ApplicationResource\Pages;
use Modules\Incubation\Filament\Resources\ApplicationResource\Schemas\ApplicationSchema;
use Modules\Incubation\Filament\Resources\ApplicationResource\Tables\ApplicationTable;
use Modules\Incubation\Models\Application;
use UnitEnum;

/**
 * Filament Resource for managing Applications (Selection Pipeline).
 */
class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Incubation';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Applications';

    protected static ?string $modelLabel = 'Application';

    protected static ?string $pluralModelLabel = 'Applications';

    public static function form(Schema $schema): Schema
    {
        return ApplicationSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApplicationTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers for mentorship sessions, milestones, etc.
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'kanban' => Pages\ApplicationsKanban::route('/kanban'),
            'create' => Pages\CreateApplication::route('/create'),
            'view' => Pages\ViewApplication::route('/{record}'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'submitted')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
