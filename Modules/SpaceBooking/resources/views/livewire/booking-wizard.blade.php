<?php

/**
 * BookingWizard - Multi-step booking component for members.
 *
 * Steps:
 * 1. Select Date & Time
 * 2. Choose available resource
 * 3. Review price quote
 * 4. Confirm & proceed to payment
 */

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Modules\Membership\Models\Member;
use Modules\SpaceBooking\Enums\ResourceType;
use Modules\SpaceBooking\Models\SpaceResource;
use Modules\SpaceBooking\Services\BookingService;
use Modules\SpaceBooking\Services\PricingService;

new class extends Component {
    // ========================================
    // STATE
    // ========================================

    public int $currentStep = 1;

    // Step 1: Date & Time
    #[Validate('required|date|after_or_equal:today')]
    public string $selectedDate = '';

    #[Validate('required')]
    public string $startTime = '';

    #[Validate('required')]
    public string $endTime = '';

    // Step 2: Resource Selection
    public ?string $resourceType = null;

    public ?int $capacity = null;

    public array $selectedAmenities = [];

    public ?int $selectedResourceId = null;

    // Step 3: Price Quote (computed)
    public ?array $priceQuote = null;

    // Step 4: Notes
    public string $notes = '';

    public ?int $attendeesCount = null;

    // UI State
    public bool $isLoading = false;

    public array $errors = [];

    // ========================================
    // LIFECYCLE
    // ========================================

    public function mount(): void
    {
        $this->selectedDate = now()->addDay()->format('Y-m-d');
        $this->startTime = '09:00';
        $this->endTime = '10:00';
    }

    // ========================================
    // COMPUTED PROPERTIES
    // ========================================

    #[Computed]
    public function availableResources(): \Illuminate\Support\Collection
    {
        if (!$this->selectedDate || !$this->startTime || !$this->endTime) {
            return collect();
        }

        try {
            $start = Carbon::parse($this->selectedDate . ' ' . $this->startTime);
            $end = Carbon::parse($this->selectedDate . ' ' . $this->endTime);

            $service = app(BookingService::class);

            $type = $this->resourceType ? ResourceType::from($this->resourceType) : null;

            return $service->getAvailableResources(
                $start,
                $end,
                $type,
                $this->capacity,
                !empty($this->selectedAmenities) ? $this->selectedAmenities : null
            );
        } catch (\Exception $e) {
            return collect();
        }
    }

    #[Computed]
    public function selectedResource(): ?SpaceResource
    {
        if (!$this->selectedResourceId) {
            return null;
        }

        return SpaceResource::with('amenities')->find($this->selectedResourceId);
    }

    #[Computed]
    public function currentMember(): ?Member
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        return Member::where('user_id', $user->id)->first();
    }

    #[Computed]
    public function resourceTypes(): array
    {
        return ResourceType::options();
    }

    #[Computed]
    public function availableAmenities(): \Illuminate\Support\Collection
    {
        return \Modules\SpaceBooking\Models\ResourceAmenity::active()
            ->ordered()
            ->get();
    }

    // ========================================
    // STEP NAVIGATION
    // ========================================

    public function nextStep(): void
    {
        $this->errors = [];

        if (!$this->validateCurrentStep()) {
            return;
        }

        if ($this->currentStep === 2 && $this->selectedResourceId) {
            $this->calculatePrice();
        }

        $this->currentStep = min(4, $this->currentStep + 1);
    }

    public function previousStep(): void
    {
        $this->currentStep = max(1, $this->currentStep - 1);
    }

    public function goToStep(int $step): void
    {
        if ($step <= $this->currentStep) {
            $this->currentStep = $step;
        }
    }

    private function validateCurrentStep(): bool
    {
        return match ($this->currentStep) {
            1 => $this->validateStep1(),
            2 => $this->validateStep2(),
            3 => $this->validateStep3(),
            default => true,
        };
    }

    private function validateStep1(): bool
    {
        if (empty($this->selectedDate)) {
            $this->errors['selectedDate'] = 'Please select a date.';
            return false;
        }

        if (empty($this->startTime) || empty($this->endTime)) {
            $this->errors['time'] = 'Please select start and end times.';
            return false;
        }

        try {
            $start = Carbon::parse($this->selectedDate . ' ' . $this->startTime);
            $end = Carbon::parse($this->selectedDate . ' ' . $this->endTime);

            if ($end->lte($start)) {
                $this->errors['endTime'] = 'End time must be after start time.';
                return false;
            }

            if ($start->lt(now())) {
                $this->errors['startTime'] = 'Cannot book in the past.';
                return false;
            }
        } catch (\Exception $e) {
            $this->errors['time'] = 'Invalid date/time format.';
            return false;
        }

        return true;
    }

    private function validateStep2(): bool
    {
        if (!$this->selectedResourceId) {
            $this->errors['resource'] = 'Please select a resource.';
            return false;
        }

        // Verify resource is still available
        $resource = $this->selectedResource;
        if (!$resource) {
            $this->errors['resource'] = 'Selected resource not found.';
            return false;
        }

        $start = Carbon::parse($this->selectedDate . ' ' . $this->startTime);
        $end = Carbon::parse($this->selectedDate . ' ' . $this->endTime);

        if (!$resource->isAvailable($start, $end)) {
            $this->errors['resource'] = 'This resource is no longer available for the selected time.';
            return false;
        }

        return true;
    }

    private function validateStep3(): bool
    {
        return $this->priceQuote !== null;
    }

    // ========================================
    // PRICING
    // ========================================

    public function calculatePrice(): void
    {
        if (!$this->selectedResource) {
            return;
        }

        try {
            $start = Carbon::parse($this->selectedDate . ' ' . $this->startTime);
            $end = Carbon::parse($this->selectedDate . ' ' . $this->endTime);

            $pricingService = app(PricingService::class);

            $this->priceQuote = $pricingService->getQuote(
                $this->selectedResource,
                $start,
                $end,
                $this->currentMember
            );
        } catch (\Exception $e) {
            $this->errors['pricing'] = 'Unable to calculate price. Please try again.';
        }
    }

    // ========================================
    // BOOKING CONFIRMATION
    // ========================================

    public function confirmBooking(): void
    {
        $this->isLoading = true;
        $this->errors = [];

        try {
            $resource = $this->selectedResource;
            if (!$resource) {
                throw new \RuntimeException('No resource selected.');
            }

            $start = Carbon::parse($this->selectedDate . ' ' . $this->startTime);
            $end = Carbon::parse($this->selectedDate . ' ' . $this->endTime);

            // Determine bookable entity
            $bookable = $this->currentMember ?? auth()->user();
            if (!$bookable) {
                throw new \RuntimeException('You must be logged in to make a booking.');
            }

            $bookingService = app(BookingService::class);

            $booking = $bookingService->createBooking(
                $resource,
                $bookable,
                $start,
                $end,
                [
                    'notes' => $this->notes,
                    'attendees_count' => $this->attendeesCount,
                ]
            );

            // Emit event for payment processing
            $this->dispatch('booking-created', bookingId: $booking->id);

            // Reset wizard
            $this->reset(['selectedResourceId', 'priceQuote', 'notes', 'attendeesCount']);
            $this->currentStep = 1;

            session()->flash('success', 'Booking confirmed! Booking code: ' . $booking->booking_code);

        } catch (\Exception $e) {
            $this->errors['booking'] = $e->getMessage();
        } finally {
            $this->isLoading = false;
        }
    }

    // ========================================
    // RESOURCE SELECTION
    // ========================================

    public function selectResource(int $resourceId): void
    {
        $this->selectedResourceId = $resourceId;
        $this->priceQuote = null; // Reset quote when resource changes
    }

    // ========================================
    // HELPERS
    // ========================================

    public function getTimeSlots(): array
    {
        $slots = [];
        $start = Carbon::today()->setHour(6);
        $end = Carbon::today()->setHour(22);

        while ($start->lt($end)) {
            $slots[$start->format('H:i')] = $start->format('g:i A');
            $start->addMinutes(30);
        }

        return $slots;
    }
};
?>

<div class="max-w-4xl mx-auto">
    {{-- Progress Steps --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @foreach([1 => 'Date & Time', 2 => 'Select Resource', 3 => 'Review Price', 4 => 'Confirm'] as $step => $label)
                <div class="flex items-center {{ $step < 4 ? 'flex-1' : '' }}">
                    <button
                        wire:click="goToStep({{ $step }})"
                        @class([
                            'w-10 h-10 rounded-full flex items-center justify-center font-semibold text-sm transition-colors',
                            'bg-primary-600 text-white' => $currentStep >= $step,
                            'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => $currentStep < $step,
                            'cursor-pointer hover:ring-2 hover:ring-primary-300' => $step <= $currentStep,
                            'cursor-not-allowed' => $step > $currentStep,
                        ])
                        @disabled($step > $currentStep)
                    >
                        {{ $step }}
                    </button>
                    <span class="ml-2 text-sm font-medium {{ $currentStep >= $step ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $label }}
                    </span>
                    @if($step < 4)
                        <div class="flex-1 h-0.5 mx-4 {{ $currentStep > $step ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Error Messages --}}
    @if(!empty($errors))
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            @foreach($errors as $error)
                <p class="text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Success Message --}}
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Step Content --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        {{-- Step 1: Date & Time --}}
        @if($currentStep === 1)
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Select Date & Time</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="selectedDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Date
                        </label>
                        <input
                            type="date"
                            id="selectedDate"
                            wire:model="selectedDate"
                            min="{{ now()->format('Y-m-d') }}"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="startTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Start Time
                            </label>
                            <select
                                id="startTime"
                                wire:model="startTime"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                            >
                                @foreach($this->getTimeSlots() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="endTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                End Time
                            </label>
                            <select
                                id="endTime"
                                wire:model="endTime"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                            >
                                @foreach($this->getTimeSlots() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Quick Duration Buttons --}}
                <div>
                    <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Quick Duration</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach([1 => '1 Hour', 2 => '2 Hours', 4 => 'Half Day', 8 => 'Full Day'] as $hours => $label)
                            <button
                                type="button"
                                wire:click="$set('endTime', '{{ Carbon\Carbon::parse($startTime)->addHours($hours)->format('H:i') }}')"
                                class="px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Step 2: Resource Selection --}}
        @if($currentStep === 2)
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Choose a Resource</h2>

                {{-- Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <div>
                        <label for="resourceType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Resource Type
                        </label>
                        <select
                            id="resourceType"
                            wire:model.live="resourceType"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option value="">All Types</option>
                            @foreach($this->resourceTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="capacity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Min Capacity
                        </label>
                        <input
                            type="number"
                            id="capacity"
                            wire:model.live.debounce.300ms="capacity"
                            min="1"
                            placeholder="Any"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Amenities
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($this->availableAmenities->take(4) as $amenity)
                                <label class="inline-flex items-center">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedAmenities"
                                        value="{{ $amenity->id }}"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
                                    >
                                    <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">{{ $amenity->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Available Resources Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($this->availableResources as $resource)
                        <button
                            type="button"
                            wire:click="selectResource({{ $resource->id }})"
                            @class([
                                'text-left p-4 rounded-lg border-2 transition-all',
                                'border-primary-500 bg-primary-50 dark:bg-primary-900/20' => $selectedResourceId === $resource->id,
                                'border-gray-200 dark:border-gray-700 hover:border-primary-300' => $selectedResourceId !== $resource->id,
                            ])
                        >
                            <div class="flex items-start gap-4">
                                @if($resource->image)
                                    <img src="{{ Storage::url($resource->image) }}" alt="{{ $resource->name }}" class="w-20 h-20 rounded-lg object-cover">
                                @else
                                    <div class="w-20 h-20 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <x-heroicon-o-building-office class="w-8 h-8 text-gray-400" />
                                    </div>
                                @endif

                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $resource->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $resource->resource_type->label() }} · {{ $resource->capacity }} people
                                    </p>
                                    <p class="text-sm font-medium text-primary-600 dark:text-primary-400 mt-1">
                                        {{ $resource->getFormattedPrice($resource->getDefaultPriceUnit()) }}
                                    </p>

                                    @if($resource->amenities->count() > 0)
                                        <div class="flex flex-wrap gap-1 mt-2">
                                            @foreach($resource->amenities->take(3) as $amenity)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                    {{ $amenity->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                @if($selectedResourceId === $resource->id)
                                    <x-heroicon-s-check-circle class="w-6 h-6 text-primary-600" />
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="col-span-2 text-center py-12">
                            <x-heroicon-o-calendar-days class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                            <p class="text-gray-500 dark:text-gray-400">No resources available for the selected time slot.</p>
                            <button
                                type="button"
                                wire:click="previousStep"
                                class="mt-4 text-primary-600 hover:text-primary-700 font-medium"
                            >
                                Try a different time
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- Step 3: Price Review --}}
        @if($currentStep === 3)
            <div class="space-y-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Review Your Booking</h2>

                @if($this->selectedResource && $priceQuote)
                    {{-- Booking Summary --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Booking Details</h3>

                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Resource:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $this->selectedResource->name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Date:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Time:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $startTime }} - {{ $endTime }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Duration:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $priceQuote['quantity'] }} {{ $priceQuote['price_unit']->pluralLabel() }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h3 class="font-medium text-gray-700 dark:text-gray-300">Price Breakdown</h3>

                            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Unit Price:</span>
                                    <span class="text-gray-900 dark:text-white">{{ number_format($priceQuote['unit_price'], 2) }} SDG / {{ $priceQuote['price_unit']->label() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Quantity:</span>
                                    <span class="text-gray-900 dark:text-white">{{ $priceQuote['quantity'] }} {{ $priceQuote['price_unit']->pluralLabel() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Base Price:</span>
                                    <span class="text-gray-900 dark:text-white">{{ number_format($priceQuote['base_price'], 2) }} SDG</span>
                                </div>

                                @if($priceQuote['available_credits'] > 0)
                                    <div class="flex justify-between text-green-600 dark:text-green-400">
                                        <span>Credits Applied:</span>
                                        <span>-{{ number_format($priceQuote['available_credits'], 2) }} {{ $priceQuote['price_unit']->pluralLabel() }}</span>
                                    </div>
                                @endif

                                @if($priceQuote['discount_percent'] > 0)
                                    <div class="flex justify-between text-green-600 dark:text-green-400">
                                        <span>Plan Discount ({{ $priceQuote['discount_percent'] }}%):</span>
                                        <span>-{{ number_format($priceQuote['base_price'] * $priceQuote['discount_percent'] / 100, 2) }} SDG</span>
                                    </div>
                                @endif

                                <hr class="border-gray-200 dark:border-gray-700">

                                <div class="flex justify-between text-lg font-semibold">
                                    <span class="text-gray-900 dark:text-white">Total:</span>
                                    <span class="text-primary-600 dark:text-primary-400">{{ $priceQuote['formatted_total'] }}</span>
                                </div>
                            </div>

                            @if($this->currentMember && $priceQuote['available_credits'] > 0)
                                <div class="text-sm text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                                    <x-heroicon-o-gift class="w-4 h-4 inline mr-1" />
                                    You have {{ $priceQuote['available_credits'] }} free {{ $priceQuote['price_unit']->pluralLabel() }} from your membership plan!
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Additional Notes --}}
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Notes (Optional)
                        </label>
                        <textarea
                            id="notes"
                            wire:model="notes"
                            rows="3"
                            placeholder="Any special requests or notes for your booking..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-primary-500 focus:border-primary-500"
                        ></textarea>
                    </div>

                    @if($this->selectedResource->resource_type === \Modules\SpaceBooking\Enums\ResourceType::MEETING_ROOM)
                        <div>
                            <label for="attendeesCount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Number of Attendees
                            </label>
                            <input
                                type="number"
                                id="attendeesCount"
                                wire:model="attendeesCount"
                                min="1"
                                max="{{ $this->selectedResource->capacity }}"
                                placeholder="How many people will attend?"
                                class="w-full md:w-1/3 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                        </div>
                    @endif
                @endif
            </div>
        @endif

        {{-- Step 4: Confirmation --}}
        @if($currentStep === 4)
            <div class="space-y-6 text-center py-8">
                <div class="w-16 h-16 mx-auto bg-primary-100 dark:bg-primary-900/30 rounded-full flex items-center justify-center">
                    <x-heroicon-o-check-circle class="w-10 h-10 text-primary-600 dark:text-primary-400" />
                </div>

                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Ready to Book?</h2>

                <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                    You're about to book <strong>{{ $this->selectedResource?->name }}</strong>
                    on <strong>{{ Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}</strong>
                    from <strong>{{ $startTime }}</strong> to <strong>{{ $endTime }}</strong>.
                </p>

                @if($priceQuote && $priceQuote['estimated_total'] > 0)
                    <p class="text-lg font-semibold text-primary-600 dark:text-primary-400">
                        Total: {{ $priceQuote['formatted_total'] }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Payment will be required to complete the booking.
                    </p>
                @else
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                        Free (covered by your membership)
                    </p>
                @endif

                <button
                    type="button"
                    wire:click="confirmBooking"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center px-8 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="confirmBooking">Confirm Booking</span>
                    <span wire:loading wire:target="confirmBooking" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    </span>
                </button>
            </div>
        @endif
    </div>

    {{-- Navigation Buttons --}}
    <div class="flex justify-between mt-6">
        <button
            type="button"
            wire:click="previousStep"
            @class([
                'px-6 py-2 text-gray-700 dark:text-gray-300 font-medium rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors',
                'invisible' => $currentStep === 1,
            ])
        >
            Back
        </button>

        @if($currentStep < 4)
            <button
                type="button"
                wire:click="nextStep"
                class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
            >
                Continue
            </button>
        @endif
    </div>
</div>
