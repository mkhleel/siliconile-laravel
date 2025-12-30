<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\SpaceBooking\Filament\Resources\BookingResource\Pages;
use Modules\SpaceBooking\Filament\Resources\BookingResource\Schemas\BookingSchema;
use Modules\SpaceBooking\Filament\Resources\BookingResource\Tables\BookingTable;
use Modules\SpaceBooking\Models\Booking;
use UnitEnum;

/**
 * Filament Resource for managing Bookings.
 *
 * Trace: SRS-FR-BOOKING-002 (Booking Management)
 */
class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Space Booking';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Bookings';

    protected static ?string $modelLabel = 'Booking';

    protected static ?string $pluralModelLabel = 'Bookings';

    public static function form(Schema $schema): Schema
    {
        return BookingSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
            'calendar' => Pages\BookingCalendar::route('/calendar'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
