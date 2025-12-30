<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

final class ListRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'list';
    }

    public static function getLabel(): string
    {
        return 'List';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add List')
            ->modalDescription('Create an ordered or unordered list.')
            ->modalWidth('lg')
            ->schema([
                Select::make('type')
                    ->label('List Type')
                    ->options([
                        'ul' => 'Unordered List (bullets)',
                        'ol' => 'Ordered List (numbers)',
                    ])
                    ->default('ul')
                    ->required(),

                Repeater::make('items')
                    ->label('List Items')
                    ->schema([
                        TextInput::make('content')
                            ->label('Item Text')
                            ->required()
                            ->placeholder('Enter list item...')
                            ->maxLength(500),
                    ])
                    ->addActionLabel('Add Item')
                    ->minItems(1)
                    ->maxItems(20)
                    ->defaultItems(3)
                    ->collapsible()
                    ->reorderable()
                    ->itemLabel(fn (array $state): string => $state['content'] ?? 'Empty item'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $type = $config['type'] === 'ol' ? 'Ordered' : 'Unordered';
        $itemCount = count($config['items'] ?? []);

        return "{$type} List ({$itemCount} items)";
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $type = $config['type'] ?? 'ul';
        $items = $config['items'] ?? [];

        if (empty($items)) {
            return '<div style="padding: 20px; border: 2px dashed #d1d5db; text-align: center; color: #6b7280;">No list items</div>';
        }

        $html = "<{$type} style=\"margin: 10px 0; padding-left: 20px; color: #374151;\">";

        foreach ($items as $item) {
            $content = $item['content'] ?? '';
            if ($content) {
                $html .= "<li style=\"margin: 5px 0;\">{$content}</li>";
            }
        }

        $html .= "</{$type}>";

        return $html;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.list', [
            'type' => $config['type'] ?? 'ul',
            'items' => $config['items'] ?? [],
        ])->render();
    }
}
