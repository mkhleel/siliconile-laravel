<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;

final class ButtonBlock
{
    public static function make(): Block
    {
        return Block::make('button')

            ->icon(Heroicon::OutlinedCursorArrowRays)
            ->schema([
                TextInput::make('text')

                    ->required()
                    ->placeholder('Click Here'),
                TextInput::make('url')

                    ->url()
                    ->required()
                    ->placeholder('https://example.com'),
                Select::make('style')

                    ->options([
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'outline' => 'Outline',
                        'ghost' => 'Ghost',
                        'link' => 'Link Style',
                    ])
                    ->default('primary'),
                Select::make('size')

                    ->options([
                        'sm' => 'Small',
                        'md' => 'Medium',
                        'lg' => 'Large',
                        'xl' => 'Extra Large',
                    ])
                    ->default('md'),
                Select::make('alignment')

                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('left'),
                Toggle::make('new_tab')

                    ->default(false),
                TextInput::make('icon')

                    ->placeholder('heroicon-o-arrow-right')
                    ->helperText('Heroicon icon name'),
            ])
            ->columns(2)
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Button';
                }

                $text = $state['text'] ?? 'Button';
                $style = $state['style'] ?? 'primary';

                return "Button: {$text} ({$style})";
            });
    }
}
