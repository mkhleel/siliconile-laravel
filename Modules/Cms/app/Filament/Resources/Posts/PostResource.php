<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Posts;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
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
use Modules\Cms\Filament\Resources\Posts\Pages\CreatePost;
use Modules\Cms\Filament\Resources\Posts\Pages\EditPost;
use Modules\Cms\Filament\Resources\Posts\Pages\ListPosts;
use Modules\Cms\Filament\Resources\Posts\Pages\ViewPost;
use Modules\Cms\Models\Category;
use Modules\Cms\Models\Post;
use Modules\Cms\Models\Tag;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Newspaper;

    public static function getNavigationLabel(): string
    {
        return __('Posts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('CMS');
    }

    protected static ?int $navigationSort = 2;

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
                                // Post Information Section
                                Section::make(__('Post Information'))
                                    ->description(__('Basic post details and settings'))
                                    ->icon(Heroicon::DocumentText)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('title')
                                                    ->label('Post Title')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (
                                                        string $context,
                                                        $state,
                                                        Set $set
                                                    ) => $context === 'create' ? $set('slug', Str::slug($state)) : null
                                                    )
                                                    ->columnSpanFull(),

                                                TextInput::make('slug')
                                                    ->label('URL Slug')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(Post::class, 'slug', ignoreRecord: true)
                                                    ->alphaDash()
                                                    ->columnSpan(1),

                                                Select::make('status')
                                                    ->label('Status')
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
                                            ->label('Post Excerpt')
                                            ->maxLength(500)
                                            ->rows(3)
                                            ->helperText(__('Brief summary of the post content'))
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                DateTimePicker::make('published_at')
                                                    ->label('Publish Date')
                                                    ->default(now())
                                                    ->columnSpan(1),

                                                Toggle::make('is_featured')
                                                    ->label('Featured Post')
                                                    ->helperText(__('Mark this post as featured'))
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                // Post Content Section
                                Section::make(__('Post Content'))
                                    ->description(__('Main content and rich text editor'))
                                    ->icon(Heroicon::PencilSquare)
                                    ->schema([
                                        ContentBlocks::makeRichEditorForPosts(),
                                    ]),

                                // SEO Section (Collapsible)
                                Section::make(__('SEO Settings'))
                                    ->description(__('Search engine optimization settings'))
                                    ->icon(Heroicon::MagnifyingGlass)
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        TextInput::make('meta_title')
                                            ->label('Meta Title')
                                            ->maxLength(60)
                                            ->helperText(__('Recommended: 50-60 characters'))
                                            ->columnSpanFull(),

                                        Textarea::make('meta_description')
                                            ->label('Meta Description')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText(__('Recommended: 150-160 characters'))
                                            ->columnSpanFull(),

                                        Textarea::make('meta_keywords')
                                            ->label('Meta Keywords')
                                            ->maxLength(255)
                                            ->helperText(__('Comma-separated keywords'))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // Sidebar (1/3 width)
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                // Organization Section
                                Section::make(__('Organization'))
                                    ->description(__('Post categorization and relationships'))
                                    ->icon(Heroicon::FolderOpen)
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('Category')
                                            ->relationship('category', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn ($state, Set $set) => $set('slug',
                                                        Str::slug($state))
                                                    ),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique(Category::class, 'slug'),
                                                Textarea::make('description'),
                                            ])
                                            ->columnSpanFull(),

                                        Select::make('author_id')
                                            ->label('Author')
                                            ->relationship('author', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(auth()->id())
                                            ->columnSpanFull(),

                                        Select::make('tags')
                                            ->label('Tags')
                                            ->relationship('tags', 'name')
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn ($state, Set $set) => $set('slug',
                                                        Str::slug($state))
                                                    ),
                                                TextInput::make('slug')
                                                    ->required()
                                                    ->unique(Tag::class, 'slug'),
                                                Textarea::make('description'),
                                                ColorPicker::make('color')
                                                    ->default('#3b82f6'),
                                            ])
                                            ->columnSpanFull(),

                                        TextInput::make('reading_time')
                                            ->label('Reading Time (minutes)')
                                            ->numeric()
                                            ->default(0)
                                            ->helperText(__('Will be calculated automatically if left at 0'))
                                            ->columnSpanFull(),
                                    ]),

                                // Media Section
                                Section::make(__('Featured Image'))
                                    ->description(__('Upload and manage post images'))
                                    ->icon(Heroicon::Photo)
                                    ->schema([
                                        FileUpload::make('featured_image')
                                            ->label('Featured Image')
                                            ->image()
                                            ->directory('cms/posts')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->helperText(__('Upload a featured image for this post'))
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
                    ->sortable()
                    ->limit(50),
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
                TextColumn::make('author.name')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tags.name')
                    ->badge()
                    ->separator(',')
                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('reading_time')
                    ->suffix(' min')
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
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
                SelectFilter::make('author')
                    ->relationship('author', 'name'),
                Filter::make('is_featured')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('publish')
                    ->icon('heroicon-o-eye')
                    ->action(function (Post $record) {
                        $record->update([
                            'status' => 'published',
                            'published_at' => $record->published_at ?? now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Post $record) => $record->status !== 'published'),
                Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->action(function (Post $record) {
                        $data = $record->toArray();
                        unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
                        $data['title'] = $data['title'].' (Copy)';
                        $data['slug'] = Str::slug($data['title']);
                        $data['status'] = 'draft';
                        $data['published_at'] = null;
                        $newPost = Post::create($data);
                        $newPost->tags()->sync($record->tags->pluck('id'));
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
            ->defaultSort('created_at', 'desc');
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
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
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
