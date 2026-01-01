<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Models\Attendee;

/**
 * {{ __('My Tickets') }} Component
 *
 * Displays all tickets for the authenticated user.
 */
new #[Layout('components.layouts.app')] class extends Component {
    public string $filter = 'upcoming';

    /**
     * Get user's tickets.
     */
    #[Computed]
    public function tickets(): Collection
    {
        $query = Attendee::query()
            ->where('user_id', auth()->id())
            ->whereNotIn('status', [AttendeeStatus::Cancelled])
            ->with(['event', 'ticketType']);

        if ($this->filter === 'upcoming') {
            $query->whereHas('event', fn ($q) => $q->where('start_date', '>=', now()));
        } elseif ($this->filter === 'past') {
            $query->whereHas('event', fn ($q) => $q->where('start_date', '<', now()));
        }

        return $query
            ->orderBy(
                Attendee::query()
                    ->select('start_date')
                    ->from('events')
                    ->whereColumn('events.id', 'attendees.event_id')
                    ->limit(1),
                $this->filter === 'past' ? 'desc' : 'asc'
            )
            ->get();
    }

    /**
     * Get ticket counts by status.
     */
    #[Computed]
    public function ticketCounts(): array
    {
        return [
            'upcoming' => Attendee::query()
                ->where('user_id', auth()->id())
                ->whereNotIn('status', [AttendeeStatus::Cancelled])
                ->whereHas('event', fn ($q) => $q->where('start_date', '>=', now()))
                ->count(),
            'past' => Attendee::query()
                ->where('user_id', auth()->id())
                ->whereNotIn('status', [AttendeeStatus::Cancelled])
                ->whereHas('event', fn ($q) => $q->where('start_date', '<', now()))
                ->count(),
            'all' => Attendee::query()
                ->where('user_id', auth()->id())
                ->whereNotIn('status', [AttendeeStatus::Cancelled])
                ->count(),
        ];
    }
}; ?>

<div class="min-h-screen bg-background py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-foreground">My Tickets</h1>
            <a href="{{ route('events.index') }}" wire:navigate
               class="text-primary hover:text-primary text-sm font-medium">
                {{ __('{{ __('Browse Events') }} →') }}
            </a>
        </div>

        {{-- Filter Tabs --}}
        <div class="flex space-x-1 bg-muted rounded-lg p-1 mb-8">
            <button wire:click="$set('filter', 'upcoming')"
                    class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors
                           {{ $filter === 'upcoming' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                {{ __('Upcoming (') }}{{ $this->ticketCounts['upcoming'] }})
            </button>
            <button wire:click="$set('filter', 'past')"
                    class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors
                           {{ $filter === 'past' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                {{ __('Past (') }}{{ $this->ticketCounts['past'] }})
            </button>
            <button wire:click="$set('filter', 'all')"
                    class="flex-1 py-2 px-4 rounded-md text-sm font-medium transition-colors
                           {{ $filter === 'all' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                {{ __('All (') }}{{ $this->ticketCounts['all'] }})
            </button>
        </div>

        {{-- Tickets List --}}
        @if($this->tickets->isEmpty())
            <div class="text-center py-16 bg-card rounded-xl shadow-sm border border-border">
                <x-heroicon-o-ticket class="mx-auto h-16 w-16 text-muted-foreground" />
                <h3 class="mt-4 text-lg font-semibold text-foreground">{{ __('No tickets found') }}</h3>
                <p class="mt-2 text-muted-foreground">
                    @if($filter === 'upcoming')
                        {{ __('You don\'t have any upcoming event tickets.') }}
                    @elseif($filter === 'past')
                        {{ __('You haven\'t attended any events yet.') }}
                    @else
                        {{ __('You haven\'t registered for any events.') }}
                    @endif
                </p>
                <a href="{{ route('events.index') }}" wire:navigate
                   class="mt-4 inline-flex items-center px-4 py-2 bg-primary text-primary-foreground font-semibold rounded-lg hover:bg-primary/90 transition-colors">
                    Browse Events
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($this->tickets as $ticket)
                    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden hover:shadow-md transition-shadow">
                        <div class="flex">
                            {{-- Event Image --}}
                            <div class="w-32 md:w-48 bg-muted relative flex-shrink-0">
                                @if($ticket->event->thumbnail_image)
                                    <img src="{{ Storage::url($ticket->event->thumbnail_image) }}"
                                         alt="{{ $ticket->event->title }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <x-heroicon-o-calendar class="w-12 h-12 text-muted-foreground" />
                                    </div>
                                @endif

                                {{-- Date Badge --}}
                                <div class="absolute top-2 left-2 bg-card rounded-lg px-2 py-1 text-center shadow-sm">
                                    <div class="text-xs font-semibold text-primary uppercase">
                                        {{ $ticket->event->start_date->format('M') }}
                                    </div>
                                    <div class="text-lg font-bold text-foreground">
                                        {{ $ticket->event->start_date->format('j') }}
                                    </div>
                                </div>
                            </div>

                            {{-- Ticket Details --}}
                            <div class="flex-1 p-4 md:p-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <a href="{{ route('events.show', $ticket->event->slug) }}" wire:navigate
                                           class="font-semibold text-lg text-gray-900 dark:text-white hover:text-primary-600">
                                            {{ $ticket->event->title }}
                                        </a>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $ticket->event->start_date->format('l, F j, Y \a\t g:i A') }}
                                        </p>
                                    </div>

                                    <span class="px-3 py-1 text-xs font-medium rounded-full
                                        {{ match($ticket->status->value) {
                                            'confirmed' => 'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400',
                                            'checked_in' => 'bg-info-100 text-info-800 dark:bg-info-900/20 dark:text-info-400',
                                            'pending_payment' => 'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
                                        } }}">
                                        {{ $ticket->status->getLabel() }}
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div class="flex items-center">
                                        <x-heroicon-o-ticket class="w-4 h-4 mr-1" />
                                        {{ $ticket->ticketType->name }}
                                    </div>
                                    <div class="flex items-center">
                                        <x-heroicon-o-hashtag class="w-4 h-4 mr-1" />
                                        <span class="font-mono">{{ $ticket->reference_no }}</span>
                                    </div>
                                    @if($ticket->event->location_type->value === 'online')
                                        <div class="flex items-center">
                                            <x-heroicon-o-video-camera class="w-4 h-4 mr-1" />
                                            Online
                                        </div>
                                    @else
                                        <div class="flex items-center">
                                            <x-heroicon-o-map-pin class="w-4 h-4 mr-1" />
                                            {{ $ticket->event->location_name ?? 'TBA' }}
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center gap-4">
                                    <a href="{{ route('events.view-ticket', $ticket->reference_no) }}" wire:navigate
                                       class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                                        <x-heroicon-o-qr-code class="w-4 h-4 mr-2" />
                                        View Ticket
                                    </a>

                                    @if($ticket->ticket_pdf_path)
                                        <a href="{{ Storage::url($ticket->ticket_pdf_path) }}" target="_blank"
                                           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                                            Download PDF
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
