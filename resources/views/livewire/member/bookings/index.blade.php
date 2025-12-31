<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Modules\SpaceBooking\Models\Booking;
use Modules\SpaceBooking\Enums\BookingStatus;

new
#[Layout('components.layouts.app', ['title' => 'My Bookings'])]
#[Title('My Bookings')]
class extends Component {
    use WithPagination;

    public string $filterStatus = '';
    public string $search = '';

    public function with(): array
    {
        $user = auth()->user();
        
        $bookings = Booking::where('bookable_id', $user->id)
            ->where('bookable_type', get_class($user))
            ->with(['spaceResource'])
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn($q) => $q->whereHas('spaceResource', fn($sq) => $sq->where('name', 'like', '%' . $this->search . '%')))
            ->latest('start_time')
            ->paginate(10);

        return [
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
        ];
    }

    public function cancelBooking(int $bookingId): void
    {
        $user = auth()->user();
        
        $booking = Booking::where('bookable_id', $user->id) 
            ->where('bookable_type', get_class($user))
            ->findOrFail($bookingId);
        
        // Check if booking can be cancelled (e.g., not already completed/cancelled)
        if ($booking->status === BookingStatus::Completed || $booking->status === BookingStatus::Cancelled) {
            session()->flash('error', 'This booking cannot be cancelled.');
            return;
        }

        // Check if cancellation is within allowed timeframe (24 hours before)
        if ($booking->start_time->diffInHours(now()) < 24) {
            session()->flash('error', 'Bookings must be cancelled at least 24 hours in advance.');
            return;
        }

        $booking->update(['status' => BookingStatus::Cancelled]);
        session()->flash('success', 'Booking cancelled successfully.');
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <!-- Page Header -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="relative w-full">
            <flux:heading size="xl" level="1">{{ __('My Bookings') }}</flux:heading>
            <flux:subheading size="lg">{{ __('Manage your space reservations') }}</flux:subheading>
        </div>
        <flux:button href="{{ route('spaces') }}" icon="plus">{{ __('Book a Space') }}</flux:button>
    </div>

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

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by space name...') }}" icon="magnifying-glass" />
            </div>
            <div class="w-full md:w-48">
                <flux:select wire:model.live="filterStatus">
                    <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                    @foreach($statuses as $status)
                        <flux:select.option value="{{ $status->value }}">{{ ucfirst($status->value) }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Bookings List -->
    @if($bookings->count() > 0)
        <div class="space-y-4">
            @foreach($bookings as $booking)
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="p-6">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                            <!-- Space Image -->
                            <div class="h-24 w-full shrink-0 overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-800 lg:h-20 lg:w-32">
                                @if($booking->spaceResource?->image)
                                    <img 
                                        src="{{ Storage::url($booking->spaceResource->image) }}" 
                                        alt="{{ $booking->spaceResource->name }}"
                                        class="h-full w-full object-cover"
                                    >
                                @else
                                    <div class="flex h-full w-full items-center justify-center">
                                        <flux:icon.building-office class="size-8 text-neutral-400" />
                                    </div>
                                @endif
                            </div>

                            <!-- Booking Details -->
                            <div class="min-w-0 flex-1">
                                <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                                    <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $booking->spaceResource?->name ?? 'Space Deleted' }}</h3>
                                    <flux:badge :color="match($booking->status->value ?? 'pending') {
                                        'pending' => 'yellow',
                                        'confirmed' => 'green',
                                        'completed' => 'blue',
                                        'cancelled' => 'red',
                                        'rejected' => 'zinc',
                                        default => 'zinc'
                                    }" size="sm">
                                        {{ ucfirst($booking->status->value ?? 'pending') }}
                                    </flux:badge>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Date') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->start_time->format('M j, Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Time') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Duration') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->start_time->diffInHours($booking->end_time) }} {{ __('hours') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Total') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->currency ?? 'EGP' }} {{ number_format($booking->total_price ?? 0, 2) }}</p>
                                    </div>
                                </div>

                                @if($booking->notes)
                                    <p class="mt-3 line-clamp-2 text-sm text-neutral-500 dark:text-neutral-400">{{ $booking->notes }}</p>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex shrink-0 flex-row gap-2 lg:flex-col">
                                <flux:button href="{{ route('member.bookings.show', $booking) }}" variant="outline" size="sm">{{ __('View Details') }}</flux:button>
                                @if($booking->status->value === 'pending' || $booking->status->value === 'confirmed')
                                    <flux:button 
                                        wire:click="cancelBooking({{ $booking->id }})"
                                        wire:confirm="{{ __('Are you sure you want to cancel this booking?') }}"
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:bg-red-50 dark:hover:bg-red-950/20"
                                    >
                                        {{ __('Cancel') }}
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-2">
            {{ $bookings->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
            <div class="p-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                    <flux:icon.calendar class="size-8 text-neutral-400" />
                </div>
                <flux:heading size="lg" class="mb-2">{{ __('No bookings yet') }}</flux:heading>
                <flux:subheading class="mb-6">{{ __("You haven't made any space reservations yet.") }}</flux:subheading>
                <flux:button href="{{ route('spaces') }}">{{ __('Browse Available Spaces') }}</flux:button>
            </div>
        </div>
    @endif
</div>
