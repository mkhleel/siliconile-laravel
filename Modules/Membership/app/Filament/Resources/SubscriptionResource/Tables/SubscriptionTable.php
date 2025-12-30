<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\SubscriptionResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Modules\Membership\Enums\SubscriptionStatus;

class SubscriptionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['member.user', 'plan']))
            ->columns([
                Columns\TextColumn::make('member.member_code')
                    ->label('Member Code')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('member.user.name')
                    ->label('Member Name')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date('M d, Y')
                    ->sortable(),

                Columns\TextColumn::make('end_date')
                    ->label('End')
                    ->date('M d, Y')
                    ->sortable(),

                Columns\IconColumn::make('auto_renew')
                    ->label('Auto Renew')
                    ->boolean()
                    ->toggleable(),

                Columns\TextColumn::make('price_at_subscription')
                    ->label('Price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable()
                    ->toggleable(),

                Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SubscriptionStatus::class)
                    ->multiple(),

                Filters\TernaryFilter::make('auto_renew')
                    ->label('Auto Renew')
                    ->boolean()
                    ->trueLabel('Auto Renew Enabled')
                    ->falseLabel('Auto Renew Disabled')
                    ->native(false),

                Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn ($query) => $query->expiringWithinDays(7)),

                Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Start From'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Start Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['start_from'], fn ($q, $date) => $q->where('start_date', '>=', $date))
                            ->when($data['start_until'], fn ($q, $date) => $q->where('start_date', '<=', $date));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
