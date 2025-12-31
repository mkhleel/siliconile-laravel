<?php

declare(strict_types=1);

namespace Modules\Network\Filament\Resources;

use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Network\Enums\SyncAction;
use Modules\Network\Filament\Resources\NetworkSyncLogResource\Pages;
use Modules\Network\Models\NetworkSyncLog;
use UnitEnum;

/**
 * Filament Resource for viewing Network Sync Logs.
 *
 * Read-only resource for auditing network operations.
 */
class NetworkSyncLogResource extends Resource
{
    protected static ?string $model = NetworkSyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Network';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Sync Log';

    protected static ?string $pluralModelLabel = 'Sync Logs';

    public static function getNavigationLabel(): string
    {
        return __('Sync Logs');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('member.member_code')
                    ->label(__('Member Code'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('member.user.name')
                    ->label(__('Member Name'))
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('action')
                    ->label(__('Action'))
                    ->badge()
                    ->formatStateUsing(fn (SyncAction $state) => $state->label())
                    ->icon(fn (SyncAction $state) => $state->icon())
                    ->color(fn (SyncAction $state) => match ($state) {
                        SyncAction::CREATED, SyncAction::ENABLED => 'success',
                        SyncAction::DISABLED, SyncAction::KICKED, SyncAction::DELETED => 'danger',
                        SyncAction::UPDATED, SyncAction::PASSWORD_RESET => 'warning',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('error_message')
                    ->label(__('Error'))
                    ->wrap()
                    ->limit(50)
                    ->toggleable()
                    ->visible(fn ($record) => $record?->status === 'failed'),

                Tables\Columns\TextColumn::make('router_ip')
                    ->label(__('Router IP'))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label(__('Action'))
                    ->options(
                        collect(SyncAction::cases())
                            ->mapWithKeys(fn (SyncAction $action) => [$action->value => $action->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options([
                        'success' => __('Success'),
                        'failed' => __('Failed'),
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('from')
                                    ->label(__('From')),
                                DatePicker::make('until')
                                    ->label(__('Until')),
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNetworkSyncLogs::route('/'),
            'view' => Pages\ViewNetworkSyncLog::route('/{record}'),
        ];
    }
}
