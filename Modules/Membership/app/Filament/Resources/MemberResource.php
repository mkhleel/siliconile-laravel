<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Membership\Filament\Resources\MemberResource\Pages;
use Modules\Membership\Filament\Resources\MemberResource\Schemas\MemberSchema;
use Modules\Membership\Filament\Resources\MemberResource\Tables\MemberTable;
use Modules\Membership\Models\Member;
use UnitEnum;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string | UnitEnum | null $navigationGroup = 'Membership';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MemberSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MemberTable::configure($table);
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
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'view' => Pages\ViewMember::route('/{record}'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}
