<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Membership\Filament\Resources\PlanResource\Pages;
use Modules\Membership\Filament\Resources\PlanResource\Schemas\PlanSchema;
use Modules\Membership\Filament\Resources\PlanResource\Tables\PlanTable;
use Modules\Membership\Models\Plan;
use UnitEnum;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Membership';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PlanSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanTable::configure($table);
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'view' => Pages\ViewPlan::route('/{record}'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
