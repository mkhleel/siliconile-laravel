<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\SubscriptionResource\Pages;

use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Membership\Filament\Resources\SubscriptionResource;
use Modules\Membership\Services\SubscriptionService;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status->value === 'pending')
                ->action(function ($record) {
                    app(SubscriptionService::class)->activateSubscription($record);
                    $record->refresh();
                }),

            Actions\Action::make('renew')
                ->label('Renew')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->canRenew())
                ->action(function ($record) {
                    app(SubscriptionService::class)->renewSubscription($record);
                    $record->refresh();
                }),

            Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Components\TextEntry::make('cancellation_reason')
                        ->label('Reason for Cancellation'),
                ])
                ->visible(fn ($record) => $record->isActive())
                ->action(function ($record, array $data) {
                    app(SubscriptionService::class)->cancelSubscription(
                        $record,
                        $data['cancellation_reason'] ?? 'No reason provided',
                        auth()->id()
                    );
                    $record->refresh();
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Overview')
                    ->schema([
                        Components\TextEntry::make('member.member_code')
                            ->label('Member Code'),
                        Components\TextEntry::make('member.user.name')
                            ->label('Member Name'),
                        Components\TextEntry::make('plan.name')
                            ->label('Plan'),
                        Components\TextEntry::make('status')
                            ->badge(),
                        Components\IconEntry::make('auto_renew')
                            ->label('Auto Renew')
                            ->boolean(),
                        Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Dates & Duration')
                    ->schema([
                        Components\TextEntry::make('start_date')
                            ->label('Start Date')
                            ->date(),
                        Components\TextEntry::make('end_date')
                            ->label('End Date')
                            ->date(),
                        Components\TextEntry::make('next_billing_date')
                            ->label('Next Billing')
                            ->date()
                            ->placeholder('—'),
                        Components\TextEntry::make('grace_period_days')
                            ->label('Grace Period')
                            ->suffix(' days'),
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        Components\TextEntry::make('price_at_subscription')
                            ->label('Price')
                            ->money(fn ($record) => $record->currency),
                        Components\TextEntry::make('currency')
                            ->label('Currency'),
                    ])
                    ->columns(2),
            ]);
    }
}
