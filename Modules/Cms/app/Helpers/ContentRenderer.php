<?php

declare(strict_types=1);

namespace Modules\Cms\Helpers;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use Modules\Cms\Filament\RichEditorBlocks\ButtonRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\CallToActionRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\CodeRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\HeadingRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ImageRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ListRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ParagraphRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\QuoteRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\VideoRichBlock;

final class ContentRenderer
{
    /**
     * Render content supporting both RichEditor and legacy Builder formats
     */
    public static function render(mixed $content): HtmlString
    {
        if (empty($content)) {
            return new HtmlString('');
        }

        // Handle string content (legacy or simple text)
        if (is_string($content)) {
            return new HtmlString($content);
        }

        // Handle array content - check if it's RichEditor format
        if (is_array($content)) {
            // Check if it's RichEditor format (has 'type' and 'content' structure)
            if (self::isRichEditorContent($content)) {
                return self::renderRichEditorContent($content);
            }

            // Handle legacy Builder format
            return self::renderBuilderContent($content);
        }

        // Fallback for any other format
        return new HtmlString((string) $content);
    }

    /**
     * Check if content is in RichEditor format
     */
    private static function isRichEditorContent(array $content): bool
    {
        // RichEditor content typically has a 'type' field and nested structure
        if (isset($content['type'])) {
            return true;
        }

        // Check if it's an array of RichEditor nodes
        if (is_array($content) && ! empty($content)) {
            $firstItem = reset($content);

            return is_array($firstItem) && isset($firstItem['type']);
        }

        return false;
    }

    /**
     * Render RichEditor content using Filament's renderer
     */
    private static function renderRichEditorContent(array $content): HtmlString
    {
        try {
            $renderer = RichContentRenderer::make($content)
                ->customBlocks([
                    HeadingRichBlock::class,
                    ParagraphRichBlock::class,
                    ImageRichBlock::class,
                    ListRichBlock::class,
                    QuoteRichBlock::class,
                    ButtonRichBlock::class,
                    CodeRichBlock::class,
                    VideoRichBlock::class,
                    CallToActionRichBlock::class,
                ]);

            $html = $renderer->toHtml();

            return new HtmlString($html);
        } catch (\Exception $e) {
            // Fallback in case of rendering error
            return new HtmlString('<div class="content-error">Content rendering error</div>');
        }
    }

    /**
     * Render legacy Builder content
     */
    private static function renderBuilderContent(array $content): HtmlString
    {
        $html = '';

        foreach ($content as $block) {
            if (! is_array($block) || ! isset($block['type'])) {
                continue;
            }

            $html .= self::renderBuilderBlock($block);
        }

        return new HtmlString($html);
    }

    /**
     * Render individual Builder block
     */
    private static function renderBuilderBlock(array $block): string
    {
        $type = $block['type'] ?? '';
        $data = $block['data'] ?? [];

        // Map legacy block types to view templates
        $viewMap = [
            'heading' => 'cms::blocks.heading',
            'paragraph' => 'cms::blocks.paragraph',
            'image' => 'cms::blocks.image',
            'gallery' => 'cms::blocks.gallery',
            'video' => 'cms::blocks.video',
            'quote' => 'cms::blocks.quote',
            'button' => 'cms::blocks.button',
            'code' => 'cms::blocks.code',
            'list' => 'cms::blocks.list',
            'divider' => 'cms::blocks.divider',
            'spacer' => 'cms::blocks.spacer',
            'callToAction' => 'cms::blocks.call-to-action',
        ];

        $view = $viewMap[$type] ?? null;

        if ($view && View::exists($view)) {
            try {
                return Blade::render("@include('{$view}', \$data)", ['data' => $data]);
            } catch (\Exception $e) {
                return "<div class=\"block-error\">Error rendering {$type} block</div>";
            }
        }

        // Fallback for unknown block types
        return "<div class=\"unknown-block\">Unknown block type: {$type}</div>";
    }

    /**
     * Extract plain text from content for search/preview purposes
     */
    public static function extractText(mixed $content, ?int $limit = null): string
    {
        if (empty($content)) {
            return '';
        }

        if (is_string($content)) {
            $text = strip_tags($content);
        } elseif (is_array($content)) {
            $text = self::extractTextFromArray($content);
        } else {
            $text = (string) $content;
        }

        $text = preg_replace('/\s+/', ' ', trim($text));

        if ($limit && strlen($text) > $limit) {
            $text = substr($text, 0, $limit).'...';
        }

        return $text;
    }

    /**
     * Extract text from array content (both RichEditor and Builder)
     */
    private static function extractTextFromArray(array $content): string
    {
        $text = '';

        foreach ($content as $item) {
            if (is_array($item)) {
                if (isset($item['type'])) {
                    // Handle both RichEditor and Builder blocks
                    if (isset($item['content'])) {
                        if (is_array($item['content'])) {
                            $text .= self::extractTextFromArray($item['content']).' ';
                        } else {
                            $text .= strip_tags((string) $item['content']).' ';
                        }
                    }

                    if (isset($item['data'])) {
                        $text .= self::extractTextFromData($item['data']).' ';
                    }

                    if (isset($item['text'])) {
                        $text .= strip_tags((string) $item['text']).' ';
                    }
                } else {
                    $text .= self::extractTextFromArray($item).' ';
                }
            } elseif (is_string($item)) {
                $text .= strip_tags($item).' ';
            }
        }

        return $text;
    }

    /**
     * Extract text from block data
     */
    private static function extractTextFromData(array $data): string
    {
        $text = '';

        foreach ($data as $value) {
            if (is_string($value)) {
                $text .= strip_tags($value).' ';
            } elseif (is_array($value)) {
                $text .= self::extractTextFromArray($value).' ';
            }
        }

        return $text;
    }
}
