<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

final class ListBlock
{
    public static function make(): Block
    {
        return Block::make('list')

            ->icon(Heroicon::OutlinedListBullet)
            ->schema([
                Select::make('type')

                    ->options([
                        'ul' => 'Bulleted List',
                        'ol' => 'Numbered List',
                    ])
                    ->default('ul')
                    ->required()
                    ->live(),
                Repeater::make('items')

                    ->schema([
                        TextInput::make('content')

                            ->required()
                            ->placeholder('Enter list item'),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->maxItems(20)
                    ->reorderable()
                    ->collapsible()
                    ->cloneable()
                    ->columnSpanFull(),
            ])
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'List';
                }

                $type = $state['type'] === 'ol' ? 'Numbered' : 'Bulleted';
                $count = isset($state['items']) ? count($state['items']) : 0;

                return "{$type} List ({$count} items)";
            });
    }
}
