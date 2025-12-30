<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Cms\Filament\RichEditorBlocks\ButtonRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\CallToActionRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\CodeRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\HeadingRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ImageRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ListRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\ParagraphRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\QuoteRichBlock;
use Modules\Cms\Filament\RichEditorBlocks\VideoRichBlock;

final class Page extends Model implements HasRichContent
{
    use HasFactory, InteractsWithRichContent, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'published_at',
        'template',
        'parent_id',
        'category_id',
        'sort_order',
        'featured_image',
        'is_featured',
        'excerpt',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'content' => 'array',
            'sort_order' => 'integer',
        ];
    }

    protected $attributes = [
        'status' => 'draft',
        'template' => 'default',
        'sort_order' => 0,
        'is_featured' => false,
    ];

    public function setUpRichContent(): void
    {
        $this->registerRichContent('content')
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsVisibility('public')
            ->customBlocks([
                HeadingRichBlock::class,
                ParagraphRichBlock::class,
                ImageRichBlock::class,
                ListRichBlock::class,
                QuoteRichBlock::class,
                ButtonRichBlock::class,
                CodeRichBlock::class,
                VideoRichBlock::class,
                CallToActionRichBlock::class => [
                    'pageUrl' => fn (): string => route('page.show', $this->slug ?? '#'),
                ],
            ])
            ->mergeTags([
                'page_title' => fn (): string => $this->title ?? '',
                'page_date' => fn (): string => $this->published_at?->format('F j, Y') ?? '',
                'page_category' => fn (): string => $this->category?->name ?? '',
                'current_date' => fn (): string => now()->format('F j, Y'),
            ]);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' &&
               $this->published_at <= now();
    }
}
