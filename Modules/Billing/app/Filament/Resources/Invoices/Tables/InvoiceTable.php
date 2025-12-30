<?php

declare(strict_types=1);

namespace Modules\Billing\Filament\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Billing\Enums\InvoiceStatus;

class InvoiceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->placeholder('DRAFT')
                    ->weight('bold'),

                Columns\TextColumn::make('billable_name')
                    ->label('Customer')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('billable', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                                    $userQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->description(fn ($record) => $record->billable_email),

                Columns\TextColumn::make('issue_date')
                    ->label('Issue Date')
                    ->date('M d, Y')
                    ->sortable(),

                Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null),

                Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency ?? 'SAR')
                    ->sortable()
                    ->alignEnd(),

                Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->options(InvoiceStatus::class)
                    ->multiple(),

                Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query) => $query->overdue())
                    ->toggle(),

                Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Issue Date From'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Issue Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),

                Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn ($record) => $record->isEditable()),
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
}
