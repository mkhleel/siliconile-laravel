<?php

namespace Modules\Core\Filament\Resources\Languages;

use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Modules\Core\Filament\Pages\EditLanguageTranslations;
use Modules\Core\Filament\Resources\Languages\Pages\CreateLanguage;
use Modules\Core\Filament\Resources\Languages\Pages\ListLanguages;
use Modules\Core\Helpers\LanguageFlags;
use Modules\Core\Models\Localization\Language;

class LanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('Languages');
    }

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('iso')

                    ->formatStateUsing(fn ($record) => '<img src="'.LanguageFlags::getFlag($record->iso, true).'" alt="'.$record->name.'" width="32" height="32" />'
                    )
                    ->html()
                    ->tooltip(fn ($record) => __('Click to edit translations for :language', ['language' => $record->name]))
                    ->alignLeft(),
                TextColumn::make('name')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->searchable(),
                // activate
                ToggleColumn::make('is_activated')->alignRight(),
                // flag icon from LanguageFlags helper

            ])
            ->recordActions([
                Action::make('edit')
                    ->url(fn ($record) => EditLanguageTranslations::getUrl(['language' => $record->iso])),
            ]);
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
            'index' => ListLanguages::route('/'),
            'create' => CreateLanguage::route('/create'),
        ];
    }
}
