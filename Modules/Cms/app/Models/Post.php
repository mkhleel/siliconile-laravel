<?php

declare(strict_types=1);

namespace Modules\Cms\Models;

use App\Models\User;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

final class Post extends Model implements HasRichContent
{
    use HasFactory, InteractsWithRichContent, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'status',
        'published_at',
        'featured_image',
        'is_featured',
        'category_id',
        'author_id',
        'reading_time',
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
        ];
    }

    protected $attributes = [
        'status' => 'draft',
        'is_featured' => false,
        'reading_time' => 0,
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
                    'postUrl' => fn (): string => route('posts.show', $this->slug ?? '#'),
                ],
            ])
            ->mergeTags([
                'post_title' => fn (): string => $this->title ?? '',
                'post_author' => fn (): string => $this->author?->name ?? '',
                'post_date' => fn (): string => $this->published_at?->format('F j, Y') ?? '',
                'post_category' => fn (): string => $this->category?->name ?? '',
            ]);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
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
