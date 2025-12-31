<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Enums\EventType;
use Modules\Events\Models\Event;

/**
 * Event List Component
 *
 * Displays a filterable grid of upcoming events.
 */
new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';

    public ?string $type = null;

    public ?string $timeFilter = 'upcoming';

    public int $perPage = 12;

    /**
     * Reset pagination when filters change.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedTimeFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Get filtered events.
     */
    #[Computed]
    public function events()
    {
        return Event::query()
            ->where('status', EventStatus::Published)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('location_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->type, function ($query) {
                $query->where('type', $this->type);
            })
            ->when($this->timeFilter === 'upcoming', function ($query) {
                $query->where('start_date', '>=', now());
            })
            ->when($this->timeFilter === 'past', function ($query) {
                $query->where('start_date', '<', now());
            })
            ->when($this->timeFilter === 'this_week', function ($query) {
                $query->whereBetween('start_date', [now(), now()->endOfWeek()]);
            })
            ->when($this->timeFilter === 'this_month', function ($query) {
                $query->whereBetween('start_date', [now(), now()->endOfMonth()]);
            })
            ->orderBy('start_date', $this->timeFilter === 'past' ? 'desc' : 'asc')
            ->with(['ticketTypes'])
            ->withCount('attendees')
            ->paginate($this->perPage);
    }

    /**
     * Get featured events for the hero section.
     */
    #[Computed]
    public function featuredEvents(): Collection
    {
        return Event::query()
            ->where('status', EventStatus::Published)
            ->where('is_featured', true)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(3)
            ->get();
    }

    /**
     * Get event type options.
     */
    public function getTypeOptions(): array
    {
        return collect(EventType::cases())
            ->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()])
            ->toArray();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->type = null;
        $this->timeFilter = 'upcoming';
        $this->resetPage();
    }
}; ?>

<div>
    {{-- Hero Section --}}
    <x-sections.hero
        title="Events & <span class='text-primary'>Workshops</span>"
        subtitle="Join our vibrant community events, workshops, and networking sessions. Connect with fellow entrepreneurs, learn from experts, and grow your startup."
    />

    {{-- Main Content --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Featured Events Section --}}
            @if($this->featuredEvents->isNotEmpty() && $this->timeFilter === 'upcoming' && !$search)
                <div class="mb-12 bg-gradient-to-r from-primary-600 to-primary-800 rounded-2xl overflow-hidden">
                    <div class="px-8 py-12">
                        <h2 class="text-2xl font-bold text-white mb-6">Featured Events</h2>
                        <div class="grid md:grid-cols-3 gap-6">
                            @foreach($this->featuredEvents as $event)
                                <a href="{{ route('events.show', $event->slug) }}" wire:navigate
                                   class="bg-white/10 backdrop-blur-sm rounded-xl p-6 hover:bg-white/20 transition-colors">
                            @if($event->thumbnail_image)
                                <img src="{{ Storage::url($event->thumbnail_image) }}"
                                     alt="{{ $event->title }}"
                                     class="w-full h-32 object-cover rounded-lg mb-4">
                            @endif
                            <h3 class="text-lg font-semibold text-white">{{ $event->title }}</h3>
                            <p class="text-primary-100 text-sm mt-1">
                                {{ $event->start_date->format('M j, Y \a\t g:i A') }}
                            </p>
                            <span class="inline-block mt-3 px-3 py-1 bg-white/20 rounded-full text-xs text-white">
                                {{ $event->type->getLabel() }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Search & Filters --}}
    <div class="mb-8 flex flex-col md:flex-row gap-4">
        {{-- Search --}}
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search events..."
                icon="magnifying-glass"
            />
        </div>

        {{-- Type Filter --}}
        <div class="w-full md:w-48">
            <flux:select wire:model.live="type" placeholder="All Types">
                <flux:select.option value="">All Types</flux:select.option>
                @foreach($this->getTypeOptions() as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        {{-- Time Filter --}}
        <div class="w-full md:w-48">
            <flux:select wire:model.live="timeFilter">
                <flux:select.option value="upcoming">Upcoming</flux:select.option>
                <flux:select.option value="this_week">This Week</flux:select.option>
                <flux:select.option value="this_month">This Month</flux:select.option>
                <flux:select.option value="past">Past Events</flux:select.option>
                <flux:select.option value="all">All Events</flux:select.option>
            </flux:select>
        </div>

        {{-- Clear Filters --}}
        @if($search || $type || $timeFilter !== 'upcoming')
            <flux:button wire:click="clearFilters" variant="ghost" icon="x-mark">
                Clear
            </flux:button>
        @endif
    </div>

    {{-- Events Grid --}}
    @if($this->events->isEmpty())
        <div class="text-center py-16">
            <x-heroicon-o-calendar class="mx-auto h-16 w-16 text-muted-foreground" />
            <h3 class="mt-4 text-lg font-semibold text-foreground">No events found</h3>
            <p class="mt-2 text-muted-foreground">
                @if($search || $type)
                    Try adjusting your filters or search terms.
                @else
                    Check back later for upcoming events.
                @endif
            </p>
            @if($search || $type || $timeFilter !== 'upcoming')
                <flux:button wire:click="clearFilters" variant="primary" class="mt-4">
                    Clear Filters
                </flux:button>
            @endif
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($this->events as $event)
                <article class="bg-card rounded-xl shadow-sm border border-border overflow-hidden hover:shadow-md transition-shadow">
                    {{-- Event Image --}}
                    <a href="{{ route('events.show', $event->slug) }}" wire:navigate class="block">
                        <div class="aspect-[4/3] bg-muted relative">
                            @if($event->thumbnail_image)
                                <img src="{{ Storage::url($event->thumbnail_image) }}"
                                     alt="{{ $event->title }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <x-heroicon-o-calendar class="w-12 h-12 text-muted-foreground" />
                                </div>
                            @endif

                            {{-- Date Badge --}}
                            <div class="absolute top-3 left-3 bg-card rounded-lg px-3 py-2 text-center shadow-sm">
                                <div class="text-xs font-semibold text-primary uppercase">
                                    {{ $event->start_date->format('M') }}
                                </div>
                                <div class="text-xl font-bold text-foreground">
                                    {{ $event->start_date->format('j') }}
                                </div>
                            </div>

                            {{-- Type Badge --}}
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $event->type->getColor() }}-100 text-{{ $event->type->getColor() }}-800 dark:bg-{{ $event->type->getColor() }}-900/20 dark:text-{{ $event->type->getColor() }}-400">
                                    {{ $event->type->getLabel() }}
                                </span>
                            </div>
                        </div>
                    </a>

                    {{-- Event Details --}}
                    <div class="p-4">
                        <a href="{{ route('events.show', $event->slug) }}" wire:navigate>
                            <h3 class="font-semibold text-foreground hover:text-primary line-clamp-2">
                                {{ $event->title }}
                            </h3>
                        </a>

                        <p class="mt-2 text-sm text-muted-foreground line-clamp-2">
                            {{ $event->short_description ?? Str::limit(strip_tags($event->description), 100) }}
                        </p>

                        <div class="mt-4 space-y-2 text-sm text-muted-foreground">
                            {{-- Time --}}
                            <div class="flex items-center">
                                <x-heroicon-o-clock class="w-4 h-4 mr-2" />
                                {{ $event->start_date->format('g:i A') }}
                                @if($event->end_date)
                                    - {{ $event->end_date->format('g:i A') }}
                                @endif
                            </div>

                            {{-- Location --}}
                            <div class="flex items-center">
                                @if($event->location_type->value === 'online')
                                    <x-heroicon-o-video-camera class="w-4 h-4 mr-2" />
                                    Online
                                @else
                                    <x-heroicon-o-map-pin class="w-4 h-4 mr-2" />
                                    {{ $event->location_name ?? 'TBA' }}
                                @endif
                            </div>

                            {{-- Attendees --}}
                            <div class="flex items-center">
                                <x-heroicon-o-users class="w-4 h-4 mr-2" />
                                {{ $event->attendees_count }} registered
                                @if($event->total_capacity)
                                    <span class="text-muted-foreground ml-1">/ {{ $event->total_capacity }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Price & CTA --}}
                        <div class="mt-4 flex items-center justify-between">
                            <div>
                                @if($event->is_free)
                                    <span class="text-success-600 font-semibold">Free</span>
                                @else
                                    @php
                                        $minPrice = $event->ticketTypes->min('price');
                                    @endphp
                                    <span class="font-semibold text-foreground">
                                        From {{ number_format($minPrice, 0) }} {{ $event->currency }}
                                    </span>
                                @endif
                            </div>

                            <a href="{{ route('events.show', $event->slug) }}" wire:navigate
                               class="text-sm font-medium text-primary hover:text-primary">
                                View Details →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $this->events->links() }}
        </div>
    @endif
</div>
