<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Resources\Tags;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\ColorPicker;
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
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Modules\Cms\Filament\Resources\Tags\Pages\CreateTag;
use Modules\Cms\Filament\Resources\Tags\Pages\EditTag;
use Modules\Cms\Filament\Resources\Tags\Pages\ListTags;
use Modules\Cms\Filament\Resources\Tags\Pages\ViewTag;
use Modules\Cms\Models\Tag;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Tag;

    public static function getNavigationLabel(): string
    {
        return __('Tags');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('CMS');
    }

    protected static ?int $navigationSort = 5;

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
                                // Tag Information Section
                                Section::make(__('Tag Information'))
                                    ->description(__('Basic tag details and settings'))
                                    ->icon(Heroicon::Tag)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->label('Tag Name')
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
                                                    ->unique(Tag::class, 'slug', ignoreRecord: true)
                                                    ->alphaDash()
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('description')
                                            ->label('Tag Description')
                                            ->maxLength(500)
                                            ->rows(3)
                                            ->helperText(__('Brief description of this tag'))
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->schema([
                                                ColorPicker::make('color')
                                                    ->label('Tag Color')
                                                    ->default('#3b82f6')
                                                    ->helperText(__('Choose a color for this tag'))
                                                    ->columnSpan(1),

                                                Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->helperText(__('Enable or disable this tag'))
                                                    ->default(true)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // Sidebar (1/3 width)
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                // Tag Preview Section
                                Section::make(__('Tag Preview'))
                                    ->description(__('Preview how this tag will appear'))
                                    ->icon(Heroicon::Eye)
                                    ->schema([
                                        // This could be a custom component to show tag preview
                                        // For now, we'll leave it empty as it would require custom components
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
                ColorColumn::make('color'),
                IconColumn::make('is_active')
                    ->boolean(),
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
            ->defaultSort('name');
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
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'view' => ViewTag::route('/{record}'),
            'edit' => EditTag::route('/{record}/edit'),
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
