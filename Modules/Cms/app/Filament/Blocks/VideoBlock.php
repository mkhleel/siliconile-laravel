<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

final class VideoBlock
{
    public static function make(): Block
    {
        return Block::make('video')

            ->icon(Heroicon::OutlinedPlay)
            ->schema([
                TextInput::make('url')

                    ->url()
                    ->required()
                    ->placeholder('https://youtube.com/watch?v=... or https://vimeo.com/...')
                    ->helperText('Supports YouTube, Vimeo, and direct video URLs'),
                TextInput::make('title')

                    ->placeholder('Optional video title'),
                Textarea::make('description')

                    ->rows(2)
                    ->placeholder('Optional video description'),
                Select::make('aspect_ratio')

                    ->options([
                        '16:9' => '16:9 (Widescreen)',
                        '4:3' => '4:3 (Standard)',
                        '1:1' => '1:1 (Square)',
                        '21:9' => '21:9 (Ultra-wide)',
                    ])
                    ->default('16:9'),
                Select::make('alignment')

                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('center'),
                Select::make('width')

                    ->options([
                        'small' => 'Small (400px)',
                        'medium' => 'Medium (600px)',
                        'large' => 'Large (800px)',
                        'full' => 'Full Width',
                    ])
                    ->default('large'),
            ])
            ->columns(2)
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Video';
                }

                if (isset($state['title']) && ! empty($state['title'])) {
                    return 'Video: '.Str::limit($state['title'], 40);
                }

                if (isset($state['url']) && ! empty($state['url'])) {
                    return 'Video: '.Str::limit($state['url'], 40);
                }

                return 'Video';
            });
    }
}
