<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

final class HeadingBlock
{
    public static function make(): Block
    {
        return Block::make('heading')

            ->icon('heroicon-o-h1')
            ->schema([
                TextInput::make('content')

                    ->required()
                    ->live(onBlur: true)
                    ->placeholder('Enter your heading'),
                Select::make('level')

                    ->options([
                        'h1' => 'H1 - Main Title',
                        'h2' => 'H2 - Section Title',
                        'h3' => 'H3 - Subsection',
                        'h4' => 'H4 - Sub-subsection',
                        'h5' => 'H5 - Minor Heading',
                        'h6' => 'H6 - Smallest Heading',
                    ])
                    ->default('h2')
                    ->required(),
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
                    return 'Heading';
                }

                $level = $state['level'] ?? 'H2';
                $content = $state['content'] ?? 'Untitled heading';

                return strtoupper($level).': '.Str::limit($content, 40);
            });
    }
}
