<?php

declare(strict_types=1);

namespace Modules\Membership\Filament\Resources\MemberResource\Schemas;

use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Membership\Enums\MemberType;

class MemberSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Member Identity')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(User::class),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20),
                            ]),

                        Forms\Components\TextInput::make('member_code')
                            ->label('Member Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),

                        Forms\Components\Select::make('member_type')
                            ->label('Member Type')
                            ->options(MemberType::class)
                            ->required()
                            ->live()
                            ->default(MemberType::INDIVIDUAL),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Corporate Information')
                    ->schema([
                        Forms\Components\Select::make('parent_member_id')
                            ->label('Parent Member')
                            ->relationship('parentMember', 'member_code')
                            ->searchable()
                            ->preload()
                            ->helperText('For team members under a corporate account'),

                        Forms\Components\TextInput::make('company_name')
                            ->label('Company Name')
                            ->maxLength(255)
                            ->required(fn ($get) => $get('member_type') === MemberType::CORPORATE->value),

                        Forms\Components\TextInput::make('company_vat_number')
                            ->label('VAT Number')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('company_address')
                            ->label('Company Address')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->visible(fn ($get) => $get('member_type') === MemberType::CORPORATE->value),

                Section::make('Profile & CRM')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label('Bio')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('interests')
                            ->label('Interests')
                            ->placeholder('Add tags')
                            ->helperText('E.g., Entrepreneurship, Tech, Design'),

                        Forms\Components\TextInput::make('linkedin_url')
                            ->label('LinkedIn URL')
                            ->url()
                            ->prefixIcon(Heroicon::OutlinedLink)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('twitter_url')
                            ->label('Twitter/X URL')
                            ->url()
                            ->prefixIcon(Heroicon::OutlinedLink)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('website_url')
                            ->label('Website URL')
                            ->url()
                            ->prefixIcon(Heroicon::OutlinedGlobeAlt)
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Referral Tracking')
                    ->schema([
                        Forms\Components\Select::make('referred_by_member_id')
                            ->label('Referred By')
                            ->relationship('referredBy', 'member_code')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('referral_count')
                            ->label('Referrals Made')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Deactivation Info')
                    ->schema([
                        Forms\Components\Textarea::make('deactivation_reason')
                            ->label('Deactivation Reason')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('deactivated_at')
                            ->label('Deactivated At')
                            ->disabled(),
                    ])
                    ->visible(fn ($get) => ! $get('is_active'))
                    ->collapsible(),
            ]);
    }
}
