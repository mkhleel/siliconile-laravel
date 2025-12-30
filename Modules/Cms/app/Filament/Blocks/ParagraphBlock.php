<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Str;

final class ParagraphBlock
{
    public static function make(): Block
    {
        return Block::make('paragraph')

            ->icon('heroicon-o-bars-3-bottom-left')
            ->schema([
                Textarea::make('content')

                    ->required()
                    ->rows(4)
                    ->live(onBlur: true)
                    ->placeholder('Enter your paragraph content'),
                Select::make('alignment')

                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                        'justify' => 'Justify',
                    ])
                    ->default('left'),
            ])
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Paragraph';
                }

                $content = $state['content'] ?? 'Empty paragraph';

                return 'Paragraph: '.Str::limit(strip_tags($content), 50);
            });
    }
}
