<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;

final class ParagraphRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'paragraph';
    }

    public static function getLabel(): string
    {
        return 'Rich Paragraph';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Rich Paragraph')
            ->modalDescription('Create a paragraph with rich text formatting.')
            ->modalWidth('2xl')
            ->schema([
                RichEditor::make('content')
                    ->label('Paragraph Content')
                    ->required()
                    ->placeholder('Enter your paragraph content...')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'subscript',
                        'superscript',
                        'link',
                        'blockquote',
                        'bulletList',
                        'orderedList',
                    ])
                    ->maxLength(2000),

                Select::make('alignment')
                    ->label('Text Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                        'justify' => 'Justify',
                    ])
                    ->default('left'),

                Select::make('size')
                    ->label('Text Size')
                    ->options([
                        'small' => 'Small',
                        'normal' => 'Normal',
                        'large' => 'Large',
                    ])
                    ->default('normal'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $content = strip_tags($config['content'] ?? 'Empty paragraph');

        return 'Rich Paragraph: '.Str::limit($content, 50);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $content = $config['content'] ?? 'Your paragraph content will appear here...';
        $alignment = $config['alignment'] ?? 'left';
        $size = $config['size'] ?? 'normal';

        $style = match ($alignment) {
            'center' => 'text-align: center;',
            'right' => 'text-align: right;',
            'justify' => 'text-align: justify;',
            default => 'text-align: left;',
        };

        $fontSize = match ($size) {
            'small' => 'font-size: 14px;',
            'large' => 'font-size: 18px;',
            default => 'font-size: 16px;',
        };

        return "<div style=\"{$style} {$fontSize} margin: 10px 0; color: #374151; line-height: 1.6;\">{$content}</div>";
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.paragraph', [
            'content' => $config['content'] ?? '',
            'alignment' => $config['alignment'] ?? 'left',
            'size' => $config['size'] ?? 'normal',
        ])->render();
    }
}
