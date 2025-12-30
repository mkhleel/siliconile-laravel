<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

final class HeadingRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'heading';
    }

    public static function getLabel(): string
    {
        return 'Heading';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Heading')
            ->modalDescription('Create a heading with the desired level and content.')
            ->modalWidth('lg')
            ->schema([
                TextInput::make('content')
                    ->label('Heading Text')
                    ->required()
                    ->placeholder('Enter your heading')
                    ->maxLength(255),

                Select::make('level')
                    ->label('Heading Level')
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
                    ->label('Text Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('left'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $level = strtoupper($config['level'] ?? 'H2');
        $content = $config['content'] ?? 'Untitled heading';

        return "{$level}: ".Str::limit($content, 40);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $level = $config['level'] ?? 'h2';
        $content = $config['content'] ?? 'Untitled heading';
        $alignment = $config['alignment'] ?? 'left';

        $style = match ($alignment) {
            'center' => 'text-align: center;',
            'right' => 'text-align: right;',
            default => 'text-align: left;',
        };

        return "<{$level} style=\"{$style} margin: 10px 0; color: #374151;\">{$content}</{$level}>";
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.heading', [
            'content' => $config['content'] ?? '',
            'level' => $config['level'] ?? 'h2',
            'alignment' => $config['alignment'] ?? 'left',
        ])->render();
    }
}
