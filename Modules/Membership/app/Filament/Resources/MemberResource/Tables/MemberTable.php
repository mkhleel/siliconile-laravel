<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\MemberResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Modules\Membership\Enums\MemberType;

class MemberTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Eager load relationships to prevent N+1 queries
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'subscriptions']))
            ->columns([
                Columns\TextColumn::make('member_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Columns\TextColumn::make('member_type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),

                Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Columns\TextColumn::make('referral_count')
                    ->label('Referrals')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Columns\TextColumn::make('created_at')
                    ->label('Member Since')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Filters\SelectFilter::make('member_type')
                    ->label('Member Type')
                    ->options(MemberType::class),

                Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->native(false),

                Filters\Filter::make('has_referrals')
                    ->label('Has Referrals')
                    ->query(fn ($query) => $query->where('referral_count', '>', 0)),
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
