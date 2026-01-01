<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Enums\EventStatus;
use Modules\Events\Models\Event;
use Modules\Events\Models\TicketType;

/**
 * Event Detail Component
 *
 * Displays full event information with ticket selection.
 */
new #[Layout('layouts.app')] class extends Component {
    public Event $event;

    /**
     * Mount the component with the event slug.
     */
    public function mount(string $slug): void
    {
        $this->event = Event::query()
            ->where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->with([
                'ticketTypes' => fn ($q) => $q->available()->orderBy('sort_order'),
                'organizer',
                'sessions',
            ])
            ->withCount('attendees')
            ->firstOrFail();
    }

    /**
     * Get available ticket types.
     */
    #[Computed]
    public function ticketTypes(): Collection
    {
        return $this->event->ticketTypes
            ->filter(fn (TicketType $ticket) => ! $ticket->is_hidden)
            ->filter(fn (TicketType $ticket) => $ticket->is_purchasable);
    }

    /**
     * Check if registration is open.
     */
    #[Computed]
    public function isRegistrationOpen(): bool
    {
        return $this->event->is_registration_open;
    }

    /**
     * Check if event is sold out.
     */
    #[Computed]
    public function isSoldOut(): bool
    {
        return $this->event->is_sold_out;
    }

    /**
     * Get spots remaining.
     */
    #[Computed]
    public function spotsRemaining(): ?int
    {
        return $this->event->available_spots;
    }

    /**
     * Check if user is already registered.
     */
    #[Computed]
    public function isUserRegistered(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        return $this->event->attendees()
            ->where('user_id', auth()->id())
            ->whereNotIn('status', [AttendeeStatus::Cancelled])
            ->exists();
    }

    /**
     * Get related events.
     */
    #[Computed]
    public function relatedEvents(): Collection
    {
        return Event::query()
            ->where('status', EventStatus::Published)
            ->where('id', '!=', $this->event->id)
            ->where('type', $this->event->type)
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->limit(3)
            ->get();
    }
}; ?>

<div>
    {{-- Hero Section --}}
    <div class="relative bg-gradient-to-r from-gray-900 to-gray-800 overflow-hidden">
        {{-- Background Image --}}
        @if($event->banner_image)
            <div class="absolute inset-0">
                <img src="{{ Storage::url($event->banner_image) }}"
                     alt="{{ $event->title }}"
                     class="w-full h-full object-cover opacity-30">
            </div>
        @endif

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
            <div class="flex flex-col md:flex-row gap-8">
                {{-- Event Info --}}
                <div class="flex-1">
                    {{-- Breadcrumb --}}
                    <nav class="flex items-center space-x-2 text-sm text-gray-400 mb-4">
                        <a href="{{ route('events.index') }}" wire:navigate class="hover:text-white">Events</a>
                        <span>/</span>
                        <span>{{ $event->type->getLabel() }}</span>
                    </nav>

                    {{-- Type Badge --}}
                    <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full bg-{{ $event->type->getColor() }}-500/20 text-white border border-{{ $event->type->getColor() }}-500/30 mb-4">
                        <x-icon :name="$event->type->getIcon()" class="w-4 h-4 mr-2 " />
                        <span class="ml-2">{{ $event->type->getLabel() }}</span>
                    </span>

                    {{-- Title --}}
                    <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                        {{ $event->title }}
                    </h1>

                    {{-- Short Description --}}
                    @if($event->short_description)
                        <p class="text-xl text-gray-300 mb-6">
                            {{ $event->short_description }}
                        </p>
                    @endif

                    {{-- Event Meta --}}
                    <div class="flex flex-wrap gap-6 text-gray-300">
                        {{-- Date --}}
                        <div class="flex items-center">
                            <x-heroicon-o-calendar class="w-5 h-5 mr-2 text-primary-400" />
                            <div>
                                <div class="font-semibold text-white">{{ $event->start_date->format('l, F j, Y') }}</div>
                                <div class="text-sm">{{ $event->start_date->format('g:i A') }}
                                    @if($event->end_date)
                                        - {{ $event->end_date->format('g:i A') }}
                                    @endif
                                    ({{ $event->timezone }})
                                </div>
                            </div>
                        </div>

                        {{-- Location --}}
                        <div class="flex items-center">
                            @if($event->location_type->value === 'online')
                                <x-heroicon-o-video-camera class="w-5 h-5 mr-2 text-primary-400" />
                                <div>
                                    <div class="font-semibold text-white">Online Event</div>
                                    <div class="text-sm">{{ __('Join from anywhere') }}</div>
                                </div>
                            @else
                                <x-heroicon-o-map-pin class="w-5 h-5 mr-2 text-primary-400" />
                                <div>
                                    <div class="font-semibold text-white">{{ $event->location_name }}</div>
                                    @if($event->location_address)
                                        <div class="text-sm">{{ $event->location_address }}</div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Organizer --}}
                        @if($event->organizer_name || $event->organizer)
                            <div class="flex items-center">
                                <x-heroicon-o-user-circle class="w-5 h-5 mr-2 text-primary-400" />
                                <div>
                                    <div class="font-semibold text-white">
                                        {{ $event->organizer_name ?? $event->organizer?->name }}
                                    </div>
                                    <div class="text-sm">Organizer</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Registration Card --}}
                <div class="w-full md:w-96">
                    <div class="bg-card rounded-xl shadow-xl p-6">
                        {{-- Price Display --}}
                        <div class="mb-4">
                            @if($event->is_free)
                                <div class="text-3xl font-bold text-success-600">Free</div>
                            @else
                                @php
                                    $minPrice = $this->ticketTypes->min('price');
                                    $maxPrice = $this->ticketTypes->max('price');
                                @endphp
                                <div class="text-3xl font-bold text-foreground">
                                    @if($minPrice == $maxPrice)
                                        {{ number_format($minPrice, 0) }} {{ $event->currency }}
                                    @else
                                        From {{ number_format($minPrice, 0) }} {{ $event->currency }}
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Availability Status --}}
                        <div class="mb-4 pb-4 border-b border-border">
                            @if($this->isSoldOut)
                                <div class="flex items-center text-danger-600">
                                    <x-heroicon-o-x-circle class="w-5 h-5 mr-2" />
                                    <span class="font-semibold">{{ __('Sold Out') }}</span>
                                </div>
                            @elseif($this->spotsRemaining !== null && $this->spotsRemaining <= 10)
                                <div class="flex items-center text-warning-600">
                                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 mr-2" />
                                    <span class="font-semibold">Only {{ $this->spotsRemaining }} {{ __('spots left!') }}</span>
                                </div>
                            @else
                                <div class="flex items-center text-success-600">
                                    <x-heroicon-o-check-circle class="w-5 h-5 mr-2" />
                                    <span class="font-semibold">{{ __('Spots Available') }}</span>
                                </div>
                            @endif

                            <div class="text-sm text-muted-foreground mt-1">
                                {{ $event->attendees_count }} registered
                                @if($event->total_capacity)
                                    / {{ $event->total_capacity }} capacity
                                @endif
                            </div>
                        </div>

                        {{-- CTA Button --}}
                        @if($this->isUserRegistered)
                            <div class="text-center py-4 px-6 bg-success-50 rounded-lg">
                                <x-heroicon-o-check-badge class="w-8 h-8 mx-auto text-success-600 mb-2" />
                                <p class="text-success-700 font-semibold">{{ __('You\'re registered!') }}</p>
                                <a href="{{ route('events.my-tickets') }}" wire:navigate
                                   class="text-sm text-success-600 hover:underline">
                                    {{ __('View your ticket →') }}
                                </a>
                            </div>
                        @elseif(!$this->isRegistrationOpen)
                            <div class="text-center py-4 px-6 bg-muted rounded-lg">
                                <p class="text-muted-foreground">
                                    Registration
                                    @if($event->registration_start_date && $event->registration_start_date->isFuture()) {{ __('opens') }} {{ $event->registration_start_date->diffForHumans() }}
                                    @else
                                        {{ __('is closed') }}
                                    @endif
                                </p>
                            </div>
                        @elseif($this->isSoldOut)
                            @if($event->allow_waitlist)
                                <a href="{{ route('events.book', $event->slug) }}" wire:navigate
                                   class="block w-full text-center py-3 px-6 bg-secondary text-secondary-foreground font-semibold rounded-lg hover:bg-secondary/80 transition-colors">
                                    Join Waitlist
                                </a>
                            @else
                                <button disabled
                                        class="w-full py-3 px-6 bg-muted text-muted-foreground font-semibold rounded-lg cursor-not-allowed">
                                    Sold Out
                                </button>
                            @endif
                        @else
                            <a href="{{ route('events.book', $event->slug) }}" wire:navigate
                               class="block w-full text-center py-3 px-6 bg-primary text-primary-foreground font-semibold rounded-lg hover:bg-primary/90 transition-colors">
                                {{ $event->is_free ? 'Register Now' : 'Get Tickets' }}
                            </a>
                        @endif

                        {{-- Share --}}
                        <div class="mt-4 flex justify-center space-x-4">
                            <button onclick="navigator.share ? navigator.share({title: '{{ $event->title }}', url: window.location.href}) : navigator.clipboard.writeText(window.location.href)"
                                    class="text-muted-foreground hover:text-foreground">
                                <x-heroicon-o-share class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col lg:flex-row gap-12">
            {{-- Event Content --}}
            <div class="flex-1">
                {{-- Description --}}
                <section class="mb-12">
                    <h2 class="text-2xl font-bold text-foreground mb-6">{{ __('About This Event') }}</h2>
                    <div class="prose prose-lg max-w-none">
                        {!! $event->description !!}
                    </div>
                </section>

                {{-- Sessions (for multi-session events) --}}
                @if($event->is_multi_session && $event->sessions->isNotEmpty())
                    <section class="mb-12">
                        <h2 class="text-2xl font-bold text-foreground mb-6">Schedule</h2>
                        <div class="space-y-4">
                            @foreach($event->sessions as $session)
                                <div class="bg-muted rounded-lg p-4">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-semibold text-foreground">
                                                {{ $session->title }}
                                            </h3>
                                            @if($session->description)
                                                <p class="text-muted-foreground mt-1">
                                                    {{ $session->description }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-right text-sm text-muted-foreground">
                                            <div>{{ $session->start_time->format('M j, Y') }}</div>
                                            <div>{{ $session->start_time->format('g:i A') }} - {{ $session->end_time->format('g:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Tags --}}
                @if($event->tags && is_array($event->tags) && count($event->tags) > 0)
                    <section class="mb-12">
                        <h2 class="text-lg font-semibold text-foreground mb-4">Tags</h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach($event->tags as $tag)
                                <span class="px-3 py-1 bg-muted text-muted-foreground rounded-full text-sm">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="lg:w-80">
                {{-- Ticket Types --}}
                @if($this->ticketTypes->isNotEmpty())
                    <div class="bg-card rounded-xl shadow-sm border border-border p-6 mb-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4">{{ __('Ticket Options') }}</h3>
                        <div class="space-y-4">
                            @foreach($this->ticketTypes as $ticket)
                                <div class="border border-border rounded-lg p-4 {{ !$ticket->is_purchasable ? 'opacity-60' : '' }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-foreground">{{ $ticket->name }}</h4>
                                            @if($ticket->description)
                                                <p class="text-sm text-muted-foreground mt-1">
                                                    {{ $ticket->description }}
                                                </p>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            @if($ticket->is_free)
                                                <span class="text-success-600 font-semibold">Free</span>
                                            @else
                                                <span class="font-semibold text-foreground">
                                                    {{ number_format($ticket->price, 0) }} {{ $ticket->currency }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    @if(!$ticket->is_purchasable)
                                        <div class="mt-2 text-sm text-danger-600">
                                            {{ $ticket->quantity_available <= 0 ? 'Sold Out' : 'Not Available' }}
                                        </div>
                                    @elseif($ticket->quantity_available !== null && $ticket->quantity_available <= 10)
                                        <div class="mt-2 text-sm text-warning-600">
                                            Only {{ $ticket->quantity_available }} left
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Location Map Placeholder --}}
                @if($event->location_type->value !== 'online' && $event->location_address)
                    <div class="bg-card rounded-xl shadow-sm border border-border p-6 mb-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4">Location</h3>
                        <div class="bg-muted rounded-lg h-40 flex items-center justify-center mb-4">
                            <x-heroicon-o-map class="w-12 h-12 text-muted-foreground" />
                        </div>
                        <p class="text-foreground font-medium">{{ $event->location_name }}</p>
                        <p class="text-muted-foreground text-sm">{{ $event->location_address }}</p>
                    </div>
                @endif

                {{-- Organizer --}}
                @if($event->organizer_name || $event->organizer)
                    <div class="bg-card rounded-xl shadow-sm border border-border p-6">
                        <h3 class="text-lg font-semibold text-foreground mb-4">Organizer</h3>
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                                <x-heroicon-o-user class="w-6 h-6 text-primary" />
                            </div>
                            <div class="ml-4">
                                <p class="font-medium text-foreground">
                                    {{ $event->organizer_name ?? $event->organizer?->name }}
                                </p>
                                @if($event->organizer_email)
                                    <a href="mailto:{{ $event->organizer_email }}"
                                       class="text-sm text-primary hover:underline">
                                        Contact Organizer
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- {{ __('Related Events') }} --}}
    @if($this->relatedEvents->isNotEmpty())
        <div class="bg-muted py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-foreground mb-8">Related Events</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($this->relatedEvents as $relatedEvent)
                        <a href="{{ route('events.show', $relatedEvent->slug) }}" wire:navigate
                           class="bg-card rounded-xl shadow-sm border border-border overflow-hidden hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-{{ $relatedEvent->type->getColor() }}-100 text-{{ $relatedEvent->type->getColor() }}-800">
                                    {{ $relatedEvent->type->getLabel() }}
                                </span>
                                <h3 class="mt-3 font-semibold text-foreground">
                                    {{ $relatedEvent->title }}
                                </h3>
                                <p class="mt-2 text-sm text-muted-foreground">
                                    {{ $relatedEvent->start_date->format('M j, Y \a\t g:i A') }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
