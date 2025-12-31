<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Incubation\Filament\Resources\CohortResource\Pages;
use Modules\Incubation\Filament\Resources\CohortResource\Schemas\CohortSchema;
use Modules\Incubation\Filament\Resources\CohortResource\Tables\CohortTable;
use Modules\Incubation\Models\Cohort;
use UnitEnum;

/**
 * Filament Resource for managing Cohorts (Program Cycles).
 */
class CohortResource extends Resource
{
    protected static ?string $model = Cohort::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Incubation';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Cohorts';

    protected static ?string $modelLabel = 'Cohort';

    protected static ?string $pluralModelLabel = 'Cohorts';

    public static function form(Schema $schema): Schema
    {
        return CohortSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CohortTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers to be added later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCohorts::route('/'),
            'create' => Pages\CreateCohort::route('/create'),
            'view' => Pages\ViewCohort::route('/{record}'),
            'edit' => Pages\EditCohort::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'open_for_applications')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}
