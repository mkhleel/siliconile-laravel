<?php

declare(strict_types=1);

namespace Modules\Incubation\Filament\Resources\CohortResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Incubation\Enums\CohortStatus;

/**
 * Schema configuration for Cohort forms.
 */
class CohortSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'lg' => 3,
            ])
            ->components([
                // Main Information - Left side (2 columns)
                Section::make('Cohort Information')
                    ->description('Basic information about this program cycle')
                    ->icon('heroicon-o-academic-cap')
                    ->columnSpan([
                        'sm' => 1,
                        'lg' => 2,
                    ])
                    ->schema(self::getBasicInfoSchema())
                    ->collapsible(),

                // Status & Capacity - Right side (1 column)
                Section::make('Status & Capacity')
                    ->description('Current status and capacity settings')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columnSpan([
                        'sm' => 1,
                        'lg' => 1,
                    ])
                    ->schema(self::getStatusSchema())
                    ->collapsible(),

                // Timeline Section - Full width
                Section::make('Timeline')
                    ->description('Application and program dates')
                    ->icon('heroicon-o-calendar')
                    ->columnSpan('full')
                    ->schema(self::getTimelineSchema())
                    ->collapsible(),

                // Program Details - Full width
                Section::make('Program Details')
                    ->description('Eligibility criteria and benefits')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columnSpan('full')
                    ->schema(self::getProgramDetailsSchema())
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    private static function getBasicInfoSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Cohort Name')
                ->placeholder('e.g., Cycle 1 - Winter 2025')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('slug')
                ->label('URL Slug')
                ->placeholder('Auto-generated from name')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->helperText('Used in URLs. Leave blank to auto-generate.')
                ->columnSpanFull(),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->placeholder('Describe this program cycle...')
                ->rows(4)
                ->columnSpanFull(),

            Forms\Components\Select::make('program_manager_user_id')
                ->label('Program Manager')
                ->relationship('programManager', 'name')
                ->searchable()
                ->preload()
                ->columnSpanFull(),

            Forms\Components\FileUpload::make('cover_image')
                ->label('Cover Image')
                ->image()
                ->directory('cohorts/covers')
                ->visibility('public')
                ->columnSpanFull(),
        ];
    }

    private static function getStatusSchema(): array
    {
        return [
            Forms\Components\Select::make('status')
                ->label('Status')
                ->options(CohortStatus::options())
                ->default(CohortStatus::DRAFT->value)
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('capacity')
                ->label('Max Startups')
                ->numeric()
                ->default(10)
                ->minValue(1)
                ->maxValue(100)
                ->required()
                ->suffix('startups'),

            Forms\Components\Placeholder::make('accepted_count')
                ->label('Accepted')
                ->content(fn ($record) => $record?->accepted_count ?? 0)
                ->visibleOn(['edit', 'view']),

            Forms\Components\Placeholder::make('available_spots')
                ->label('Available Spots')
                ->content(fn ($record) => $record?->available_spots ?? '-')
                ->visibleOn(['edit', 'view']),
        ];
    }

    private static function getTimelineSchema(): array
    {
        return [
            Grid::make(4)
                ->schema([
                    Forms\Components\DatePicker::make('application_start_date')
                        ->label('Applications Open')
                        ->native(false)
                        ->displayFormat('M j, Y'),

                    Forms\Components\DatePicker::make('application_end_date')
                        ->label('Applications Close')
                        ->native(false)
                        ->displayFormat('M j, Y')
                        ->afterOrEqual('application_start_date'),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Program Starts')
                        ->required()
                        ->native(false)
                        ->displayFormat('M j, Y'),

                    Forms\Components\DatePicker::make('end_date')
                        ->label('Program Ends')
                        ->required()
                        ->native(false)
                        ->displayFormat('M j, Y')
                        ->afterOrEqual('start_date'),
                ]),
        ];
    }

    private static function getProgramDetailsSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    Forms\Components\Repeater::make('eligibility_criteria')
                        ->label('Eligibility Criteria')
                        ->simple(
                            Forms\Components\TextInput::make('criterion')
                                ->placeholder('Enter a criterion...')
                        )
                        ->addActionLabel('Add Criterion')
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),

                    Forms\Components\Repeater::make('benefits')
                        ->label('Program Benefits')
                        ->simple(
                            Forms\Components\TextInput::make('benefit')
                                ->placeholder('Enter a benefit...')
                        )
                        ->addActionLabel('Add Benefit')
                        ->defaultItems(0)
                        ->reorderable()
                        ->collapsible(),
                ]),
        ];
    }
}
