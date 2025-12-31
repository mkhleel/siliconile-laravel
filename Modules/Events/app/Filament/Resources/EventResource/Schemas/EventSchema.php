<?php

declare(strict_types=1);

namespace Modules\Events\Filament\Resources\EventResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Enums\EventType;
use Modules\Events\Enums\LocationType;
use Modules\Events\Enums\TicketTypeStatus;

/**
 * Schema configuration for Event forms.
 */
class EventSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'lg' => 3,
            ])
            ->components([
                Tabs::make('EventTabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Basic Info')
                            ->icon('heroicon-o-information-circle')
                            ->schema(self::getBasicInfoSchema()),

                        Tab::make('Schedule & Location')
                            ->icon('heroicon-o-clock')
                            ->schema(self::getScheduleLocationSchema()),

                        Tab::make('Ticket Types')
                            ->icon('heroicon-o-ticket')
                            ->schema(self::getTicketTypesSchema()),

                        Tab::make('Registration Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema(self::getRegistrationSchema()),

                        Tab::make('SEO & Metadata')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema(self::getSeoSchema()),
                    ]),
            ]);
    }

    private static function getBasicInfoSchema(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    Section::make('Event Details')
                        ->columnSpan(2)
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Event Title')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, ?string $state, ?string $operation): void {
                                    if ($operation === 'create' && $state) {
                                        $set('slug', Str::slug($state));
                                    }
                                })
                                ->columnSpanFull(),

                            Forms\Components\TextInput::make('slug')
                                ->label('URL Slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->prefix(fn () => url('/events/').'/')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('short_description')
                                ->label('Short Description')
                                ->rows(2)
                                ->maxLength(500)
                                ->helperText('Brief summary shown in event listings (max 500 chars)')
                                ->columnSpanFull(),

                            Forms\Components\RichEditor::make('description')
                                ->label('Full Description')
                                ->toolbarButtons([
                                    'bold',
                                    'italic',
                                    'underline',
                                    'strike',
                                    'h2',
                                    'h3',
                                    'bulletList',
                                    'orderedList',
                                    'link',
                                    'blockquote',
                                    'redo',
                                    'undo',
                                ])
                                ->columnSpanFull(),
                        ]),

                    Section::make('Status & Type')
                        ->columnSpan(1)
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->label('Event Type')
                                ->options(EventType::class)
                                ->required()
                                ->native(false),

                            Forms\Components\Select::make('status')
                                ->label('Status')
                                ->options(EventStatus::class)
                                ->default(EventStatus::Draft)
                                ->required()
                                ->native(false),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('Featured Event')
                                ->helperText('Show prominently on homepage'),

                            Forms\Components\Toggle::make('is_free')
                                ->label('Free Event')
                                ->helperText('No payment required')
                                ->live(),

                            Forms\Components\Select::make('currency')
                                ->label('Currency')
                                ->options([
                                    'EGP' => 'EGP - Egyptian Pound',
                                    'USD' => 'USD - US Dollar',
                                ])
                                ->default('EGP')
                                ->required()
                                ->visible(fn (Get $get): bool => ! $get('is_free'))
                                ->native(false),
                        ]),

                    Section::make('Event Images')
                        ->columnSpanFull()
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\FileUpload::make('banner_image')
                                        ->label('Banner Image')
                                        ->image()
                                        ->disk('public')
                                        ->directory('events/banners')
                                        ->imageResizeMode('cover')
                                        ->imageCropAspectRatio('16:9')
                                        ->imageResizeTargetWidth('1920')
                                        ->imageResizeTargetHeight('1080')
                                        ->helperText('Recommended: 1920x1080px (16:9)'),

                                    Forms\Components\FileUpload::make('thumbnail_image')
                                        ->label('Thumbnail Image')
                                        ->image()
                                        ->disk('public')
                                        ->directory('events/thumbnails')
                                        ->imageResizeMode('cover')
                                        ->imageCropAspectRatio('4:3')
                                        ->imageResizeTargetWidth('800')
                                        ->imageResizeTargetHeight('600')
                                        ->helperText('Recommended: 800x600px (4:3)'),
                                ]),
                        ]),

                    Section::make('Organizer Information')
                        ->columnSpanFull()
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Forms\Components\Select::make('organizer_id')
                                        ->label('Organizer (User)')
                                        ->relationship('organizer', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->nullable(),

                                    Forms\Components\TextInput::make('organizer_name')
                                        ->label('Organizer Name')
                                        ->maxLength(255)
                                        ->helperText('Override if different from user'),

                                    Forms\Components\TextInput::make('organizer_email')
                                        ->label('Organizer Email')
                                        ->email()
                                        ->maxLength(255),
                                ]),
                        ]),
                ]),
        ];
    }

    private static function getScheduleLocationSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    Section::make('Date & Time')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Forms\Components\DateTimePicker::make('start_date')
                                        ->label('Start Date & Time')
                                        ->required()
                                        ->seconds(false)
                                        ->native(false)
                                        ->minDate(now()),

                                    Forms\Components\DateTimePicker::make('end_date')
                                        ->label('End Date & Time')
                                        ->seconds(false)
                                        ->native(false)
                                        ->after('start_date'),
                                ]),

                            Forms\Components\Select::make('timezone')
                                ->label('Timezone')
                                ->options([
                                    'Africa/Khartoum' => 'Africa/Khartoum (CAT)',
                                    'UTC' => 'UTC',
                                    'Europe/London' => 'Europe/London (GMT)',
                                ])
                                ->default('Africa/Khartoum')
                                ->required()
                                ->native(false),

                            Forms\Components\Toggle::make('is_multi_session')
                                ->label('Multi-Session Event')
                                ->helperText('Event has multiple sessions/days')
                                ->live(),

                            Forms\Components\TextInput::make('session_count')
                                ->label('Number of Sessions')
                                ->numeric()
                                ->minValue(2)
                                ->visible(fn (Get $get): bool => (bool) $get('is_multi_session')),
                        ]),

                    Section::make('Location')
                        ->schema([
                            Forms\Components\Select::make('location_type')
                                ->label('Location Type')
                                ->options(LocationType::class)
                                ->required()
                                ->live()
                                ->native(false),

                            Forms\Components\TextInput::make('location_name')
                                ->label('Venue Name')
                                ->maxLength(255)
                                ->visible(fn (Get $get): bool => in_array($get('location_type'), [
                                    LocationType::Physical->value,
                                    LocationType::Hybrid->value,
                                    'physical',
                                    'hybrid',
                                ])),

                            Forms\Components\Textarea::make('location_address')
                                ->label('Address')
                                ->rows(2)
                                ->visible(fn (Get $get): bool => in_array($get('location_type'), [
                                    LocationType::Physical->value,
                                    LocationType::Hybrid->value,
                                    'physical',
                                    'hybrid',
                                ])),

                            Forms\Components\TextInput::make('location_link')
                                ->label('Online Meeting Link')
                                ->url()
                                ->prefix('https://')
                                ->helperText('Zoom, Google Meet, or other video conference link')
                                ->visible(fn (Get $get): bool => in_array($get('location_type'), [
                                    LocationType::Virtual->value,
                                    LocationType::Hybrid->value,
                                    'online',
                                    'hybrid',
                                ])),

                            Forms\Components\Select::make('room_id')
                                ->label('Book a Room')
                                // ->relationship('room', 'name')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->helperText('Link to a coworking space room')
                                ->visible(fn (Get $get): bool => in_array($get('location_type'), [
                                    LocationType::Physical->value,
                                    LocationType::Hybrid->value,
                                    'physical',
                                    'hybrid',
                                ])),
                        ]),
                ]),
        ];
    }

    private static function getTicketTypesSchema(): array
    {
        return [
            Section::make('Ticket Types')
                ->description('Define ticket tiers and pricing for this event')
                ->schema([
                    Forms\Components\Repeater::make('ticketTypes')
                        ->relationship()
                        ->label('')
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Ticket Name')
                                        ->required()
                                        ->placeholder('e.g., Early Bird, VIP, Student')
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('price')
                                        ->label('Price')
                                        ->numeric()
                                        ->prefix('EGP')
                                        ->default(0)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('quantity')
                                        ->label('Available Qty')
                                        ->numeric()
                                        ->minValue(1)
                                        ->placeholder('Unlimited if empty')
                                        ->columnSpan(1),

                                    Forms\Components\Select::make('status')
                                        ->label('Status')
                                        ->options(TicketTypeStatus::class)
                                        ->default(TicketTypeStatus::Active)
                                        ->required()
                                        ->native(false)
                                        ->columnSpan(1),
                                ]),

                            Grid::make(4)
                                ->schema([
                                    Forms\Components\DateTimePicker::make('sale_start_date')
                                        ->label('Sale Starts')
                                        ->seconds(false)
                                        ->native(false)
                                        ->columnSpan(1),

                                    Forms\Components\DateTimePicker::make('sale_end_date')
                                        ->label('Sale Ends')
                                        ->seconds(false)
                                        ->native(false)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('min_per_order')
                                        ->label('Min/Order')
                                        ->numeric()
                                        ->default(1)
                                        ->minValue(1)
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('max_per_order')
                                        ->label('Max/Order')
                                        ->numeric()
                                        ->default(10)
                                        ->minValue(1)
                                        ->columnSpan(1),
                                ]),

                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(2)
                                ->placeholder('What\'s included with this ticket?')
                                ->columnSpanFull(),

                            Grid::make(2)
                                ->schema([
                                    Forms\Components\Toggle::make('is_hidden')
                                        ->label('Hidden')
                                        ->helperText('Only visible via direct link'),

                                    Forms\Components\Toggle::make('requires_promo_code')
                                        ->label('Requires Promo Code')
                                        ->helperText('Unlock with a special code'),
                                ]),
                        ])
                        ->reorderable()
                        ->reorderableWithButtons()
                        ->orderColumn('sort_order')
                        ->collapsible()
                        ->collapsed(fn (string $operation): bool => $operation === 'edit')
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'New Ticket Type')
                        ->addActionLabel('Add Ticket Type')
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ]),
        ];
    }

    private static function getRegistrationSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    Section::make('Registration Period')
                        ->schema([
                            Forms\Components\DateTimePicker::make('registration_start_date')
                                ->label('Registration Opens')
                                ->seconds(false)
                                ->native(false)
                                ->helperText('Leave empty to open immediately'),

                            Forms\Components\DateTimePicker::make('registration_end_date')
                                ->label('Registration Closes')
                                ->seconds(false)
                                ->native(false)
                                ->helperText('Leave empty to close at event start'),
                        ]),

                    Section::make('Capacity & Settings')
                        ->schema([
                            Forms\Components\TextInput::make('total_capacity')
                                ->label('Total Capacity')
                                ->numeric()
                                ->minValue(1)
                                ->helperText('Maximum attendees (sum of all ticket types)'),

                            Forms\Components\TextInput::make('max_tickets_per_order')
                                ->label('Max Tickets Per Order')
                                ->numeric()
                                ->default(10)
                                ->minValue(1)
                                ->maxValue(100),
                        ]),

                    Section::make('Registration Options')
                        ->columnSpanFull()
                        ->schema([
                            Grid::make(3)
                                ->schema([
                                    Forms\Components\Toggle::make('allow_guest_registration')
                                        ->label('Allow Guest Registration')
                                        ->helperText('Non-members can register')
                                        ->default(true),

                                    Forms\Components\Toggle::make('allow_waitlist')
                                        ->label('Enable Waitlist')
                                        ->helperText('Allow signups when sold out'),

                                    Forms\Components\Toggle::make('require_approval')
                                        ->label('Require Approval')
                                        ->helperText('Manually approve each registration'),
                                ]),
                        ]),
                ]),
        ];
    }

    private static function getSeoSchema(): array
    {
        return [
            Section::make('Search Engine Optimization')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Meta Title')
                        ->maxLength(60)
                        ->helperText('Recommended: 50-60 characters')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->maxLength(160)
                        ->rows(3)
                        ->helperText('Recommended: 150-160 characters')
                        ->columnSpanFull(),

                    Forms\Components\TagsInput::make('tags')
                        ->label('Tags')
                        ->separator(',')
                        ->suggestions([
                            'workshop',
                            'training',
                            'networking',
                            'tech',
                            'business',
                            'startup',
                            'coding',
                            'design',
                            'marketing',
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
