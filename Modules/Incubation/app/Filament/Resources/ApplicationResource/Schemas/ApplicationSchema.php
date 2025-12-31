<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\ApplicationResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Modules\Incubation\Enums\ApplicationStatus;
use Modules\Incubation\Enums\StartupStage;
use Modules\Incubation\Models\Cohort;

/**
 * Schema configuration for Application forms.
 */
class ApplicationSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Application')
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-building-office')
                            ->schema(self::getBasicInfoSchema()),

                        Tabs\Tab::make('Founders')
                            ->icon('heroicon-o-users')
                            ->schema(self::getFoundersSchema()),

                        Tabs\Tab::make('Business Details')
                            ->icon('heroicon-o-briefcase')
                            ->schema(self::getBusinessSchema()),

                        Tabs\Tab::make('Materials')
                            ->icon('heroicon-o-document-text')
                            ->schema(self::getMaterialsSchema()),

                        Tabs\Tab::make('Evaluation')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->schema(self::getEvaluationSchema())
                            ->visible(fn ($operation) => $operation !== 'create'),

                        Tabs\Tab::make('Interview')
                            ->icon('heroicon-o-calendar')
                            ->schema(self::getInterviewSchema())
                            ->visible(fn ($operation) => $operation !== 'create'),
                    ]),
            ]);
    }

    private static function getBasicInfoSchema(): array
    {
        return [
            Grid::make(3)->schema([
                Section::make('Startup Information')
                    ->columnSpan(2)
                    ->schema([
                        Forms\Components\Select::make('cohort_id')
                            ->label('Cohort')
                            ->options(Cohort::query()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('startup_name')
                            ->label('Startup Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(50),

                        Forms\Components\TextInput::make('website_url')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Status')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\TextInput::make('application_code')
                            ->label('Application Code')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn(['edit', 'view']),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ApplicationStatus::options())
                            ->default(ApplicationStatus::SUBMITTED->value)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options([
                                'website' => 'Website',
                                'referral' => 'Referral',
                                'event' => 'Event',
                                'social_media' => 'Social Media',
                                'other' => 'Other',
                            ])
                            ->default('website')
                            ->native(false),

                        Forms\Components\TextInput::make('referral_source')
                            ->label('Referral Details')
                            ->maxLength(255),
                    ]),
            ]),
        ];
    }

    private static function getFoundersSchema(): array
    {
        return [
            Forms\Components\Repeater::make('founders_data')
                ->label('Founders / Team Members')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('role')
                            ->label('Role')
                            ->placeholder('e.g., CEO, CTO')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('linkedin')
                            ->label('LinkedIn URL')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('bio')
                            ->label('Short Bio')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                ])
                ->addActionLabel('Add Founder')
                ->minItems(1)
                ->defaultItems(1)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Founder')
                ->columnSpanFull(),
        ];
    }

    private static function getBusinessSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('Problem & Solution')
                    ->schema([
                        Forms\Components\Textarea::make('problem_statement')
                            ->label('Problem Statement')
                            ->required()
                            ->rows(4)
                            ->placeholder('What problem are you solving?')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('solution')
                            ->label('Solution')
                            ->required()
                            ->rows(4)
                            ->placeholder('How does your solution address this problem?')
                            ->columnSpanFull(),
                    ]),

                Section::make('Business Model')
                    ->schema([
                        Forms\Components\TextInput::make('industry')
                            ->label('Industry')
                            ->maxLength(100),

                        Forms\Components\Select::make('business_model')
                            ->label('Business Model')
                            ->options([
                                'B2B' => 'B2B',
                                'B2C' => 'B2C',
                                'B2B2C' => 'B2B2C',
                                'Marketplace' => 'Marketplace',
                                'SaaS' => 'SaaS',
                                'Other' => 'Other',
                            ])
                            ->native(false),

                        Forms\Components\Select::make('stage')
                            ->label('Stage')
                            ->options(StartupStage::options())
                            ->native(false),

                        Forms\Components\Textarea::make('traction')
                            ->label('Current Traction')
                            ->rows(3)
                            ->placeholder('Users, revenue, key metrics...'),

                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('funding_raised')
                                ->label('Funding Raised')
                                ->numeric()
                                ->prefix(fn ($get) => $get('funding_currency') ?? 'EGP'),

                            Forms\Components\Select::make('funding_currency')
                                ->label('Currency')
                                ->options([
                                    'EGP' => 'EGP',
                                    'USD' => 'USD',
                                    'EUR' => 'EUR',
                                ])
                                ->default('EGP')
                                ->native(false),
                        ]),
                    ]),
            ]),

            Forms\Components\Textarea::make('why_apply')
                ->label('Why Are You Applying?')
                ->rows(4)
                ->placeholder('What do you hope to achieve from this program?')
                ->columnSpanFull(),
        ];
    }

    private static function getMaterialsSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('Pitch Materials')
                    ->schema([
                        Forms\Components\FileUpload::make('pitch_deck_path')
                            ->label('Upload Pitch Deck')
                            ->acceptedFileTypes(['application/pdf', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'])
                            ->maxSize(20480) // 20MB
                            ->directory('pitch-decks/'.now()->format('Y/m'))
                            ->visibility('private')
                            ->downloadable()
                            ->previewable(false)
                            ->helperText('PDF or PowerPoint (max 20MB)'),

                        Forms\Components\TextInput::make('pitch_deck_url')
                            ->label('Or Pitch Deck URL')
                            ->url()
                            ->placeholder('Link to Google Slides, Canva, etc.')
                            ->maxLength(500),

                        Forms\Components\TextInput::make('video_pitch_url')
                            ->label('Video Pitch URL')
                            ->url()
                            ->placeholder('YouTube, Vimeo, Loom link')
                            ->maxLength(500),
                    ]),

                Section::make('Social Links')
                    ->schema([
                        Forms\Components\TextInput::make('social_links.linkedin')
                            ->label('LinkedIn')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('social_links.twitter')
                            ->label('Twitter/X')
                            ->url()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('social_links.facebook')
                            ->label('Facebook')
                            ->url()
                            ->maxLength(255),
                    ]),
            ]),

            Forms\Components\Textarea::make('additional_notes')
                ->label('Additional Notes')
                ->rows(3)
                ->placeholder('Anything else you want to share?')
                ->columnSpanFull(),
        ];
    }

    private static function getEvaluationSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('Scoring')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('evaluation_scores.innovation')
                                ->label('Innovation (1-10)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10),

                            Forms\Components\TextInput::make('evaluation_scores.team')
                                ->label('Team (1-10)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10),

                            Forms\Components\TextInput::make('evaluation_scores.market')
                                ->label('Market Potential (1-10)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10),

                            Forms\Components\TextInput::make('evaluation_scores.traction')
                                ->label('Traction (1-10)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10),

                            Forms\Components\TextInput::make('evaluation_scores.scalability')
                                ->label('Scalability (1-10)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10),

                            Forms\Components\TextInput::make('evaluation_scores.fit')
                                ->label('Program Fit (1-10)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(10),
                        ]),

                        Forms\Components\Placeholder::make('score')
                            ->label('Overall Score')
                            ->content(fn ($record) => $record?->score ? number_format($record->score, 1).'/100' : 'Not scored'),
                    ]),

                Section::make('Internal Notes')
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Internal Notes')
                            ->rows(6)
                            ->placeholder('Private notes for reviewers (not visible to applicant)')
                            ->helperText('These notes are only visible to staff'),
                    ]),
            ]),
        ];
    }

    private static function getInterviewSchema(): array
    {
        return [
            Grid::make(2)->schema([
                Section::make('Interview Details')
                    ->schema([
                        Forms\Components\DateTimePicker::make('interview_scheduled_at')
                            ->label('Scheduled Date & Time')
                            ->native(false)
                            ->displayFormat('M j, Y H:i'),

                        Forms\Components\Select::make('interview_location')
                            ->label('Location')
                            ->options([
                                'Online' => 'Online (Video Call)',
                                'Office' => 'Office',
                                'Other' => 'Other',
                            ])
                            ->default('Online')
                            ->native(false),

                        Forms\Components\TextInput::make('interview_meeting_link')
                            ->label('Meeting Link')
                            ->url()
                            ->placeholder('Zoom, Google Meet, etc.')
                            ->maxLength(500),
                    ]),

                Section::make('Interview Notes')
                    ->schema([
                        Forms\Components\Textarea::make('interview_notes')
                            ->label('Interview Notes')
                            ->rows(6)
                            ->placeholder('Notes from the interview'),
                    ]),
            ]),

            Section::make('Decision')
                ->schema([
                    Grid::make(3)->schema([
                        Forms\Components\Placeholder::make('decision_at')
                            ->label('Decision Date')
                            ->content(fn ($record) => $record?->decision_at?->format('M j, Y H:i') ?? 'Pending'),

                        Forms\Components\Placeholder::make('decided_by')
                            ->label('Decided By')
                            ->content(fn ($record) => $record?->decidedBy?->name ?? '-'),

                        Forms\Components\Placeholder::make('rejection_reason_display')
                            ->label('Rejection Reason')
                            ->content(fn ($record) => $record?->rejection_reason ?? '-')
                            ->visible(fn ($record) => $record?->status === ApplicationStatus::REJECTED),
                    ]),
                ])
                ->visible(fn ($record) => $record?->decision_at !== null),
        ];
    }
}
