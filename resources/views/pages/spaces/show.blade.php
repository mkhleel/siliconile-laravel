<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Modules\SpaceBooking\Models\SpaceResource;

new
#[Layout('layouts.app')]
#[Title('Space Details')]
class extends Component {
    public SpaceResource $space;
    public string $selectedDate = '';
    public string $startTime = '';
    public string $endTime = '';
    public array $availableSlots = [];

    public function mount(string $slug): void
    {
        $this->space = SpaceResource::where('slug', $slug)
            ->where('is_active', true)
            ->with(['amenities'])
            ->firstOrFail();
            
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function checkAvailability(): void
    {
        $this->validate([
            'selectedDate' => 'required|date|after_or_equal:today',
        ]);

        // For now, show a simple message - actual availability check would query bookings
        $this->dispatch('availability-checked');
    }

    public function bookNow(): void
    {
        $this->validate([
            'selectedDate' => 'required|date|after_or_equal:today',
            'startTime' => 'required',
            'endTime' => 'required|after:startTime',
        ]);

        // Redirect to booking flow or show booking modal
        $this->redirect(route('member.bookings.create', [
            'space' => $this->space->slug,
            'date' => $this->selectedDate,
            'start' => $this->startTime,
            'end' => $this->endTime,
        ]));
    }
}; ?>

<div>
    <!-- Breadcrumb -->
    <div class="bg-muted/30 border-b">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <nav class="flex items-center space-x-2 text-sm">
                <a href="{{ route('home') }}" class="text-muted-foreground hover:text-primary transition-colors">{{ __('Home') }}</a>
                <span class="text-muted-foreground">/</span>
                <a href="{{ route('spaces') }}" class="text-muted-foreground hover:text-primary transition-colors">Spaces</a>
                <span class="text-muted-foreground">/</span>
                <span class="text-foreground font-medium">{{ $space->name }}</span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Image Gallery -->
                <div class="relative aspect-video rounded-xl overflow-hidden bg-muted">
                    @if($space->image)
                        <img 
                            src="{{ Storage::url($space->image) }}" 
                            alt="{{ $space->name }}"
                            class="w-full h-full object-cover"
                        >
                    @else
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-24 h-24 text-muted-foreground/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    @endif
                    
                    <!-- Type Badge -->
                    <div class="absolute top-4 left-4">
                        <x-ui.badge variant="default" class="bg-primary/90 backdrop-blur-sm">
                            {{ ucfirst(str_replace('_', ' ', $space->resource_type->value ?? 'Space')) }}
                        </x-ui.badge>
                    </div>
                </div>

                <!-- Title & Quick Info -->
                <div>
                    <h1 class="text-3xl lg:text-4xl font-bold text-foreground mb-4">{{ $space->name }}</h1>
                    
                    <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                        @if($space->capacity)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span>{{ __('Up to') }} {{ $space->capacity }} {{ Str::plural('person', $space->capacity) }}</span>
                        </div>
                        @endif
                        
                        @if($space->location)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ $space->location }}</span>
                        </div>
                        @endif
                        
                        @if($space->available_from && $space->available_until)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ \Carbon\Carbon::parse($space->available_from)->format('g:i A') }} - {{ \Carbon\Carbon::parse($space->available_until)->format('g:i A') }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Description -->
                @if($space->description)
                <div class="prose prose-gray dark:prose-invert max-w-none">
                    <h2 class="text-xl font-semibold mb-4">{{ __('About This Space') }}</h2>
                    {!! nl2br(e($space->description)) !!}
                </div>
                @endif

                <!-- Amenities -->
                @if($space->amenities && $space->amenities->count() > 0)
                <div>
                    <h2 class="text-xl font-semibold mb-4">Amenities</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($space->amenities as $amenity)
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/50">
                            @if($amenity->icon)
                            <span class="text-primary">{!! $amenity->icon !!}</span>
                            @else
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            @endif
                            <span class="text-sm font-medium">{{ $amenity->name }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- {{ __('Booking Guidelines') }} -->
                <div class="bg-muted/30 rounded-xl p-6 space-y-4">
                    <h2 class="text-xl font-semibold">Booking Guidelines</h2>
                    <ul class="space-y-2 text-sm text-muted-foreground">
                        @if($space->min_booking_minutes)
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ __('Minimum booking duration:') }} {{ $space->min_booking_minutes }} minutes</span>
                        </li>
                        @endif
                        @if($space->max_booking_minutes)
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ __('Maximum booking duration:') }} {{ floor($space->max_booking_minutes / 60) }} hours</span>
                        </li>
                        @endif
                        @if($space->buffer_minutes)
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $space->buffer_minutes }} {{ __('minutes buffer between bookings for setup/cleanup') }}</span>
                        </li>
                        @endif
                        @if($space->requires_approval)
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-yellow-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>{{ __('Bookings require admin approval before confirmation') }}</span>
                        </li>
                        @endif
                        <li class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ __('Cancellations must be made at least 24 hours in advance') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Sidebar - Booking Widget -->
            <div class="lg:col-span-1">
                <div class="sticky top-24">
                    <x-ui.card class="overflow-hidden">
                        <!-- Pricing Header -->
                        <div class="bg-primary/5 p-6 border-b">
                            <div class="text-sm text-muted-foreground mb-2">Starting from</div>
                            <div class="flex items-baseline gap-2">
                                @if($space->hourly_rate)
                                <span class="text-3xl font-bold text-foreground">{{ $space->currency ?? 'EGP' }} {{ number_format($space->hourly_rate, 0) }}</span>
                                <span class="text-muted-foreground">{{ __('/hour') }}</span>
                                @elseif($space->daily_rate)
                                <span class="text-3xl font-bold text-foreground">{{ $space->currency ?? 'EGP' }} {{ number_format($space->daily_rate, 0) }}</span>
                                <span class="text-muted-foreground">{{ __('/day') }}</span>
                                @elseif($space->monthly_rate)
                                <span class="text-3xl font-bold text-foreground">{{ $space->currency ?? 'EGP' }} {{ number_format($space->monthly_rate, 0) }}</span>
                                <span class="text-muted-foreground">{{ __('/month') }}</span>
                                @else
                                <span class="text-xl font-medium text-foreground">{{ __('Contact for pricing') }}</span>
                                @endif
                            </div>
                            
                            <!-- All Rates -->
                            @if(($space->hourly_rate && $space->daily_rate) || ($space->daily_rate && $space->monthly_rate))
                            <div class="mt-4 pt-4 border-t border-primary/10 space-y-2 text-sm">
                                @if($space->hourly_rate)
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Hourly rate') }}</span>
                                    <span class="font-medium">{{ $space->currency ?? 'EGP' }} {{ number_format($space->hourly_rate, 0) }}</span>
                                </div>
                                @endif
                                @if($space->daily_rate)
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Daily rate') }}</span>
                                    <span class="font-medium">{{ $space->currency ?? 'EGP' }} {{ number_format($space->daily_rate, 0) }}</span>
                                </div>
                                @endif
                                @if($space->monthly_rate)
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">{{ __('Monthly rate') }}</span>
                                    <span class="font-medium">{{ $space->currency ?? 'EGP' }} {{ number_format($space->monthly_rate, 0) }}</span>
                                </div>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Booking Form -->
                        <div class="p-6 space-y-4">
                            <div>
                                <label for="date" class="block text-sm font-medium mb-2">{{ __('Select Date') }}</label>
                                <x-ui.input 
                                    type="date" 
                                    wire:model="selectedDate" 
                                    min="{{ now()->format('Y-m-d') }}"
                                    class="w-full"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="startTime" class="block text-sm font-medium mb-2">{{ __('Start Time') }}</label>
                                    <x-ui.select wire:model="startTime" class="w-full">
                                        <option value="">Select</option>
                                        @for($hour = 8; $hour <= 22; $hour++)
                                            <option value="{{ sprintf('%02d:00', $hour) }}">{{ sprintf('%02d:00', $hour) }}</option>
                                            <option value="{{ sprintf('%02d:30', $hour) }}">{{ sprintf('%02d:30', $hour) }}</option>
                                        @endfor
                                    </x-ui.select>
                                </div>
                                <div>
                                    <label for="endTime" class="block text-sm font-medium mb-2">{{ __('End Time') }}</label>
                                    <x-ui.select wire:model="endTime" class="w-full">
                                        <option value="">Select</option>
                                        @for($hour = 8; $hour <= 23; $hour++)
                                            <option value="{{ sprintf('%02d:00', $hour) }}">{{ sprintf('%02d:00', $hour) }}</option>
                                            @if($hour < 23)
                                            <option value="{{ sprintf('%02d:30', $hour) }}">{{ sprintf('%02d:30', $hour) }}</option>
                                            @endif
                                        @endfor
                                    </x-ui.select>
                                </div>
                            </div>

                            @auth
                            <x-ui.button wire:click="bookNow" class="w-full" size="lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Book Now
                            </x-ui.button>
                            @else
                            <a href="{{ route('login') }}" class="block">
                                <x-ui.button class="w-full" size="lg">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    Login to Book
                                </x-ui.button>
                            </a>
                            @endauth

                            <p class="text-xs text-center text-muted-foreground">
                                {{ __('No payment required until booking is confirmed') }}
                            </p>
                        </div>
                    </x-ui.card>

                    <!-- Help Card -->
                    <div class="mt-6 p-4 rounded-xl bg-muted/30 text-center">
                        <p class="text-sm text-muted-foreground mb-2">{{ __('Need help choosing?') }}</p>
                        <a href="{{ route('contact') }}" class="text-sm font-medium text-primary hover:underline">
                            {{ __('Contact our team →') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Spaces -->
    @php
        $relatedSpaces = \Modules\SpaceBooking\Models\SpaceResource::where('id', '!=', $space->id)
            ->where('is_active', true)
            ->where('resource_type', $space->resource_type)
            ->limit(3)
            ->get();
    @endphp

    @if($relatedSpaces->count() > 0)
    <x-sections.content title="{{ __('Similar Spaces') }}" class="bg-muted/30">
        <div class="grid md:grid-cols-3 gap-6">
            @foreach($relatedSpaces as $relatedSpace)
            <x-cards.space-card :space="$relatedSpace" />
            @endforeach
        </div>
    </x-sections.content>
    @endif
</div>
