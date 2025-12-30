<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\RichEditorBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

final class ImageRichBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'image';
    }

    public static function getLabel(): string
    {
        return 'Image';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Add Image')
            ->modalDescription('Upload an image with optional caption and styling.')
            ->modalWidth('lg')
            ->schema([
                FileUpload::make('url')
                    ->label('Image')
                    ->image()
                    ->required()
                    ->directory('cms/rich-editor/images')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageResizeMode('contain')
                    ->maxSize(5120) // 5MB
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']),

                TextInput::make('alt')
                    ->label('Alternative Text')
                    ->placeholder('Describe the image for accessibility')
                    ->helperText('This text will be read by screen readers and shown if the image fails to load.')
                    ->maxLength(255),

                TextInput::make('caption')
                    ->label('Caption (Optional)')
                    ->placeholder('Enter a caption for the image')
                    ->maxLength(500),

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
                        'small' => 'Small (25%)',
                        'medium' => 'Medium (50%)',
                        'large' => 'Large (75%)',
                        'full' => 'Full Width (100%)',
                    ])
                    ->default('large'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function getPreviewLabel(array $config): string
    {
        $alt = $config['alt'] ?? 'Image';
        $caption = $config['caption'] ?? '';

        if ($caption) {
            return "Image: {$alt} - ".Str::limit($caption, 30);
        }

        return "Image: {$alt}";
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function toPreviewHtml(array $config): string
    {
        $url = $config['url'] ?? '';
        $alt = $config['alt'] ?? 'Image';
        $caption = $config['caption'] ?? '';
        $alignment = $config['alignment'] ?? 'center';
        $size = $config['size'] ?? 'large';

        if (! $url) {
            return '<div style="padding: 20px; border: 2px dashed #d1d5db; text-align: center; color: #6b7280;">Image not uploaded</div>';
        }

        $imageUrl = str_starts_with($url, 'http') ? $url : asset('storage/'.$url);

        $containerStyle = match ($alignment) {
            'left' => 'text-align: left;',
            'right' => 'text-align: right;',
            default => 'text-align: center;',
        };

        $width = match ($size) {
            'small' => '25%',
            'medium' => '50%',
            'large' => '75%',
            default => '100%',
        };

        $html = "<div style=\"{$containerStyle} margin: 10px 0;\">";
        $html .= "<img src=\"{$imageUrl}\" alt=\"{$alt}\" style=\"max-width: {$width}; height: auto; border-radius: 4px;\">";

        if ($caption) {
            $html .= "<div style=\"font-size: 14px; color: #6b7280; margin-top: 8px; font-style: italic;\">{$caption}</div>";
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        return view('cms::rich-blocks.image', [
            'url' => $config['url'] ?? '',
            'alt' => $config['alt'] ?? '',
            'caption' => $config['caption'] ?? '',
            'alignment' => $config['alignment'] ?? 'center',
            'size' => $config['size'] ?? 'large',
        ])->render();
    }
}
