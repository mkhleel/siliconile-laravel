<?php

declare(strict_types=1);

namespace Modules\SpaceBooking\Filament\Resources;

use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
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
                Section::make('Amenity Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                $set('slug', \Str::slug($state ?? ''));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),

                        Forms\Components\TextInput::make('icon')
                            ->placeholder('heroicon-o-wifi')
                            ->helperText('Use Heroicon names like heroicon-o-wifi'),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(500)
                            ->rows(2),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),
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
