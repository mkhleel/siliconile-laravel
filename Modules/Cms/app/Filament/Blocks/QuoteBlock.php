<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

final class QuoteBlock
{
    public static function make(): Block
    {
        return Block::make('quote')

            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->schema([
                Textarea::make('content')

                    ->required()
                    ->rows(3)
                    ->placeholder('Enter the quote text'),
                TextInput::make('author')

                    ->placeholder('Quote author (optional)'),
                TextInput::make('source')

                    ->placeholder('Source or publication (optional)'),
                Select::make('style')

                    ->options([
                        'default' => 'Default',
                        'highlight' => 'Highlighted',
                        'minimal' => 'Minimal',
                        'bordered' => 'Bordered',
                    ])
                    ->default('default'),
                Select::make('alignment')

                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('center'),
                ColorPicker::make('accent_color')

                    ->default('#3b82f6'),
            ])
            ->columns(2)
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Quote';
                }

                $content = $state['content'] ?? 'Empty quote';
                $author = isset($state['author']) && ! empty($state['author'])
                    ? ' - '.$state['author']
                    : '';

                return 'Quote: "'.Str::limit(strip_tags($content), 30).'"'.$author;
            });
    }
}
