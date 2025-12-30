<?php

namespace Modules\Cms\Filament\Resources\Navigations;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Modules\Cms\Filament\Resources\Navigations\Pages\CreateNavigation;
use Modules\Cms\Filament\Resources\Navigations\Pages\EditNavigation;
use Modules\Cms\Filament\Resources\Navigations\Pages\ListNavigations;
use Modules\Cms\Models\Navigation;

class NavigationResource extends Resource
{
    use Translatable;

    public static function getDefaultTranslatableLocale(): string
    {
        return app()->getLocale();
    }

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Bars3;

    protected static ?string $model = Navigation::class;

    protected static ?string $slug = 'navigations';

    public static function getNavigationLabel(): string
    {
        return __('Navigations');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function form(Schema $schema): Schema
    {
        $routeList = app('hook')->apply('navItems', [url('/') => 'Home']);

        return $schema
            ->components([
                Grid::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        // Main Content Area (2/3 width)
                        Group::make()
                            ->columnSpan(3)
                            ->schema([
                                // Navigation Information Section
                                Section::make(__('Navigation Information'))
                                    ->description(__('Basic navigation details and settings'))
                                    ->icon(Heroicon::Bars3)
                                    ->compact()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('key')
                                            ->label('Navigation Key')
                                            ->required()
                                            ->maxLength(255)
                                            ->helperText(__('Unique identifier for this navigation menu')),

                                        Select::make('location')
                                            ->label('Location')
                                            ->options([
                                                'header' => 'Header',
                                                'footer' => 'Footer',
                                            ])
                                            ->required()
                                            ->helperText(__('Where this navigation will be displayed')),

                                        Toggle::make('activated')
                                            ->label('Activated')
                                            ->hidden()
                                            ->helperText(__('Enable or disable this navigation menu'))
                                            ->default(true),                                    ]),

                                // Navigation Items Section
                                Section::make(__('Navigation Items'))
                                    ->description(__('Configure menu items and structure'))
                                    ->icon(Heroicon::ListBullet)
                                    ->schema([
                                        Repeater::make('items')
                                            ->label('Menu Items')
                                            ->schema([
                                                Select::make('route')
                                                    ->inlineLabel()
                                                    ->helperText(__('If page is not found, please clear the cache from general settings'))
                                                    ->live()
                                                    ->searchable()
                                                    ->afterStateUpdated(function (Select $component, Get $get, Set $set, $state) {
                                                        $set('title', $component->getOptions()[$state] ?? null);
                                                        $set('url', $state);
                                                    })
                                                    ->options($routeList)
                                                    ->columnSpanFull(),

                                                TextInput::make('title')
                                                    ->inlineLabel()
                                                    ->label('Title')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->columnSpan(1),

                                                TextInput::make('url')
                                                    ->inlineLabel()
                                                    ->label('URL')
                                                    ->maxLength(255)
                                                    ->columnSpan(1),

                                                Toggle::make('blank')
                                                    ->inlineLabel()
                                                    ->label('Open in New Tab')
                                                    ->helperText(__('Open link in a new tab/window'))
                                                    ->columnSpanFull(),
                                                Repeater::make('items')
                                                    ->table([
                                                        // Define your table columns here
                                                        Repeater\TableColumn::make('title')->hiddenHeaderLabel(),
                                                        Repeater\TableColumn::make('url')->hiddenHeaderLabel(),
                                                        Repeater\TableColumn::make('blank')->hiddenHeaderLabel(),
                                                    ])
                                                    ->label('Sub Items')
                                                    ->default([])
                                                    ->compact()
                                                    ->schema([
                                                        Select::make('route')
                                                            ->label('Route')
                                                            ->live()
                                                            ->searchable()
                                                            ->afterStateUpdated(function (Select $component, Get $get, Set $set, $state) {
                                                                $set('title', $component->getOptions()[$state] ?? null);
                                                                $set('url', $state);
                                                            })
                                                            ->options($routeList)
                                                            ->columnSpanFull(),

                                                        TextInput::make('title')
                                                            ->label('Title')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->columnSpan(1),

                                                        TextInput::make('url')
                                                            ->label('URL')
                                                            ->maxLength(255)
                                                            ->columnSpan(1),
                                                    ])
                                                    ->collapsible()
                                                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                                    ->columnSpanFull(),
                                            ])
                                            ->reorderable()
                                            ->collapsible()
                                            ->collapsed()
                                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
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
                TextColumn::make('key')->sortable(),
                ToggleColumn::make('activated')->alignRight(),
            ])
            ->filters([
                Filter::make('activated')
                    ->query(fn (Builder $query): Builder => $query->where('activated', true)),

            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNavigations::route('/'),
            'create' => CreateNavigation::route('/create'),
            'edit' => EditNavigation::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
