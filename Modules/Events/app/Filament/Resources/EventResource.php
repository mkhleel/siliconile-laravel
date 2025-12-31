<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Events\Filament\Resources\EventResource\Pages;
use Modules\Events\Filament\Resources\EventResource\RelationManagers\AttendeesRelationManager;
use Modules\Events\Filament\Resources\EventResource\RelationManagers\TicketTypesRelationManager;
use Modules\Events\Filament\Resources\EventResource\Schemas\EventSchema;
use Modules\Events\Filament\Resources\EventResource\Tables\EventTable;
use Modules\Events\Models\Event;
use UnitEnum;

/**
 * Filament Resource for managing Events.
 *
 * Allows administrators to create, edit, and manage events
 * including ticket types, attendees, and check-in functionality.
 */
class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Events';

    protected static ?string $modelLabel = 'Event';

    protected static ?string $pluralModelLabel = 'Events';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return EventSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TicketTypesRelationManager::class,
            AttendeesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'published')
            ->where('start_date', '>=', now())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Upcoming published events';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'description', 'organizer_name'];
    }
}
