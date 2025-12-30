<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

final class QuoteRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'quote';
    }

    public static function getLabel(): string
    {
        return 'Quote';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Quote')
            ->modalDescription('Create a styled quote with optional attribution.')
            ->modalWidth('lg')
            ->schema([
                Textarea::make('content')
                    ->label('Quote Content')
                    ->required()
                    ->placeholder('Enter the quote...')
                    ->rows(4)
                    ->maxLength(1000),

                TextInput::make('author')
                    ->label('Author (Optional)')
                    ->placeholder('Who said this quote?')
                    ->maxLength(255),

                TextInput::make('source')
                    ->label('Source (Optional)')
                    ->placeholder('Book, website, etc.')
                    ->maxLength(255),

                Select::make('style')
                    ->label('Quote Style')
                    ->options([
                        'default' => 'Default',
                        'emphasis' => 'Emphasis',
                        'minimal' => 'Minimal',
                        'bordered' => 'Bordered',
                    ])
                    ->default('default'),

                Select::make('alignment')
                    ->label('Alignment')
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
        $content = $config['content'] ?? 'Empty quote';
        $author = $config['author'] ?? '';

        $label = 'Quote: '.Str::limit($content, 40);

        if ($author) {
            $label .= " - {$author}";
        }

        return $label;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $content = $config['content'] ?? 'Your quote will appear here...';
        $author = $config['author'] ?? '';
        $source = $config['source'] ?? '';
        $alignment = $config['alignment'] ?? 'left';

        $alignStyle = match ($alignment) {
            'center' => 'text-align: center;',
            'right' => 'text-align: right;',
            default => 'text-align: left;',
        };

        $html = "<blockquote style=\"{$alignStyle} margin: 20px 0; padding: 15px; border-left: 4px solid #3b82f6; background: #f8fafc; font-style: italic; color: #374151;\">";
        $html .= "<p style=\"margin: 0; font-size: 16px; line-height: 1.6;\">\"{$content}\"</p>";

        if ($author || $source) {
            $html .= '<footer style="margin-top: 10px; font-size: 14px; color: #6b7280; font-style: normal;">';
            $html .= '— ';
            if ($author) {
                $html .= $author;
            }
            if ($source) {
                $html .= $author ? ", {$source}" : $source;
            }
            $html .= '</footer>';
        }

        $html .= '</blockquote>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.quote', [
            'content' => $config['content'] ?? '',
            'author' => $config['author'] ?? '',
            'source' => $config['source'] ?? '',
            'style' => $config['style'] ?? 'default',
            'alignment' => $config['alignment'] ?? 'left',
        ])->render();
    }
}
