<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\MentorResource\Schemas;

use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Schema configuration for Mentor forms.
 */
class MentorSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Section::make('Mentor Information')
                        ->columnSpan(2)
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Link to User Account')
                                ->options(User::query()->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->helperText('Optional: Link to an existing user account'),

                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(50),

                            Forms\Components\TextInput::make('company')
                                ->label('Company/Organization')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('title')
                                ->label('Job Title')
                                ->maxLength(255),

                            Forms\Components\Textarea::make('bio')
                                ->rows(4)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Section::make('Status')
                        ->columnSpan(1)
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),

                            Forms\Components\TextInput::make('max_mentees')
                                ->label('Max Mentees')
                                ->numeric()
                                ->default(5)
                                ->minValue(1)
                                ->maxValue(20),

                            Forms\Components\Placeholder::make('stats')
                                ->label('Statistics')
                                ->content(function ($record): string {
                                    if (! $record) {
                                        return 'New mentor';
                                    }

                                    return sprintf(
                                        '%d sessions • %d completed • %.1f avg rating',
                                        $record->total_sessions,
                                        $record->completed_sessions,
                                        $record->average_rating ?? 0
                                    );
                                }),
                        ]),
                ]),

                Section::make('Expertise & Availability')
                    ->schema([
                        Forms\Components\TagsInput::make('expertise')
                            ->label('Areas of Expertise')
                            ->placeholder('Add expertise areas')
                            ->suggestions([
                                'Product Development',
                                'Marketing',
                                'Sales',
                                'Fundraising',
                                'Finance',
                                'Operations',
                                'Technology',
                                'Legal',
                                'HR',
                                'Strategy',
                            ])
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('availability')
                            ->label('Weekly Availability')
                            ->keyLabel('Day')
                            ->valueLabel('Time Slots')
                            ->keyPlaceholder('e.g., Monday')
                            ->valuePlaceholder('e.g., 10:00-12:00, 14:00-16:00')
                            ->columnSpanFull(),
                    ]),

                Section::make('Social Links')
                    ->schema([
                        Forms\Components\TextInput::make('linkedin_url')
                            ->label('LinkedIn')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('twitter_url')
                            ->label('Twitter/X')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('website_url')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}
