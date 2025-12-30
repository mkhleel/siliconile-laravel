<?php

namespace Modules\Core\Filament\Components;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SEO
{
    public static function make(array $only = ['title', 'author', 'description']): array
    {
        return [Section::make(
            Arr::only([
                'title' => TextInput::make('title')
                    ->columnSpan(2),

                'author' => TextInput::make('author')
                    ->columnSpan(2),

                'tags' => TagsInput::make('tags')
                    ->columnSpan(2),

                'description' => Textarea::make('description')
                    ->helperText(function (?string $state): string {
                        return (string) Str::of(strlen($state))
                            ->append(' / ')
                            ->append(160 .' ')
                            ->append(Str::of(__('Characters'))->lower());
                    })
                    ->reactive()
                    ->columnSpan(2),
            ], $only)
        )
            ->afterStateHydrated(function (Section $component, ?Model $record) use ($only): void {
                $component->getChildComponentContainer()->fill(
                    $record?->seo?->only($only) ?: []
                );
            })
            ->description(__('SEO Details, If you leave empty, it will be generated automatically.'))
            ->statePath('seo')
            ->dehydrated(false)
            ->saveRelationshipsUsing(function (Model $record, array $state) use ($only): void {
                $state = collect($state)->only($only)->map(fn ($value) => $value ?: null)->all();
                if ($record->seo && $record->seo->exists) {
                    $record->seo->update($state);
                } else {
                    $record->seo()->create($state);
                }
            })];
    }
}
