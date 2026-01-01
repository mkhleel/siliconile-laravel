<?php

declare(strict_types=1);

use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Modules\SpaceBooking\Models\SpaceResource;
use Modules\SpaceBooking\Services\BookingService;
use Modules\SpaceBooking\Services\PricingService;

new
#[Layout('components.layouts.app', ['title' => 'Book Space'])]
#[Title('Book Space')]
class extends Component
{
    public ?SpaceResource $spaceResource = null;

    #[Url]
    public string $date = '';

    #[Url]
    public string $start = '';

    #[Url]
    public string $end = '';

    public string $notes = '';

    public int $attendeesCount = 1;

    public bool $isAvailable = false;

    public array $priceCalculation = [];

    public array $availableSlots = [];

    public string $errorMessage = '';

    public function mount(string $space): void
    {
        $this->spaceResource = SpaceResource::where('slug', $space)
            ->where('is_active', true)
            ->with(['amenities'])
            ->firstOrFail();

        // Set defaults
        $this->date = $this->date ?: now()->format('Y-m-d');
        $this->attendeesCount = 1;

        // Calculate price if we have all required data
        if ($this->date && $this->start && $this->end) {
            $this->checkAvailabilityAndPrice();
        }

        // Load available time slots
        $this->loadAvailableSlots();
    }

    public function updatedDate(): void
    {
        $this->loadAvailableSlots();
        $this->checkAvailabilityAndPrice();
    }

    public function updatedStart(): void
    {
        $this->checkAvailabilityAndPrice();
    }

    public function updatedEnd(): void
    {
        $this->checkAvailabilityAndPrice();
    }

    protected function loadAvailableSlots(): void
    {
        if (! $this->date) {
            return;
        }

        try {
            $date = Carbon::parse($this->date);
            $bookingService = app(BookingService::class);
            $this->availableSlots = $bookingService->getTimeSlots($this->spaceResource, $date, 30);
        } catch (\Exception $e) {
            $this->availableSlots = [];
        }
    }

    protected function checkAvailabilityAndPrice(): void
    {
        $this->errorMessage = '';
        $this->isAvailable = false;
        $this->priceCalculation = [];

        if (! $this->date || ! $this->start || ! $this->end) {
            return;
        }

        try {
            $startTime = Carbon::parse($this->date.' '.$this->start);
            $endTime = Carbon::parse($this->date.' '.$this->end);

            // Validate end time is after start time
            if ($endTime->lte($startTime)) {
                $this->errorMessage = __('End time must be after start time.');

                return;
            }

            // Check minimum duration
            $durationMinutes = (int) $startTime->diffInMinutes($endTime);
            if ($this->spaceResource->min_booking_minutes && $durationMinutes < $this->spaceResource->min_booking_minutes) {
                $this->errorMessage = __('Minimum booking duration is :min minutes.', ['min' => $this->spaceResource->min_booking_minutes]);

                return;
            }

            // Check maximum duration
            if ($this->spaceResource->max_booking_minutes && $durationMinutes > $this->spaceResource->max_booking_minutes) {
                $this->errorMessage = __('Maximum booking duration is :max minutes.', ['max' => $this->spaceResource->max_booking_minutes]);

                return;
            }

            $bookingService = app(BookingService::class);
            $this->isAvailable = $bookingService->isAvailable($this->spaceResource, $startTime, $endTime);

            if (! $this->isAvailable) {
                $this->errorMessage = __('This time slot is not available. Please select a different time.');

                return;
            }

            // Calculate price
            $pricingService = app(PricingService::class);
            $user = auth()->user();

            // Check if user has a member profile
            $member = $user->member ?? null;

            $this->priceCalculation = $pricingService->calculatePrice(
                $this->spaceResource,
                $startTime,
                $endTime,
                $member
            );

        } catch (\Exception $e) {
            $this->errorMessage = __('Unable to check availability. Please try again.');
        }
    }

    public function createBooking(): void
    {
        $this->validate([
            'date' => 'required|date|after_or_equal:today',
            'start' => 'required',
            'end' => 'required',
            'notes' => 'nullable|string|max:1000',
            'attendeesCount' => 'required|integer|min:1|max:'.($this->spaceResource->capacity ?? 100),
        ]);

        if (! $this->isAvailable) {
            $this->errorMessage = __('This time slot is no longer available.');

            return;
        }

        try {
            $startTime = Carbon::parse($this->date.' '.$this->start);
            $endTime = Carbon::parse($this->date.' '.$this->end);

            $user = auth()->user();
            $bookable = $user->member ?? $user;

            $bookingService = app(BookingService::class);
            $booking = $bookingService->createBooking(
                $this->spaceResource,
                $bookable,
                $startTime,
                $endTime,
                [
                    'notes' => $this->notes,
                    'attendees_count' => $this->attendeesCount,
                ]
            );

            session()->flash('success', __('Your booking has been created successfully!'));
            $this->redirect(route('member.bookings.show', $booking), navigate: true);

        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        } catch (\Exception $e) {
            $this->errorMessage = __('Unable to create booking. Please try again.');
        }
    }

    protected function generateTimeOptions(): array
    {
        $options = [];
        $startHour = $this->spaceResource->available_from
            ? (int) Carbon::parse($this->spaceResource->available_from)->format('H')
            : 8;
        $endHour = $this->spaceResource->available_until
            ? (int) Carbon::parse($this->spaceResource->available_until)->format('H')
            : 22;

        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            $options[] = sprintf('%02d:00', $hour);
            if ($hour < $endHour) {
                $options[] = sprintf('%02d:30', $hour);
            }
        }

        return $options;
    }

    public function with(): array
    {
        return [
            'timeOptions' => $this->generateTimeOptions(),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
    <!-- Breadcrumb -->
    <nav class="flex items-center space-x-2 text-sm text-neutral-500 dark:text-neutral-400">
        <a href="{{ route('member.portal') }}" class="hover:text-primary transition-colors">{{ __('Dashboard') }}</a>
        <span>/</span>
        <a href="{{ route('member.bookings') }}" class="hover:text-primary transition-colors">{{ __('My Bookings') }}</a>
        <span>/</span>
        <span class="text-neutral-900 dark:text-white font-medium">{{ __('New Booking') }}</span>
    </nav>

    <!-- Page Header -->
    <div>
        <flux:heading size="xl" level="1">{{ __('Book') }}: {{ $spaceResource->name }}</flux:heading>
        <flux:subheading size="lg">{{ __('Complete your reservation details') }}</flux:subheading>
    </div>

    <!-- Error Message -->
    @if($errorMessage)
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/20">
            <div class="flex items-start gap-3">
                <flux:icon.exclamation-circle class="size-5 shrink-0 text-red-600 dark:text-red-400" />
                <p class="text-sm text-red-800 dark:text-red-200">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Main Form -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Space Details Card -->
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <!-- Space Image -->
                    <div class="h-32 w-full shrink-0 overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-800 sm:h-28 sm:w-40">
                        @if($spaceResource->image)
                            <img 
                                src="{{ Storage::url($spaceResource->image) }}" 
                                alt="{{ $spaceResource->name }}"
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
                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-2">{{ $spaceResource->name }}</h3>
                        <div class="flex flex-wrap gap-4 text-sm text-neutral-500 dark:text-neutral-400">
                            @if($spaceResource->capacity)
                            <div class="flex items-center gap-1.5">
                                <flux:icon.users class="size-4" />
                                <span>{{ __('Up to :count people', ['count' => $spaceResource->capacity]) }}</span>
                            </div>
                            @endif
                            @if($spaceResource->location)
                            <div class="flex items-center gap-1.5">
                                <flux:icon.map-pin class="size-4" />
                                <span>{{ $spaceResource->location }}</span>
                            </div>
                            @endif
                        </div>
                        @if($spaceResource->hourly_rate)
                        <div class="mt-3">
                            <span class="text-lg font-bold text-neutral-900 dark:text-white">
                                {{ $spaceResource->currency ?? 'EGP' }} {{ number_format($spaceResource->hourly_rate, 0) }}
                            </span>
                            <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('/hour') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Booking Form Card -->
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white mb-6">{{ __('Booking Details') }}</h2>
                
                <form wire:submit="createBooking" class="space-y-6">
                    <!-- Date Selection -->
                    <div>
                        <flux:field>
                            <flux:label>{{ __('Date') }}</flux:label>
                            <flux:input 
                                type="date" 
                                wire:model.live="date" 
                                min="{{ now()->format('Y-m-d') }}"
                                max="{{ now()->addMonths(3)->format('Y-m-d') }}"
                            />
                            <flux:error name="date" />
                        </flux:field>
                    </div>

                    <!-- Time Selection -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:field>
                                <flux:label>{{ __('Start Time') }}</flux:label>
                                <flux:select wire:model.live="start">
                                    <flux:select.option value="">{{ __('Select time') }}</flux:select.option>
                                    @foreach($timeOptions as $time)
                                        <flux:select.option value="{{ $time }}">{{ Carbon::parse($time)->format('g:i A') }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="start" />
                            </flux:field>
                        </div>
                        <div>
                            <flux:field>
                                <flux:label>{{ __('End Time') }}</flux:label>
                                <flux:select wire:model.live="end">
                                    <flux:select.option value="">{{ __('Select time') }}</flux:select.option>
                                    @foreach($timeOptions as $time)
                                        <flux:select.option value="{{ $time }}">{{ Carbon::parse($time)->format('g:i A') }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="end" />
                            </flux:field>
                        </div>
                    </div>

                    <!-- Attendees Count -->
                    @if($spaceResource->capacity)
                    <div>
                        <flux:field>
                            <flux:label>{{ __('Number of Attendees') }}</flux:label>
                            <flux:input 
                                type="number" 
                                wire:model.live="attendeesCount" 
                                min="1" 
                                max="{{ $spaceResource->capacity }}"
                            />
                            <flux:description>{{ __('Maximum :count people allowed', ['count' => $spaceResource->capacity]) }}</flux:description>
                            <flux:error name="attendeesCount" />
                        </flux:field>
                    </div>
                    @endif

                    <!-- Notes -->
                    <div>
                        <flux:field>
                            <flux:label>{{ __('Notes (Optional)') }}</flux:label>
                            <flux:textarea 
                                wire:model="notes" 
                                rows="3" 
                                placeholder="{{ __('Any special requirements or notes for your booking...') }}"
                            />
                            <flux:error name="notes" />
                        </flux:field>
                    </div>

                    <!-- Availability Status -->
                    @if($date && $start && $end)
                        <div class="rounded-lg p-4 {{ $isAvailable ? 'bg-green-50 border border-green-200 dark:bg-green-950/20 dark:border-green-800' : 'bg-red-50 border border-red-200 dark:bg-red-950/20 dark:border-red-800' }}">
                            <div class="flex items-center gap-2">
                                @if($isAvailable)
                                    <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400" />
                                    <span class="font-medium text-green-800 dark:text-green-200">{{ __('This time slot is available!') }}</span>
                                @else
                                    <flux:icon.x-circle class="size-5 text-red-600 dark:text-red-400" />
                                    <span class="font-medium text-red-800 dark:text-red-200">{{ __('This time slot is not available') }}</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Submit Button -->
                    <div class="flex items-center gap-4 pt-4">
                        <flux:button type="submit" variant="primary" class="flex-1" :disabled="!$isAvailable">
                            <span wire:loading.remove wire:target="createBooking">
                                @if($spaceResource->requires_approval)
                                    {{ __('Submit Booking Request') }}
                                @else
                                    {{ __('Confirm Booking') }}
                                @endif
                            </span>
                            <span wire:loading wire:target="createBooking">{{ __('Processing...') }}</span>
                        </flux:button>
                        
                        <flux:button href="{{ route('spaces.show', $spaceResource->slug) }}" variant="ghost">
                            {{ __('Cancel') }}
                        </flux:button>
                    </div>

                    @if($spaceResource->requires_approval)
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">
                            <flux:icon.information-circle class="inline size-4 mr-1" />
                            {{ __('This space requires admin approval. You will be notified once your booking is confirmed.') }}
                        </p>
                    @endif
                </form>
            </div>
        </div>

        <!-- Sidebar - Price Summary -->
        <div class="lg:col-span-1">
            <div class="sticky top-24">
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900 overflow-hidden">
                    <div class="bg-neutral-50 dark:bg-neutral-800/50 p-4 border-b border-neutral-200 dark:border-neutral-700">
                        <h3 class="font-semibold text-neutral-900 dark:text-white">{{ __('Price Summary') }}</h3>
                    </div>
                    
                    <div class="p-4 space-y-4">
                        @if($date && $start && $end && $isAvailable && !empty($priceCalculation))
                            <!-- Booking Summary -->
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between text-neutral-600 dark:text-neutral-400">
                                    <span>{{ __('Date') }}</span>
                                    <span class="font-medium text-neutral-900 dark:text-white">{{ Carbon::parse($date)->format('M j, Y') }}</span>
                                </div>
                                <div class="flex justify-between text-neutral-600 dark:text-neutral-400">
                                    <span>{{ __('Time') }}</span>
                                    <span class="font-medium text-neutral-900 dark:text-white">{{ Carbon::parse($start)->format('g:i A') }} - {{ Carbon::parse($end)->format('g:i A') }}</span>
                                </div>
                                <div class="flex justify-between text-neutral-600 dark:text-neutral-400">
                                    <span>{{ __('Duration') }}</span>
                                    <span class="font-medium text-neutral-900 dark:text-white">
                                        {{ $priceCalculation['quantity'] ?? 0 }} 
                                        {{ ($priceCalculation['price_unit'] ?? null)?->value === 'hourly' ? __('hours') : (($priceCalculation['price_unit'] ?? null)?->value === 'daily' ? __('days') : __('units')) }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-neutral-200 dark:border-neutral-700 pt-4 space-y-2 text-sm">
                                <div class="flex justify-between text-neutral-600 dark:text-neutral-400">
                                    <span>{{ __('Unit Price') }}</span>
                                    <span>{{ $spaceResource->currency ?? 'EGP' }} {{ number_format($priceCalculation['unit_price'] ?? 0, 2) }}</span>
                                </div>
                                
                                @if(($priceCalculation['discount_amount'] ?? 0) > 0)
                                <div class="flex justify-between text-green-600 dark:text-green-400">
                                    <span>{{ __('Discount') }}</span>
                                    <span>-{{ $spaceResource->currency ?? 'EGP' }} {{ number_format($priceCalculation['discount_amount'], 2) }}</span>
                                </div>
                                @endif
                                
                                @if(($priceCalculation['credits_used'] ?? 0) > 0)
                                <div class="flex justify-between text-blue-600 dark:text-blue-400">
                                    <span>{{ __('Credits Applied') }}</span>
                                    <span>-{{ $priceCalculation['credits_used'] }} {{ __('credits') }}</span>
                                </div>
                                @endif
                            </div>

                            <div class="border-t border-neutral-200 dark:border-neutral-700 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-neutral-900 dark:text-white">{{ __('Total') }}</span>
                                    <span class="text-xl font-bold text-neutral-900 dark:text-white">
                                        {{ $spaceResource->currency ?? 'EGP' }} {{ number_format($priceCalculation['total_price'] ?? 0, 2) }}
                                    </span>
                                </div>
                            </div>

                            @if(($priceCalculation['total_price'] ?? 0) == 0)
                                <div class="rounded-lg bg-green-50 dark:bg-green-950/20 p-3">
                                    <p class="text-sm text-green-800 dark:text-green-200">
                                        <flux:icon.sparkles class="inline size-4 mr-1" />
                                        {{ __('This booking is free with your membership credits!') }}
                                    </p>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-6 text-neutral-500 dark:text-neutral-400">
                                <flux:icon.calculator class="size-10 mx-auto mb-3 opacity-50" />
                                <p class="text-sm">{{ __('Select a date and time to see the price') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Help Card -->
                <div class="mt-6 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 p-4 text-center">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-2">{{ __('Need assistance?') }}</p>
                    <a href="{{ route('contact') }}" class="text-sm font-medium text-primary hover:underline">
                        {{ __('Contact our team') }} →
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
