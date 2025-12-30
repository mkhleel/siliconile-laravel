<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

final class TableBlock
{
    public static function make(): Block
    {
        return Block::make('table')

            ->icon('heroicon-o-table-cells')
            ->schema([
                Toggle::make('has_header')

                    ->default(true)
                    ->live(),
                Repeater::make('rows')
                    ->labelBetweenItems('Row')

                    ->itemLabel('Row')
                    ->schema([
                        Repeater::make('cells')

                            ->itemLabel('Cell')
                            ->schema([
                                Textarea::make('content')

                                    ->rows(2)
                                    ->placeholder('Cell content'),
                            ])
                            ->defaultItems(2)
                            ->minItems(1)
                            ->maxItems(10)
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                    ])
                    ->defaultItems(2)
                    ->minItems(1)
                    ->maxItems(20)
                    ->reorderable()
                    ->collapsible()
                    ->cloneable()
                    ->columnSpanFull(),
                Select::make('style')

                    ->options([
                        'default' => 'Default',
                        'striped' => 'Striped Rows',
                        'bordered' => 'Bordered',
                        'minimal' => 'Minimal',
                    ])
                    ->default('default'),
                Select::make('alignment')

                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('left'),
            ])
            ->columns(2)
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Table';
                }

                $rows = isset($state['rows']) ? count($state['rows']) : 0;
                $cols = 0;

                if ($rows > 0 && isset($state['rows'][0]['cells'])) {
                    $cols = count($state['rows'][0]['cells']);
                }

                return "Table ({$rows}×{$cols})";
            });
    }
}
