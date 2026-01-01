<?php

declare(strict_types=1);

use Carbon\Carbon;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Modules\SpaceBooking\Models\Booking;
use Modules\SpaceBooking\Enums\BookingStatus;

new
#[Layout('components.layouts.app', ['title' => 'Booking Details'])]
#[Title('Booking Details')]
class extends Component {
    public Booking $booking;

    public function mount(Booking $booking): void
    {
        $user = auth()->user();
        
        // Ensure the booking belongs to the authenticated user
        if ($booking->bookable_id !== $user->id && 
            $booking->bookable_id !== ($user->member?->id ?? 0)) {
            abort(403, 'Unauthorized');
        }
        
        $this->booking = $booking->load(['spaceResource.amenities']);
    }

    public function cancelBooking(): void
    {
        // Check if booking can be cancelled
        if ($this->booking->status === BookingStatus::COMPLETED || 
            $this->booking->status === BookingStatus::CANCELLED) {
            session()->flash('error', __('This booking cannot be cancelled.'));
            return;
        }

        // Check if cancellation is within allowed timeframe (24 hours before)
        if ($this->booking->start_time->diffInHours(now()) < 24) {
            session()->flash('error', __('Bookings must be cancelled at least 24 hours in advance.'));
            return;
        }

        $this->booking->update([
            'status' => BookingStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);
        
        session()->flash('success', __('Booking cancelled successfully.'));
        $this->redirect(route('member.bookings'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-neutral-500 dark:text-neutral-400">
        <a href="{{ route('member.portal') }}" class="hover:text-primary transition-colors">{{ __('Dashboard') }}</a>
        <span>/</span>
        <a href="{{ route('member.bookings') }}" class="hover:text-primary transition-colors">{{ __('My Bookings') }}</a>
        <span>/</span>
        <span class="text-neutral-900 dark:text-white font-medium">{{ $booking->booking_code }}</span>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950/20">
            <p class="text-sm text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/20">
            <p class="text-sm text-red-800 dark:text-red-200">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Page Header -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Booking Details') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Booking Code') }}: {{ $booking->booking_code }}</flux:subheading>
        </div>
        <div class="flex items-center gap-3">
            <flux:badge size="lg" :color="match($booking->status->value) {
                'pending' => 'yellow',
                'confirmed' => 'green',
                'completed' => 'blue',
                'cancelled' => 'red',
                'no_show' => 'zinc',
                default => 'zinc'
            }">
                {{ ucfirst($booking->status->value) }}
            </flux:badge>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Space Details Card -->
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900 overflow-hidden">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">{{ __('Space Details') }}</h2>
                    
                    <div class="flex flex-col gap-4 sm:flex-row">
                        <!-- Space Image -->
                        <div class="h-32 w-full shrink-0 overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-800 sm:h-28 sm:w-40">
                            @if($booking->spaceResource?->image)
                                <img 
                                    src="{{ Storage::url($booking->spaceResource->image) }}" 
                                    alt="{{ $booking->spaceResource->name }}"
                                    class="h-full w-full object-cover"
                                >
                            @else
                                <div class="flex h-full w-full items-center justify-center">
                                    <flux:icon.building-office class="size-10 text-neutral-400" />
                                </div>
                            @endif
                        </div>

                        <!-- Space Info -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">
                                {{ $booking->spaceResource?->name ?? __('Space Deleted') }}
                            </h3>
                            <div class="flex flex-wrap gap-4 text-sm text-neutral-500 dark:text-neutral-400">
                                @if($booking->spaceResource?->capacity)
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.users class="size-4" />
                                    <span>{{ __('Up to :count people', ['count' => $booking->spaceResource->capacity]) }}</span>
                                </div>
                                @endif
                                @if($booking->spaceResource?->location)
                                <div class="flex items-center gap-1.5">
                                    <flux:icon.map-pin class="size-4" />
                                    <span>{{ $booking->spaceResource->location }}</span>
                                </div>
                                @endif
                            </div>
                            
                            @if($booking->spaceResource?->slug)
                            <div class="mt-3">
                                <flux:button href="{{ route('spaces.show', $booking->spaceResource->slug) }}" variant="ghost" size="sm">
                                    {{ __('View Space Details') }} →
                                </flux:button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Information Card -->
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">{{ __('Booking Information') }}</h2>
                    
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Date') }}</p>
                            <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->start_time->format('l, F j, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Time') }}</p>
                            <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Duration') }}</p>
                            <p class="font-medium text-neutral-900 dark:text-white">
                                @php
                                    $hours = $booking->start_time->diffInHours($booking->end_time);
                                    $minutes = $booking->start_time->diffInMinutes($booking->end_time) % 60;
                                @endphp
                                {{ $hours }} {{ __('hours') }}
                                @if($minutes > 0)
                                    {{ $minutes }} {{ __('minutes') }}
                                @endif
                            </p>
                        </div>
                        @if($booking->attendees_count)
                        <div>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Attendees') }}</p>
                            <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->attendees_count }} {{ __('people') }}</p>
                        </div>
                        @endif
                        <div>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Created') }}</p>
                            <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->created_at->format('M j, Y \a\t g:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Payment Status') }}</p>
                            <flux:badge size="sm" :color="match($booking->payment_status?->value ?? 'unpaid') {
                                'paid' => 'green',
                                'partial' => 'yellow',
                                'unpaid' => 'red',
                                'refunded' => 'zinc',
                                default => 'zinc'
                            }">
                                {{ ucfirst($booking->payment_status?->value ?? 'Unpaid') }}
                            </flux:badge>
                        </div>
                    </div>

                    @if($booking->notes)
                    <div class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">{{ __('Notes') }}</p>
                        <p class="text-neutral-900 dark:text-white">{{ $booking->notes }}</p>
                    </div>
                    @endif

                    @if($booking->cancellation_reason)
                    <div class="mt-4 pt-4 border-t border-neutral-200 dark:border-neutral-700">
                        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-1">{{ __('Cancellation Reason') }}</p>
                        <p class="text-red-600 dark:text-red-400">{{ $booking->cancellation_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Amenities (if available) -->
            @if($booking->spaceResource?->amenities && $booking->spaceResource->amenities->count() > 0)
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">{{ __('Included Amenities') }}</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach($booking->spaceResource->amenities as $amenity)
                        <flux:badge variant="outline">
                            {{ $amenity->name }}
                        </flux:badge>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 space-y-6">
                <!-- Price Summary Card -->
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900 overflow-hidden">
                    <div class="bg-neutral-50 dark:bg-neutral-800/50 p-4 border-b border-neutral-200 dark:border-neutral-700">
                        <h3 class="font-semibold text-neutral-900 dark:text-white">{{ __('Price Summary') }}</h3>
                    </div>
                    
                    <div class="p-4 space-y-3 text-sm">
                        <div class="flex justify-between text-neutral-600 dark:text-neutral-400">
                            <span>{{ __('Unit Price') }}</span>
                            <span>{{ $booking->currency ?? 'EGP' }} {{ number_format($booking->unit_price ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-neutral-600 dark:text-neutral-400">
                            <span>{{ __('Quantity') }}</span>
                            <span>{{ $booking->quantity ?? 0 }} {{ $booking->price_unit?->value === 'hourly' ? __('hours') : ($booking->price_unit?->value === 'daily' ? __('days') : __('units')) }}</span>
                        </div>
                        
                        @if(($booking->discount_amount ?? 0) > 0)
                        <div class="flex justify-between text-green-600 dark:text-green-400">
                            <span>{{ __('Discount') }}</span>
                            <span>-{{ $booking->currency ?? 'EGP' }} {{ number_format($booking->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        
                        @if(($booking->credits_used ?? 0) > 0)
                        <div class="flex justify-between text-blue-600 dark:text-blue-400">
                            <span>{{ __('Credits Used') }}</span>
                            <span>{{ $booking->credits_used }} {{ __('credits') }}</span>
                        </div>
                        @endif
                        
                        <div class="border-t border-neutral-200 dark:border-neutral-700 pt-3">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-neutral-900 dark:text-white">{{ __('Total') }}</span>
                                <span class="text-lg font-bold text-neutral-900 dark:text-white">
                                    {{ $booking->currency ?? 'EGP' }} {{ number_format($booking->total_price ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions Card -->
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="p-4 space-y-3">
                        @if(in_array($booking->status->value, ['pending', 'confirmed']))
                            @if($booking->start_time->diffInHours(now()) >= 24)
                                <flux:button 
                                    wire:click="cancelBooking"
                                    wire:confirm="{{ __('Are you sure you want to cancel this booking? This action cannot be undone.') }}"
                                    variant="danger"
                                    class="w-full"
                                >
                                    <flux:icon.x-circle class="size-4 mr-2" />
                                    {{ __('Cancel Booking') }}
                                </flux:button>
                            @else
                                <div class="rounded-lg bg-yellow-50 dark:bg-yellow-950/20 p-3">
                                    <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                        <flux:icon.exclamation-triangle class="inline size-4 mr-1" />
                                        {{ __('Cancellations must be made at least 24 hours in advance.') }}
                                    </p>
                                </div>
                            @endif
                        @endif
                        
                        <flux:button href="{{ route('member.bookings') }}" variant="outline" class="w-full">
                            <flux:icon.arrow-left class="size-4 mr-2" />
                            {{ __('Back to Bookings') }}
                        </flux:button>

                        @if($booking->spaceResource?->slug)
                        <flux:button href="{{ route('member.bookings.create', $booking->spaceResource->slug) }}" variant="ghost" class="w-full">
                            <flux:icon.plus class="size-4 mr-2" />
                            {{ __('Book Again') }}
                        </flux:button>
                        @endif
                    </div>
                </div>

                <!-- Help Card -->
                <div class="rounded-xl bg-neutral-50 dark:bg-neutral-800/50 p-4 text-center">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">{{ __('Need to modify your booking?') }}</p>
                    <a href="{{ route('contact') }}" class="text-sm font-medium text-primary hover:underline">
                        {{ __('Contact our team') }} →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
