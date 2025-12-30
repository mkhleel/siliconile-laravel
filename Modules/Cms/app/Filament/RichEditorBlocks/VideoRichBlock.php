<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;

final class VideoRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'video';
    }

    public static function getLabel(): string
    {
        return 'Video';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Video')
            ->modalDescription('Embed a video from YouTube, Vimeo, or direct URL.')
            ->modalWidth('lg')
            ->schema([
                TextInput::make('url')
                    ->label('Video URL')
                    ->required()
                    ->url()
                    ->placeholder('https://youtube.com/watch?v=... or https://vimeo.com/...')
                    ->helperText('Supports YouTube, Vimeo, and direct video file URLs'),

                TextInput::make('title')
                    ->label('Video Title (Optional)')
                    ->placeholder('Enter video title...')
                    ->maxLength(255),

                Select::make('aspect_ratio')
                    ->label('Aspect Ratio')
                    ->options([
                        '16:9' => '16:9 (Widescreen)',
                        '4:3' => '4:3 (Standard)',
                        '1:1' => '1:1 (Square)',
                        '21:9' => '21:9 (Ultrawide)',
                    ])
                    ->default('16:9'),

                Select::make('alignment')
                    ->label('Alignment')
                    ->options([
                        'left' => 'Left',
                        'center' => 'Center',
                        'right' => 'Right',
                    ])
                    ->default('center'),

                Select::make('max_width')
                    ->label('Maximum Width')
                    ->options([
                        'small' => 'Small (400px)',
                        'medium' => 'Medium (600px)',
                        'large' => 'Large (800px)',
                        'full' => 'Full Width',
                    ])
                    ->default('large'),

                Toggle::make('autoplay')
                    ->label('Autoplay (YouTube/Vimeo)')
                    ->default(false)
                    ->helperText('Note: Most browsers block autoplay with sound'),

                Toggle::make('controls')
                    ->label('Show Controls')
                    ->default(true),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $title = $config['title'] ?? '';
        $url = $config['url'] ?? '';

        if ($title) {
            return "Video: {$title}";
        }

        // Try to extract video platform
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'Video: YouTube';
        } elseif (str_contains($url, 'vimeo.com')) {
            return 'Video: Vimeo';
        }

        return 'Video: '.Str::limit($url, 30);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $url = $config['url'] ?? '';
        $title = $config['title'] ?? '';
        $aspectRatio = $config['aspect_ratio'] ?? '16:9';
        $alignment = $config['alignment'] ?? 'center';

        if (! $url) {
            return '<div style="padding: 40px; border: 2px dashed #d1d5db; text-align: center; color: #6b7280; border-radius: 8px;">📹 Video URL not provided</div>';
        }

        $alignStyle = match ($alignment) {
            'left' => 'text-align: left;',
            'right' => 'text-align: right;',
            default => 'text-align: center;',
        };

        $paddingBottom = match ($aspectRatio) {
            '4:3' => '75%',
            '1:1' => '100%',
            '21:9' => '42.86%',
            default => '56.25%', // 16:9
        };

        $html = "<div style=\"{$alignStyle} margin: 20px 0;\">";
        $html .= "<div style=\"position: relative; width: 100%; max-width: 600px; margin: 0 auto; padding-bottom: {$paddingBottom}; background: #f3f4f6; border-radius: 8px; overflow: hidden; border: 1px solid #d1d5db;\">";

        // Video placeholder with play button
        $html .= '<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1f2937, #374151);">';
        $html .= '<div style="text-align: center; color: white;">';
        $html .= '<div style="font-size: 48px; margin-bottom: 10px;">▶️</div>';

        if ($title) {
            $html .= "<div style=\"font-size: 16px; font-weight: 600; margin-bottom: 5px;\">{$title}</div>";
        }

        // Show platform info
        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            $html .= '<div style="font-size: 14px; color: #d1d5db;">YouTube Video</div>';
        } elseif (str_contains($url, 'vimeo.com')) {
            $html .= '<div style="font-size: 14px; color: #d1d5db;">Vimeo Video</div>';
        } else {
            $html .= '<div style="font-size: 14px; color: #d1d5db;">Video</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
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
        return view('cms::rich-blocks.video', [
            'url' => $config['url'] ?? '',
            'title' => $config['title'] ?? '',
            'aspect_ratio' => $config['aspect_ratio'] ?? '16:9',
            'alignment' => $config['alignment'] ?? 'center',
            'max_width' => $config['max_width'] ?? 'large',
            'autoplay' => $config['autoplay'] ?? false,
            'controls' => $config['controls'] ?? true,
        ])->render();
    }
}
