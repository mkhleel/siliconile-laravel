<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

final class ImageBlock
{
    public static function make(): Block
    {
        return Block::make('image')

            ->icon(Heroicon::OutlinedPhoto)
            ->schema([
                FileUpload::make('url')

                    ->image()
                    ->directory('cms/content')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '4:3',
                        '1:1',
                        null,
                    ])
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('alt')

                    ->required()
                    ->placeholder('Describe the image for accessibility'),
                TextInput::make('title')

                    ->placeholder('Optional title for hover text'),
                Textarea::make('caption')

                    ->rows(2)
                    ->placeholder('Optional caption displayed below image'),
                Select::make('alignment')

                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('center'),
                Select::make('size')

                    ->options([
                        'small' => 'Small (25%)',
                        'medium' => 'Medium (50%)',
                        'large' => 'Large (75%)',
                        'full' => 'Full Width (100%)',
                    ])
                    ->default('large'),
            ])
            ->columns(2)
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Image';
                }

                if (isset($state['alt']) && ! empty($state['alt'])) {
                    return 'Image: '.Str::limit($state['alt'], 40);
                }

                return 'Image';
            });
    }
}
