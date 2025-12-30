<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;

final class CallToActionRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'call_to_action';
    }

    public static function getLabel(): string
    {
        return 'Call to Action';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Call to Action')
            ->modalDescription('Create an engaging call to action section.')
            ->modalWidth('xl')
            ->schema([
                TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->placeholder('Enter compelling title...')
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Description')
                    ->placeholder('Add supporting text...')
                    ->rows(3)
                    ->maxLength(500),

                TextInput::make('button_text')
                    ->label('Button Text')
                    ->required()
                    ->placeholder('Get Started, Learn More, etc.')
                    ->maxLength(100),

                TextInput::make('button_url')
                    ->label('Button URL')
                    ->url()
                    ->required()
                    ->placeholder('https://example.com'),

                Toggle::make('open_in_new_tab')
                    ->label('Open link in new tab')
                    ->default(false),

                Select::make('style')
                    ->label('Style')
                    ->options([
                        'default' => 'Default',
                        'primary' => 'Primary (Blue)',
                        'success' => 'Success (Green)',
                        'warning' => 'Warning (Yellow)',
                        'danger' => 'Danger (Red)',
                        'custom' => 'Custom Color',
                    ])
                    ->default('primary')
                    ->live(),

                ColorPicker::make('custom_color')
                    ->label('Custom Background Color')
                    ->visible(fn ($get) => $get('style') === 'custom')
                    ->default('#3b82f6'),

                Select::make('alignment')
                    ->label('Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('center'),

                Select::make('size')
                    ->label('Size')
                    ->options([
                        'small' => 'Small',
                        'medium' => 'Medium',
                        'large' => 'Large',
                    ])
                    ->default('medium'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $title = $config['title'] ?? 'Call to Action';

        return 'CTA: '.Str::limit($title, 40);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $title = $config['title'] ?? 'Your Call to Action';
        $description = $config['description'] ?? '';
        $buttonText = $config['button_text'] ?? 'Click Here';
        $alignment = $config['alignment'] ?? 'center';
        $style = $config['style'] ?? 'primary';

        $alignStyle = match ($alignment) {
            'left' => 'text-align: left;',
            'right' => 'text-align: right;',
            default => 'text-align: center;',
        };

        $bgColor = match ($style) {
            'success' => '#10b981',
            'warning' => '#f59e0b',
            'danger' => '#ef4444',
            'custom' => $config['custom_color'] ?? '#3b82f6',
            default => '#3b82f6',
        };

        $html = "<div style=\"{$alignStyle} margin: 20px 0; padding: 30px; background: linear-gradient(135deg, {$bgColor}15, {$bgColor}05); border: 1px solid {$bgColor}30; border-radius: 8px;\">";
        $html .= "<h3 style=\"margin: 0 0 10px 0; color: #1f2937; font-size: 24px; font-weight: bold;\">{$title}</h3>";

        if ($description) {
            $html .= "<p style=\"margin: 0 0 20px 0; color: #6b7280; font-size: 16px; line-height: 1.5;\">{$description}</p>";
        }

        $html .= "<button style=\"background: {$bgColor}; color: white; padding: 12px 24px; border: none; border-radius: 6px; font-weight: 600; font-size: 16px; cursor: pointer;\">{$buttonText}</button>";
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.call-to-action', [
            'title' => $config['title'] ?? '',
            'description' => $config['description'] ?? '',
            'button_text' => $config['button_text'] ?? '',
            'button_url' => $config['button_url'] ?? '',
            'open_in_new_tab' => $config['open_in_new_tab'] ?? false,
            'style' => $config['style'] ?? 'primary',
            'custom_color' => $config['custom_color'] ?? '#3b82f6',
            'alignment' => $config['alignment'] ?? 'center',
            'size' => $config['size'] ?? 'medium',
            'data' => $data, // For context-specific data like postUrl, pageUrl
        ])->render();
    }
}
