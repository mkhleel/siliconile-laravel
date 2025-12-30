<?php

declare(strict_types=1);

namespace Modules\Cms\Filament\Blocks;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\RichEditor;
use Modules\Cms\Filament\RichEditorBlocks\ButtonRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\CallToActionRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\CodeRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\HeadingRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ImageRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ListRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ParagraphRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\QuoteRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\VideoRichBlock;

final class ContentBlocks
{
    /**
     * Legacy Builder-based content blocks (for backward compatibility)
     */
    public static function make(): Builder
    {
        return Builder::make('content')
            ->blocks([
                HeadingBlock::make(),
                ParagraphBlock::make(),
                ImageBlock::make(),
                ListBlock::make(),
                QuoteBlock::make(),
                ButtonBlock::make(),
                CodeBlock::make(),
                VideoBlock::make(),
                TableBlock::make(),
                CallToActionBlock::make(),
            ])
            ->blockNumbers(false)
            ->blockIcons()
            ->collapsible()
            ->cloneable()
            ->reorderable()
            ->minItems(0)
            ->columnSpanFull();
    }

    /**
     * New RichEditor with custom blocks (Filament v4 TipTap-based)
     */
    public static function makeRichEditor($colName): RichEditor
    {
        return RichEditor::make($colName)
            ->label('Content')
            ->json()
            ->floatingToolbars([
                // Text formatting
                ['bold', 'italic', 'underline', 'strike'],
                ['subscript', 'superscript', 'code'],

                // Alignment
                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],

                // Headings and structure
                ['h1', 'h2', 'h3', 'blockquote'],
                ['bulletList', 'orderedList'],

                // Advanced content
                ['link', 'table', 'horizontalRule'],
                ['codeBlock', 'details'],

                // Files and special
                ['attachFiles', 'clearFormatting'],

                // History
                ['undo', 'redo'],
            ])
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
            ])
            ->activePanel('customBlocks')
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('cms/rich-editor/attachments')
            ->fileAttachmentsVisibility('public')
            ->columnSpanFull()
            ->required();
    }

    public static function getBasicBlocks(): array
    {
        return [
            HeadingBlock::make(),
            ParagraphBlock::make(),
            ImageBlock::make(),
            ListBlock::make(),
        ];
    }

    public static function getAdvancedBlocks(): array
    {
        return [
            QuoteBlock::make(),
            ButtonBlock::make(),
            CodeBlock::make(),
            VideoBlock::make(),
            TableBlock::make(),
            CallToActionBlock::make(),
        ];
    }

    /**
     * Legacy Builder for Pages (for backward compatibility)
     */
    public static function makeForPages(): Builder
    {
        return self::make()
            ->helperText('Build your page content using various content blocks. Drag and drop to reorder.');
    }

    /**
     * Legacy Builder for Posts (for backward compatibility)
     */
    public static function makeForPosts(): Builder
    {
        return self::make()
            ->helperText('Create rich content for your blog post using content blocks.');
    }

    /**
     * RichEditor for Pages (Filament v4)
     */
    public static function makeRichEditorForPages($colName): RichEditor
    {
        return self::makeRichEditor($colName)
            ->helperText('Create your page content using the rich editor with custom blocks. Use the custom blocks panel for advanced components.');
    }

    /**
     * RichEditor for Posts (Filament v4)
     */
    public static function makeRichEditorForPosts(): RichEditor
    {
        return self::makeRichEditor()
            ->helperText('Write your blog post using the rich editor. Use custom blocks for enhanced content like quotes, buttons, and call-to-actions.');
    }
}
