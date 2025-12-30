<?php

declare(strict_types=1);

namespace Modules\Core\Filament\Components;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Reusable SEO form component for Filament resources.
 *
 * Usage: SEO::make() or SEO::make(['title', 'description'])
 */
class SEO
{
    /**
     * Create SEO form fields section.
     *
     * @param array<string> $only Fields to include: 'title', 'author', 'tags', 'description'
     * @return array<Section>
     */
    public static function make(array $only = ['title', 'author', 'description']): array
    {
        return [Section::make('SEO')
            ->schema(
                Arr::only([
                    'title' => TextInput::make('title')
                        ->label(__('Meta Title'))
                        ->maxLength(60)
                        ->helperText(__('Recommended: 50-60 characters'))
                        ->columnSpan(2),

                    'author' => TextInput::make('author')
                        ->label(__('Author'))
                        ->columnSpan(2),

                    'tags' => TagsInput::make('tags')
                        ->label(__('Keywords'))
                        ->separator(',')
                        ->columnSpan(2),

                    'description' => Textarea::make('description')
                        ->label(__('Meta Description'))
                        ->maxLength(160)
                        ->helperText(function (?string $state): string {
                            $length = strlen((string) $state);

                            return "{$length} / 160 " . Str::lower(__('characters'));
                        })
                        ->live(onBlur: true)
                        ->rows(3)
                        ->columnSpan(2),
                ], $only)
            )
            ->afterStateHydrated(function (Section $component, ?Model $record) use ($only): void {
                $container = $component->getChildComponentContainer();
                if ($container && $record?->seo) {
                    $container->fill($record->seo->only($only));
                }
            })
            ->description(__('SEO Details. Leave empty for auto-generation.'))
            ->statePath('seo')
            ->dehydrated(false)
            ->collapsible()
            ->collapsed()
            ->saveRelationshipsUsing(function (Model $record, array $state) use ($only): void {
                $state = collect($state)
                    ->only($only)
                    ->map(fn ($value) => $value ?: null)
                    ->all();

                if ($record->seo?->exists) {
                    $record->seo->update($state);
                } else {
                    $record->seo()->create($state);
                }
            })];
    }
}
