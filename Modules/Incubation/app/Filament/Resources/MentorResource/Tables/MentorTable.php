<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\MentorResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Modules\Incubation\Models\Mentor;

/**
 * Table configuration for Mentor listing.
 */
class MentorTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),

                Columns\TextColumn::make('company')
                    ->searchable()
                    ->toggleable(),

                Columns\TextColumn::make('expertise')
                    ->badge()
                    ->separator(',')
                    ->limit(3),

                Columns\TextColumn::make('completed_sessions')
                    ->label('Sessions')
                    ->sortable(),

                Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn (?float $state): string => $state ? number_format($state, 1).'★' : '-')
                    ->sortable(),

                Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Filters\Filter::make('has_availability')
                    ->label('Has Availability')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->where('max_mentees', '>', 0)
                            ->orWhereNull('max_mentees');
                    })),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }
}
