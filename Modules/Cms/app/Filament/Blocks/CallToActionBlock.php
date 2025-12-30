<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

final class CallToActionBlock
{
    public static function make(): Block
    {
        return Block::make('cta')

            ->icon('heroicon-o-megaphone')
            ->schema([
                TextInput::make('title')

                    ->required()
                    ->placeholder('Take Action Now!'),
                Textarea::make('description')

                    ->rows(3)
                    ->placeholder('Compelling description to encourage action'),
                TextInput::make('button_text')

                    ->required()
                    ->placeholder('Get Started'),
                TextInput::make('button_url')

                    ->url()
                    ->required()
                    ->placeholder('https://example.com'),
                FileUpload::make('background_image')

                    ->image()
                    ->directory('cms/cta')
                    ->visibility('public'),
                ColorPicker::make('background_color')

                    ->default('#3b82f6'),
                ColorPicker::make('text_color')

                    ->default('#ffffff'),
                Select::make('style')

                    ->options([
                        'default' => 'Default',
                        'minimal' => 'Minimal',
                        'gradient' => 'Gradient',
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
            ])
            ->columns(2)
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Call to Action';
                }

                $title = $state['title'] ?? 'Call to Action';

                return 'CTA: '.Str::limit($title, 40);
            });
    }
}
