<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Pages;
use Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Schemas\SpaceResourceSchema;
use Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Tables\SpaceResourceTable;
use Modules\SpaceBooking\Models\SpaceResource;
use UnitEnum;

/**
 * Filament Resource for managing Space Resources (Rooms, Desks, Offices).
 *
 * Trace: SRS-FR-BOOKING-001 (Space Resource Management)
 */
class SpaceResourceResource extends Resource
{
    protected static ?string $model = SpaceResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Space Booking';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Resources';

    protected static ?string $modelLabel = 'Space Resource';

    protected static ?string $pluralModelLabel = 'Space Resources';

    public static function form(Schema $schema): Schema
    {
        return SpaceResourceSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpaceResourceTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Bookings relation manager could be added here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpaceResources::route('/'),
            'create' => Pages\CreateSpaceResource::route('/create'),
            'edit' => Pages\EditSpaceResource::route('/{record}/edit'),
        ];
    }
}
