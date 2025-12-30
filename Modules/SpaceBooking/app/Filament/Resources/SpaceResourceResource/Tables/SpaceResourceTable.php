<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources\SpaceResourceResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Modules\SpaceBooking\Enums\ResourceType;

/**
 * Table configuration for SpaceResource listing.
 */
class SpaceResourceTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Columns\ImageColumn::make('image')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&background=random'),

                Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Columns\TextColumn::make('resource_type')
                    ->badge()
                    ->formatStateUsing(fn (ResourceType $state): string => $state->label())
                    ->color(fn (ResourceType $state): string => $state->color())
                    ->sortable(),

                Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->sortable()
                    ->alignCenter()
                    ->suffix(' people'),

                Columns\TextColumn::make('location')
                    ->searchable()
                    ->toggleable(),

                Columns\TextColumn::make('hourly_rate')
                    ->money('SDG')
                    ->sortable()
                    ->toggleable()
                    ->label('Hourly'),

                Columns\TextColumn::make('daily_rate')
                    ->money('SDG')
                    ->sortable()
                    ->toggleable()
                    ->label('Daily'),

                Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Columns\TextColumn::make('bookings_count')
                    ->counts('bookings')
                    ->label('Bookings')
                    ->sortable()
                    ->alignCenter(),

                Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('resource_type')
                    ->options(ResourceType::options())
                    ->label('Type'),

                Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Filters\TernaryFilter::make('requires_approval')
                    ->label('Approval Required')
                    ->placeholder('All')
                    ->trueLabel('Requires approval')
                    ->falseLabel('Auto-confirmed'),
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
            ->reorderable('sort_order')
            ->deferFilters();
    }
}
