<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\SpaceBooking\Filament\Resources\ResourceAmenityResource\Pages;
use Modules\SpaceBooking\Models\ResourceAmenity;
use UnitEnum;

/**
 * Filament Resource for managing Resource Amenities.
 *
 * Trace: SRS-FR-BOOKING-003 (Amenity Management)
 */
class ResourceAmenityResource extends Resource
{
    protected static ?string $model = ResourceAmenity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Space Booking';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Amenities';

    protected static ?string $modelLabel = 'Amenity';

    protected static ?string $pluralModelLabel = 'Amenities';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Amenity Details'))
                    ->description(__('Configure amenity name and identifier'))
                    ->icon('heroicon-o-sparkles')
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('Amenity Name'))
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                    $set('slug', \Str::slug($state ?? ''));
                                }),

                            Forms\Components\TextInput::make('slug')
                                ->label(__('Slug'))
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->alphaDash(),
                        ]),
                    ]),

                Section::make(__('Display Settings'))
                    ->description(__('Icon and description for display'))
                    ->icon('heroicon-o-paint-brush')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)->schema([
                            Forms\Components\TextInput::make('icon')
                                ->label(__('Icon'))
                                ->placeholder('heroicon-o-wifi')
                                ->helperText(__('Use Heroicon names like heroicon-o-wifi')),

                            Forms\Components\Toggle::make('is_active')
                                ->label(__('Active'))
                                ->default(true)
                                ->helperText(__('Inactive amenities will not be shown')),
                        ]),

                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->maxLength(500)
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('Sorting'))
                    ->icon('heroicon-o-arrows-up-down')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label(__('Sort Order'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText(__('Lower numbers appear first')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('icon')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('resources_count')
                    ->counts('resources')
                    ->label('Used In')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResourceAmenities::route('/'),
            'create' => Pages\CreateResourceAmenity::route('/create'),
            'edit' => Pages\EditResourceAmenity::route('/{record}/edit'),
        ];
    }
}
