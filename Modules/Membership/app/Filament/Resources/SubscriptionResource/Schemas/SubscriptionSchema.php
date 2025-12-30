<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\SubscriptionResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Membership\Enums\SubscriptionStatus;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Plan;

class SubscriptionSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription Details')
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'member_code', fn ($query) => $query->with('user'))
                            ->getOptionLabelFromRecordUsing(fn (Member $record) => $record->member_code.' - '.$record->user->name)
                            ->searchable(['member_code'])
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('plan_id')
                            ->label('Plan')
                            ->relationship('plan', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($set, $get, ?int $state) {
                                if ($state) {
                                    $plan = Plan::find($state);
                                    if ($plan) {
                                        $set('price_at_subscription', $plan->price);
                                        $set('currency', $plan->currency);

                                        // Calculate dates
                                        $startDate = $get('start_date') ?? now();
                                        $endDate = now()->parse($startDate)->addDays($plan->duration_days);
                                        $set('end_date', $endDate);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(SubscriptionStatus::class)
                            ->required()
                            ->default(SubscriptionStatus::PENDING),

                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Auto Renew')
                            ->default(false),
                    ])
                    ->columns(2),

                Section::make('Dates')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now())
                            ->live(),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required(),

                        Forms\Components\DatePicker::make('next_billing_date')
                            ->label('Next Billing Date')
                            ->visible(fn ($get) => $get('auto_renew')),

                        Forms\Components\TextInput::make('grace_period_days')
                            ->label('Grace Period (Days)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Section::make('Pricing')
                    ->schema([
                        Forms\Components\TextInput::make('price_at_subscription')
                            ->label('Price')
                            ->numeric()
                            ->prefix('EGP')
                            ->required(),

                        Forms\Components\TextInput::make('currency')
                            ->label('Currency')
                            ->default('EGP')
                            ->maxLength(3)
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Cancellation Info')
                    ->schema([
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Cancelled At')
                            ->disabled(),

                        Forms\Components\Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('status') === SubscriptionStatus::CANCELLED->value)
                    ->collapsible(),
            ]);
    }
}
