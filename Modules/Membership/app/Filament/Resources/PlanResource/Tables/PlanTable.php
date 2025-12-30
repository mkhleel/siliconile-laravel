<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\PlanResource\Tables;

use App\Enums\PlanType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;

class PlanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Columns\TextColumn::make('duration_days')
                    ->label('Duration (days)')
                    ->numeric()
                    ->sortable(),

                Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

                Columns\IconColumn::make('wifi_access')
                    ->label('WiFi')
                    ->boolean()
                    ->toggleable(),

                Columns\IconColumn::make('meeting_room_access')
                    ->label('Meeting Room')
                    ->boolean()
                    ->toggleable(),

                Columns\TextColumn::make('guest_passes')
                    ->label('Guests')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Columns\TextColumn::make('current_members')
                    ->label('Members')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->max_members === null) {
                            return $state.' / ∞';
                        }

                        return $state.' / '.$record->max_members;
                    })
                    ->sortable()
                    ->toggleable(),

                Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),

                Columns\TextColumn::make('sort_order')
                    ->label('Sort')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(PlanType::class)
                    ->multiple(),

                Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->native(false),

                Filters\TernaryFilter::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->native(false),
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
            ->defaultSort('sort_order')
            ->defaultPaginationPageOption(25);
    }
}
