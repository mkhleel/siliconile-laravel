<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

final class ButtonRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'button';
    }

    public static function getLabel(): string
    {
        return 'Button';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Button')
            ->modalDescription('Create a styled button link.')
            ->modalWidth('lg')
            ->schema([
                TextInput::make('text')
                    ->label('Button Text')
                    ->required()
                    ->placeholder('Click here')
                    ->maxLength(100),

                TextInput::make('url')
                    ->label('Button URL')
                    ->url()
                    ->required()
                    ->placeholder('https://example.com'),

                Toggle::make('new_tab')
                    ->label('Open in new tab')
                    ->default(false),

                Select::make('style')
                    ->label('Button Style')
                    ->options([
                        'primary' => 'Primary',
                        'secondary' => 'Secondary',
                        'success' => 'Success',
                        'danger' => 'Danger',
                        'outline' => 'Outline',
                        'custom' => 'Custom Color',
                    ])
                    ->default('primary')
                    ->live(),

                ColorPicker::make('custom_color')
                    ->label('Custom Color')
                    ->visible(fn ($get) => $get('style') === 'custom')
                    ->default('#3b82f6'),

                Select::make('size')
                    ->label('Size')
                    ->options([
                        'small' => 'Small',
                        'medium' => 'Medium',
                        'large' => 'Large',
                    ])
                    ->default('medium'),

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
        $text = $config['text'] ?? 'Button';

        return "Button: {$text}";
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $text = $config['text'] ?? 'Button';
        $style = $config['style'] ?? 'primary';
        $size = $config['size'] ?? 'medium';
        $alignment = $config['alignment'] ?? 'left';

        $alignStyle = match ($alignment) {
            'center' => 'text-align: center;',
            'right' => 'text-align: right;',
            default => 'text-align: left;',
        };

        $bgColor = match ($style) {
            'secondary' => '#6b7280',
            'success' => '#10b981',
            'danger' => '#ef4444',
            'outline' => 'transparent',
            'custom' => $config['custom_color'] ?? '#3b82f6',
            default => '#3b82f6',
        };

        $textColor = $style === 'outline' ? '#3b82f6' : 'white';
        $border = $style === 'outline' ? 'border: 2px solid #3b82f6;' : '';

        $padding = match ($size) {
            'small' => 'padding: 8px 16px; font-size: 14px;',
            'large' => 'padding: 16px 32px; font-size: 18px;',
            default => 'padding: 12px 24px; font-size: 16px;',
        };

        $html = "<div style=\"{$alignStyle} margin: 10px 0;\">";
        $html .= "<button style=\"background: {$bgColor}; color: {$textColor}; {$padding} {$border} border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block;\">{$text}</button>";
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.button', [
            'text' => $config['text'] ?? '',
            'url' => $config['url'] ?? '',
            'new_tab' => $config['new_tab'] ?? false,
            'style' => $config['style'] ?? 'primary',
            'custom_color' => $config['custom_color'] ?? '#3b82f6',
            'size' => $config['size'] ?? 'medium',
            'alignment' => $config['alignment'] ?? 'left',
        ])->render();
    }
}
