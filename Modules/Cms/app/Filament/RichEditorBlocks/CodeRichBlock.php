<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

final class CodeRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'code';
    }

    public static function getLabel(): string
    {
        return 'Code Block';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Code Block')
            ->modalDescription('Add syntax-highlighted code with language support.')
            ->modalWidth('xl')
            ->schema([
                Textarea::make('content')
                    ->label('Code Content')
                    ->required()
                    ->placeholder('Enter your code...')
                    ->rows(10)
                    ->maxLength(5000),

                Select::make('language')
                    ->label('Programming Language')
                    ->options([
                        'text' => 'Plain Text',
                        'html' => 'HTML',
                        'css' => 'CSS',
                        'javascript' => 'JavaScript',
                        'typescript' => 'TypeScript',
                        'php' => 'PHP',
                        'python' => 'Python',
                        'java' => 'Java',
                        'csharp' => 'C#',
                        'cpp' => 'C++',
                        'go' => 'Go',
                        'rust' => 'Rust',
                        'sql' => 'SQL',
                        'json' => 'JSON',
                        'xml' => 'XML',
                        'yaml' => 'YAML',
                        'bash' => 'Bash',
                        'powershell' => 'PowerShell',
                        'markdown' => 'Markdown',
                    ])
                    ->default('text')
                    ->searchable(),

                TextInput::make('filename')
                    ->label('Filename (Optional)')
                    ->placeholder('e.g., app.js, config.php')
                    ->maxLength(255),

                Select::make('theme')
                    ->label('Theme')
                    ->options([
                        'default' => 'Default',
                        'dark' => 'Dark',
                        'light' => 'Light',
                    ])
                    ->default('default'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $language = $config['language'] ?? 'text';
        $filename = $config['filename'] ?? '';

        $label = "Code ({$language})";

        if ($filename) {
            $label .= ": {$filename}";
        }

        return $label;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $content = $config['content'] ?? 'Your code will appear here...';
        $language = $config['language'] ?? 'text';
        $filename = $config['filename'] ?? '';
        $theme = $config['theme'] ?? 'default';

        $bgColor = match ($theme) {
            'dark' => '#1f2937',
            'light' => '#f9fafb',
            default => '#f3f4f6',
        };

        $textColor = match ($theme) {
            'dark' => '#e5e7eb',
            'light' => '#374151',
            default => '#1f2937',
        };

        $html = '<div style="margin: 15px 0; border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db;">';

        if ($filename) {
            $html .= '<div style="background: #e5e7eb; padding: 8px 16px; font-size: 14px; color: #374151; font-weight: 500;">';
            $html .= "<span style=\"margin-right: 10px;\">📄</span>{$filename}";
            $html .= '</div>';
        }

        $html .= "<div style=\"background: {$bgColor}; padding: 16px; position: relative;\">";
        $html .= "<div style=\"position: absolute; top: 8px; right: 12px; font-size: 12px; color: #9ca3af; text-transform: uppercase;\">{$language}</div>";
        $html .= "<pre style=\"margin: 0; color: {$textColor}; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.5; overflow-x: auto;\">";
        $html .= '<code>'.htmlspecialchars(Str::limit($content, 300)).'</code>';
        $html .= '</pre>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.code', [
            'content' => $config['content'] ?? '',
            'language' => $config['language'] ?? 'text',
            'filename' => $config['filename'] ?? '',
            'theme' => $config['theme'] ?? 'default',
        ])->render();
    }
}
