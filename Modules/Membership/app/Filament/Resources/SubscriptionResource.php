<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Membership\Filament\Resources\SubscriptionResource\Pages;
use Modules\Membership\Filament\Resources\SubscriptionResource\Schemas\SubscriptionSchema;
use Modules\Membership\Filament\Resources\SubscriptionResource\Tables\SubscriptionTable;
use Modules\Membership\Models\Subscription;
use UnitEnum;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Membership';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionTable::configure($table);
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
