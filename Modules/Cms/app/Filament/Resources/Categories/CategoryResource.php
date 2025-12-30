<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Categories;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Modules\Cms\Filament\Resources\Categories\Pages\CreateCategory;
use Modules\Cms\Filament\Resources\Categories\Pages\EditCategory;
use Modules\Cms\Filament\Resources\Categories\Pages\ListCategories;
use Modules\Cms\Filament\Resources\Categories\Pages\ViewCategory;
use Modules\Cms\Models\Category;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Folder;

    public static function getNavigationLabel(): string
    {
        return __('Categories');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('CMS');
    }

    protected static ?int $navigationSort = 4;

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
                                // Category Information Section
                                Section::make(__('Category Information'))
                                    ->description(__('Basic category details and settings'))
                                    ->icon(Heroicon::Folder)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Category Name')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (string $context, $state, Set $set) => $context === 'create' ? $set('slug', Str::slug($state)) : null
                                                    )
                                                    ->columnSpan(1),

                                                TextInput::make('slug')
                                                    ->label('URL Slug')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->unique(Category::class, 'slug', ignoreRecord: true)
                                                    ->alphaDash()
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('description')
                                            ->label('Category Description')
                                            ->maxLength(500)
                                            ->rows(3)
                                            ->helperText(__('Brief description of this category'))
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('sort_order')
                                                    ->label('Sort Order')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->helperText(__('Lower numbers appear first'))
                                                    ->columnSpan(1),

                                                Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->helperText(__('Enable or disable this category'))
                                                    ->default(true)
                                                    ->columnSpan(1),
                                            ]),
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
                                    ]),
                            ]),

                        // Sidebar (1/3 width)
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                // Media Section
                                Section::make(__('Category Image'))
                                    ->description(__('Upload category image'))
                                    ->icon(Heroicon::Photo)
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label('Category Image')
                                            ->image()
                                            ->directory('cms/categories')
                                            ->visibility('public')
                                            ->imageEditor()
                                            ->helperText(__('Upload an image for this category'))
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
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('pages_count')
                    ->counts('pages'),
                TextColumn::make('posts_count')
                    ->counts('posts'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('is_active')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
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
