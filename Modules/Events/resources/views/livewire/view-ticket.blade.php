<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Modules\Events\Enums\AttendeeStatus;
use Modules\Events\Models\Attendee;
use Modules\Events\Services\TicketService;

/**
 * View Ticket Component
 *
 * Displays a single ticket with QR code for check-in.
 */
new #[Layout('components.layouts.app')] class extends Component {
    public Attendee $attendee;

    /**
     * Mount the component.
     */
    public function mount(string $reference): void
    {
        $this->attendee = Attendee::query()
            ->where('reference_no', $reference)
            ->with(['event', 'ticketType'])
            ->firstOrFail();

        // Ensure user owns this ticket
        if (auth()->id() !== $this->attendee->user_id) {
            abort(403, 'You do not have access to this ticket.');
        }
    }

    /**
     * Generate QR code SVG.
     */
    public function getQrCodeSvg(): string
    {
        $ticketService = app(TicketService::class);
        return $ticketService->generateQrCodeSvg($this->attendee);
    }
}; ?>

<div class="min-h-screen bg-background py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Back Link --}}
        <a href="{{ route('events.my-tickets') }}" wire:navigate
           class="inline-flex items-center text-sm text-muted-foreground hover:text-foreground mb-6">
            <x-heroicon-o-arrow-left class="w-4 h-4 mr-2" />
            Back to My Tickets
        </a>

        {{-- Ticket Card --}}
        <div class="bg-card rounded-2xl shadow-lg overflow-hidden">
            {{-- Event Header --}}
            <div class="relative bg-gradient-to-r from-primary to-primary p-6 text-primary-foreground">
                @if($attendee->event->banner_image)
                    <div class="absolute inset-0">
                        <img src="{{ Storage::url($attendee->event->banner_image) }}"
                             alt="{{ $attendee->event->title }}"
                             class="w-full h-full object-cover opacity-20">
                    </div>
                @endif

                <div class="relative">
                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-white/20 mb-3">
                        {{ $attendee->event->type->getLabel() }}
                    </span>
                    <h1 class="text-2xl font-bold mb-2">{{ $attendee->event->title }}</h1>
                    <p class="text-primary-100">
                        {{ $attendee->event->start_date->format('l, F j, Y') }}
                    </p>
                    <p class="text-primary-100">
                        {{ $attendee->event->start_date->format('g:i A') }}
                        @if($attendee->event->end_date)
                            - {{ $attendee->event->end_date->format('g:i A') }}
                        @endif
                        ({{ $attendee->event->timezone }})
                    </p>
                </div>
            </div>

            {{-- Ticket Status --}}
            <div class="border-b border-border p-4 text-center">
                <span class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-full
                    {{ match($attendee->status->value) {
                        'confirmed' => 'bg-success-100 text-success-800',
                        'checked_in' => 'bg-info-100 text-info-800',
                        'pending_payment' => 'bg-warning-100 text-warning-800',
                        default => 'bg-muted text-muted-foreground'
                    } }}">
                    @if($attendee->status === \Modules\Events\Enums\AttendeeStatus::CheckedIn)
                        <x-heroicon-o-check-badge class="w-5 h-5 mr-2" />
                    @endif
                    {{ $attendee->status->getLabel() }}
                </span>

                @if($attendee->checked_in_at)
                    <p class="text-sm text-muted-foreground mt-2">
                        {{ __('Checked in at') }} {{ $attendee->checked_in_at->format('g:i A \o\n M j, Y') }}
                    </p>
                @endif
            </div>

            {{-- QR Code Section --}}
            <div class="p-8 text-center">
                <div class="inline-block bg-card p-4 rounded-xl shadow-sm mb-4">
                    {!! $this->getQrCodeSvg() !!}
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ __('Scan this QR code at the event for check-in') }}
                </p>
            </div>

            {{-- Ticket Details --}}
            <div class="border-t border-border p-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <span class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('Ticket Type') }}</span>
                        <p class="font-semibold text-foreground mt-1">{{ $attendee->ticketType->name }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('Reference Number') }}</span>
                        <p class="font-mono font-semibold text-foreground mt-1">{{ $attendee->reference_no }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('Attendee Name') }}</span>
                        <p class="font-semibold text-foreground mt-1">{{ $attendee->name }}</p>
                    </div>
                    <div>
                        <span class="text-xs text-muted-foreground uppercase tracking-wide">{{ __('Email') }}</span>
                        <p class="font-semibold text-foreground mt-1">{{ $attendee->email }}</p>
                    </div>
                </div>

                {{-- Location --}}
                <div class="mt-6 pt-6 border-t border-border">
                    <span class="text-xs text-muted-foreground uppercase tracking-wide">Location</span>
                    <div class="mt-2 flex items-start">
                        @if($attendee->event->location_type->value === 'online')
                            <x-heroicon-o-video-camera class="w-5 h-5 text-muted-foreground mr-3 mt-0.5" />
                            <div>
                                <p class="font-semibold text-foreground">{{ __('Online Event') }}</p>
                                @if($attendee->event->location_link && $attendee->status === \Modules\Events\Enums\AttendeeStatus::Confirmed)
                                    <a href="{{ $attendee->event->location_link }}" target="_blank"
                                       class="text-primary hover:underline text-sm mt-1 inline-block">
                                        Join Meeting →
                                    </a>
                                @endif
                            </div>
                        @else
                            <x-heroicon-o-map-pin class="w-5 h-5 text-muted-foreground mr-3 mt-0.5" />
                            <div>
                                <p class="font-semibold text-foreground">
                                    {{ $attendee->event->location_name }}
                                </p>
                                @if($attendee->event->location_address)
                                    <p class="text-muted-foreground text-sm mt-1">
                                        {{ $attendee->event->location_address }}
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                {{-- {{ __('Special Requirements') }} --}}
                @if($attendee->special_requirements)
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Special Requirements</span>
                        <p class="text-gray-700 dark:text-gray-300 mt-2">{{ $attendee->special_requirements }}</p>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="border-t border-gray-200 dark:border-gray-700 p-6 bg-gray-50 dark:bg-gray-900">
                <div class="flex flex-col sm:flex-row gap-4">
                    @if($attendee->ticket_pdf_path)
                        <a href="{{ Storage::url($attendee->ticket_pdf_path) }}" target="_blank"
                           class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                            <x-heroicon-o-arrow-down-tray class="w-5 h-5 mr-2" />
                            Download PDF Ticket
                        </a>
                    @endif

                    <a href="{{ route('events.show', $attendee->event->slug) }}" wire:navigate
                       class="flex-1 inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <x-heroicon-o-calendar class="w-5 h-5 mr-2" />
                        View Event Details
                    </a>
                </div>
            </div>
        </div>

        {{-- Add to Calendar --}}
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ __('Add to your calendar') }}</p>
            <div class="flex justify-center space-x-4">
                <a href="#" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <span class="sr-only">{{ __('Google Calendar') }}</span>
                    {{-- Google Calendar Icon --}}
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.5 3h-15A1.5 1.5 0 003 4.5v15A1.5 1.5 0 004.5 21h15a1.5 1.5 0 001.5-1.5v-15A1.5 1.5 0 0019.5 3zM8 17.5a1 1 0 110-2 1 1 0 010 2zm0-4a1 1 0 110-2 1 1 0 010 2zm4 4a1 1 0 110-2 1 1 0 010 2zm0-4a1 1 0 110-2 1 1 0 010 2zm4 4a1 1 0 110-2 1 1 0 010 2zm0-4a1 1 0 110-2 1 1 0 010 2zm2-5H6V6h12v2.5z"/>
                    </svg>
                </a>
                <a href="#" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <span class="sr-only">{{ __('Apple Calendar') }}</span>
                    {{-- Apple Calendar Icon --}}
                    <svg class="w-8 h-8" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 2c-.55 0-1 .45-1 1v1H8V3c0-.55-.45-1-1-1s-1 .45-1 1v1H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2h-2V3c0-.55-.45-1-1-1zM4 20V9h16v11H4z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
