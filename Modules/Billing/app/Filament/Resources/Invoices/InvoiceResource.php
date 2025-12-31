<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Billing\Filament\Resources\Invoices\Schemas\InvoiceSchema;
use Modules\Billing\Filament\Resources\Invoices\Tables\InvoiceTable;
use Modules\Billing\Models\Invoice;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationBadge(): ?string
    {
        return (string) Invoice::unpaid()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $unpaidCount = Invoice::unpaid()->count();

        return $unpaidCount > 5 ? 'danger' : 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return InvoiceSchema::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoiceTable::configure($table);
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'billable.name', 'billable.email'];
    }
}
