<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Modules\Cms\Models\Post;

new
#[Layout('layouts.app')]
#[Title('Event Details')]
class extends Component {
    public Post $event;

    public function mount(string $slug): void
    {
        $this->event = Post::where('slug', $slug)
            ->published()
            ->with(['author', 'category', 'tags'])
            ->firstOrFail();
    }
}; ?>

<div>
    <!-- Breadcrumb -->
    <div class="bg-muted/30 border-b">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('home') }}" class="text-muted-foreground hover:text-primary transition-colors">Home</a>
                <span class="text-muted-foreground">/</span>
                <a href="{{ route('events') }}" class="text-muted-foreground hover:text-primary transition-colors">Events</a>
                <span class="text-muted-foreground">/</span>
                <span class="text-foreground font-medium line-clamp-1">{{ $event->title }}</span>
            </nav>
        </div>
    </div>

    <article>
        <!-- Hero Section -->
        <div class="relative">
            @if($event->featured_image)
            <div class="aspect-[21/9] md:aspect-[3/1] overflow-hidden">
                <img 
                    src="{{ Storage::url($event->featured_image) }}" 
                    alt="{{ $event->title }}"
                    class="w-full h-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-background via-background/50 to-transparent"></div>
            </div>
            @else
            <div class="h-48 md:h-64 bg-gradient-to-br from-primary/20 to-primary/5"></div>
            @endif

            <!-- Floating Info Card -->
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="relative -mt-24 md:-mt-32">
                    <div class="bg-background rounded-xl shadow-lg p-6 md:p-8 border">
                        <div class="flex flex-wrap gap-3 mb-4">
                            @if($event->category)
                            <x-ui.badge variant="outline">{{ $event->category->name }}</x-ui.badge>
                            @endif
                            @if($event->is_featured)
                            <x-ui.badge variant="default" class="bg-yellow-500/10 text-yellow-600 border-yellow-500/20">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                                Featured
                            </x-ui.badge>
                            @endif
                        </div>

                        <h1 class="text-2xl md:text-4xl font-bold text-foreground mb-4">{{ $event->title }}</h1>

                        <div class="flex flex-wrap items-center gap-4 md:gap-6 text-sm text-muted-foreground">
                            @if($event->published_at)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>{{ $event->published_at->format('F j, Y') }}</span>
                            </div>
                            @endif

                            @if($event->author)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span>{{ $event->author->name }}</span>
                            </div>
                            @endif

                            @if($event->reading_time)
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $event->reading_time }} min read</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <!-- Excerpt -->
                    @if($event->excerpt)
                    <div class="text-lg text-muted-foreground mb-8 leading-relaxed">
                        {{ $event->excerpt }}
                    </div>
                    @endif

                    <!-- Rich Content -->
                    <div class="prose prose-gray dark:prose-invert max-w-none">
                        @if(is_array($event->content))
                            @foreach($event->content as $block)
                                @php
                                    $type = $block['type'] ?? 'paragraph';
                                    $data = $block['data'] ?? [];
                                @endphp
                                
                                @switch($type)
                                    @case('heading')
                                        @php
                                            $level = $data['level'] ?? 2;
                                            $text = $data['text'] ?? '';
                                        @endphp
                                        @if($level === 1)
                                            <h1>{{ $text }}</h1>
                                        @elseif($level === 2)
                                            <h2>{{ $text }}</h2>
                                        @elseif($level === 3)
                                            <h3>{{ $text }}</h3>
                                        @else
                                            <h4>{{ $text }}</h4>
                                        @endif
                                        @break
                                    
                                    @case('paragraph')
                                        <p>{!! $data['text'] ?? '' !!}</p>
                                        @break
                                    
                                    @case('image')
                                        <figure>
                                            <img src="{{ $data['url'] ?? '' }}" alt="{{ $data['alt'] ?? '' }}" class="rounded-lg">
                                            @if(!empty($data['caption']))
                                            <figcaption class="text-center text-sm text-muted-foreground mt-2">{{ $data['caption'] }}</figcaption>
                                            @endif
                                        </figure>
                                        @break
                                    
                                    @case('list')
                                        @if(($data['style'] ?? 'unordered') === 'ordered')
                                        <ol>
                                            @foreach($data['items'] ?? [] as $item)
                                            <li>{!! $item !!}</li>
                                            @endforeach
                                        </ol>
                                        @else
                                        <ul>
                                            @foreach($data['items'] ?? [] as $item)
                                            <li>{!! $item !!}</li>
                                            @endforeach
                                        </ul>
                                        @endif
                                        @break
                                    
                                    @case('quote')
                                        <blockquote>
                                            <p>{!! $data['text'] ?? '' !!}</p>
                                            @if(!empty($data['caption']))
                                            <cite>— {{ $data['caption'] }}</cite>
                                            @endif
                                        </blockquote>
                                        @break
                                    
                                    @case('code')
                                        <pre><code class="language-{{ $data['language'] ?? 'plaintext' }}">{{ $data['code'] ?? '' }}</code></pre>
                                        @break
                                    
                                    @default
                                        @if(isset($data['text']))
                                        <p>{!! $data['text'] !!}</p>
                                        @endif
                                @endswitch
                            @endforeach
                        @else
                            {!! $event->content !!}
                        @endif
                    </div>

                    <!-- Tags -->
                    @if($event->tags && $event->tags->count() > 0)
                    <div class="mt-8 pt-8 border-t">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-muted-foreground">Tags:</span>
                            @foreach($event->tags as $tag)
                            <x-ui.badge variant="secondary">{{ $tag->name }}</x-ui.badge>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Share Buttons -->
                    <div class="mt-8 pt-8 border-t">
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-muted-foreground">Share:</span>
                            <div class="flex gap-2">
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($event->title) }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="p-2 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="p-2 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                                <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(request()->url()) }}&title={{ urlencode($event->title) }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="p-2 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                    </svg>
                                </a>
                                <button 
                                    onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied!');"
                                    class="p-2 rounded-lg bg-muted hover:bg-muted/80 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        <!-- Author Card -->
                        @if($event->author)
                        <x-ui.card>
                            <div class="p-6">
                                <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Posted by</h3>
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                                        <span class="text-lg font-semibold text-primary">{{ substr($event->author->name, 0, 1) }}</span>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ $event->author->name }}</p>
                                        <p class="text-sm text-muted-foreground">Team Member</p>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>
                        @endif

                        <!-- CTA Card -->
                        <x-ui.card class="bg-primary/5 border-primary/20">
                            <div class="p-6 text-center">
                                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h3 class="font-semibold mb-2">Join Our Community</h3>
                                <p class="text-sm text-muted-foreground mb-4">Be part of Luxor's most innovative startup ecosystem</p>
                                <a href="{{ route('apply') }}">
                                    <x-ui.button class="w-full">Apply Now</x-ui.button>
                                </a>
                            </div>
                        </x-ui.card>

                        <!-- Related Events -->
                        @php
                            $relatedEvents = \Modules\Cms\Models\Post::published()
                                ->where('id', '!=', $event->id)
                                ->when($event->category_id, fn($q) => $q->where('category_id', $event->category_id))
                                ->latest('published_at')
                                ->limit(3)
                                ->get();
                        @endphp

                        @if($relatedEvents->count() > 0)
                        <div>
                            <h3 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-4">Related Events</h3>
                            <div class="space-y-4">
                                @foreach($relatedEvents as $related)
                                <a href="{{ route('events.show', $related->slug) }}" class="block group">
                                    <div class="flex gap-3">
                                        @if($related->featured_image)
                                        <div class="w-20 h-16 rounded-lg overflow-hidden shrink-0">
                                            <img src="{{ Storage::url($related->featured_image) }}" alt="{{ $related->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                        </div>
                                        @endif
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-medium text-sm line-clamp-2 group-hover:text-primary transition-colors">{{ $related->title }}</h4>
                                            <p class="text-xs text-muted-foreground mt-1">{{ $related->published_at->format('M j, Y') }}</p>
                                        </div>
                                    </div>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </article>

    <!-- More Events -->
    @php
        $moreEvents = \Modules\Cms\Models\Post::published()
            ->where('id', '!=', $event->id)
            ->latest('published_at')
            ->limit(3)
            ->get();
    @endphp

    @if($moreEvents->count() > 0)
    <x-sections.content title="More Events" class="bg-muted/30">
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($moreEvents as $moreEvent)
            <x-cards.event-card :event="$moreEvent" />
            @endforeach
        </div>
        <div class="text-center mt-8">
            <a href="{{ route('events') }}">
                <x-ui.button variant="outline">View All Events</x-ui.button>
            </a>
        </div>
    </x-sections.content>
    @endif
</div>
