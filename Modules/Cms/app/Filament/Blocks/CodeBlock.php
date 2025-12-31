<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

final class CodeBlock
{
    public static function make(): Block
    {
        return Block::make('code')

            ->icon(Heroicon::OutlinedCodeBracket)
            ->schema([
                Textarea::make('content')

                    ->required()
                    ->rows(8)
                    ->placeholder('Enter your code here...')
                    ->columnSpanFull(),
                Select::make('language')

                    ->options([
                        'php' => 'PHP',
                        'javascript' => 'JavaScript',
                        'typescript' => 'TypeScript',
                        'python' => 'Python',
                        'java' => 'Java',
                        'css' => 'CSS',
                        'html' => 'HTML',
                        'sql' => 'SQL',
                        'json' => 'JSON',
                        'xml' => 'XML',
                        'yaml' => 'YAML',
                        'bash' => 'Bash',
                        'powershell' => 'PowerShell',
                        'dockerfile' => 'Dockerfile',
                        'markdown' => 'Markdown',
                        'text' => 'Plain Text',
                    ])
                    ->default('text')
                    ->searchable(),
                Select::make('theme')

                    ->options([
                        'dark' => 'Dark',
                        'light' => 'Light',
                    ])
                    ->default('dark'),
            ])
            ->columns(2)
            ->label(function (?array $state): string {
                if ($state === null) {
                    return 'Code Block';
                }

                $language = isset($state['language']) ? strtoupper($state['language']) : 'CODE';
                $lines = isset($state['content']) ? count(explode("\n", $state['content'])) : 0;

                return "{$language} Code ({$lines} lines)";
            });
    }
}
