<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Pages;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Modules\Cms\Filament\Blocks\ContentBlocks;
use Modules\Cms\Filament\Resources\Pages\Pages\CreatePage;
use Modules\Cms\Filament\Resources\Pages\Pages\EditPage;
use Modules\Cms\Filament\Resources\Pages\Pages\ListPages;
use Modules\Cms\Filament\Resources\Pages\Pages\ViewPage;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    public static function getNavigationLabel(): string
    {
        return __('Pages');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('CMS');
    }

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        // Main Content Area (2/3 width)
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                // Basic Information Section
                                Section::make(__('Page Information'))
                                    ->description(__('Basic page details and settings'))
                                    ->icon(Heroicon::DocumentText)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (string $context, $state, Set $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null)
                                                    ->columnSpanFull(),

                                                TextInput::make('slug')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(Page::class, 'slug', ignoreRecord: true)
                                                    ->alphaDash()
                                                    ->columnSpan(1),

                                                Select::make('status')
                                                    ->options([
                                                        'draft' => 'Draft',
                                                        'published' => 'Published',
                                                        'archived' => 'Archived',
                                                    ])
                                                    ->default('draft')
                                                    ->required()
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('excerpt')
                                            ->maxLength(500)
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                DateTimePicker::make('published_at')
                                                    ->default(now())
                                                    ->columnSpan(1),

                                                Select::make('template')
                                                    ->options([
                                                        'default' => 'Default',
                                                        'full-width' => 'Full Width',
                                                        'sidebar' => 'With Sidebar',
                                                        'landing' => 'Landing Page',
                                                    ])
                                                    ->default('default')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                // Content Section
                                Section::make(__('Page Content'))
                                    ->description(__('Main content and rich text editor'))
                                    ->icon(Heroicon::PencilSquare)
                                    ->schema([
                                        ContentBlocks::makeRichEditorForPages('content'),
                                    ]),

                                // SEO Section (Collapsible)
                                Section::make(__('SEO Settings'))
                                    ->description(__('Search engine optimization settings'))
                                    ->icon(Heroicon::MagnifyingGlass)
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->maxLength(60)
                                            ->helperText('Recommended: 50-60 characters')
                                            ->columnSpanFull(),

                                        Textarea::make('meta_description')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText('Recommended: 150-160 characters')
                                            ->columnSpanFull(),

                                        Textarea::make('meta_keywords')
                                            ->maxLength(255)
                                            ->helperText('Comma-separated keywords')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Sidebar (1/3 width)
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                // Organization Section
                                Section::make(__('Organization'))
                                    ->description(__('Page hierarchy and categorization'))
                                    ->icon(Heroicon::FolderOpen)
                                    ->schema([
                                        Select::make('parent_id')
                                            ->relationship('parent', 'title')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull(),

                                        Select::make('category_id')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state))),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique(Category::class, 'slug'),
                                                Textarea::make('description'),
                                            ])
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('sort_order')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->columnSpan(1),

                                                Toggle::make('is_featured')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                // Media Section
                                Section::make(__('Featured Image'))
                                    ->description(__('Upload and manage page images'))
                                    ->icon(Heroicon::Photo)
                                    ->schema([
                                        FileUpload::make('featured_image')
                                            ->image()
                                            ->directory('cms/pages')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'published',
                        'danger' => 'archived',
                    ]),
                TextColumn::make('category.name')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('parent.title')

                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
                Filter::make('is_featured')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('publish')
                    ->icon(Heroicon::Eye)
                    ->action(function (Page $record) {
                        $record->update([
                            'status' => 'published',
                            'published_at' => $record->published_at ?? now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Page $record) => $record->status !== 'published'),
                Action::make('duplicate')
                    ->icon(Heroicon::DocumentDuplicate)
                    ->action(function (Page $record) {
                        $data = $record->toArray();
                        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
                        $data['title'] = $data['title'].' (Copy)';
                        $data['slug'] = Str::slug($data['title']);
                        $data['status'] = 'draft';
                        $data['published_at'] = null;
                        Page::create($data);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    Action::make('publish')
                        ->icon(Heroicon::Eye)
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update([
                                    'status' => 'published',
                                    'published_at' => $record->published_at ?? now(),
                                ]);
                            });
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'view' => ViewPage::route('/{record}'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
