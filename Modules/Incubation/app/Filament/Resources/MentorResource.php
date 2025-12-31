<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Incubation\Filament\Resources\MentorResource\Pages;
use Modules\Incubation\Filament\Resources\MentorResource\Schemas\MentorSchema;
use Modules\Incubation\Filament\Resources\MentorResource\Tables\MentorTable;
use Modules\Incubation\Models\Mentor;
use UnitEnum;

/**
 * Filament Resource for managing Mentors.
 */
class MentorResource extends Resource
{
    protected static ?string $model = Mentor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Incubation';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Mentors';

    public static function form(Schema $schema): Schema
    {
        return MentorSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MentorTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMentors::route('/'),
            'create' => Pages\CreateMentor::route('/create'),
            'edit' => Pages\EditMentor::route('/{record}/edit'),
        ];
    }
}
