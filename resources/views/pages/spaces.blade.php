<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Computed};
use Modules\SpaceBooking\Models\SpaceResource;
use Modules\SpaceBooking\Enums\ResourceType;

new
#[Layout('layouts.app')]
#[Title('Siliconile | Book a Space')]
class extends Component
{
    public string $selectedType = '';
    public int $selectedCapacity = 0;

    public function with(): array
    {
        $query = SpaceResource::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($this->selectedType) {
            $query->where('resource_type', $this->selectedType);
        }

        if ($this->selectedCapacity > 0) {
            $query->where('capacity', '>=', $this->selectedCapacity);
        }

        $resources = $query->get();

        $resourceTypes = collect(ResourceType::cases())->mapWithKeys(fn ($type) => [
            $type->value => $type->label(),
        ])->toArray();

        return [
            'resources' => $resources,
            'resourceTypes' => $resourceTypes,
        ];
    }
};

?>

<main class="flex-1">
    <x-sections.hero
        title="Book a <span class='text-primary'>Space</span>"
        subtitle="Reserve meeting rooms, private offices, or hot desks for your next project. Flexible booking options to fit your schedule."
    />

    <!-- Filters Section -->
    <section class="py-8 border-b">
        <div class="container px-4 md:px-6">
            <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                <h2 class="text-2xl font-bold">Available Spaces</h2>
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="w-full sm:w-48">
                        <select
                            wire:model.live="selectedType"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">All Types</option>
                            @foreach($resourceTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full sm:w-48">
                        <select
                            wire:model.live="selectedCapacity"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="0">Any Capacity</option>
                            <option value="2">2+ people</option>
                            <option value="4">4+ people</option>
                            <option value="8">8+ people</option>
                            <option value="12">12+ people</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Resources Grid -->
    <x-sections.content>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-50">
            @forelse($resources as $resource)
                <x-cards.space-card
                    :name="$resource->name"
                    :description="$resource->description"
                    :type="$resource->resource_type->label()"
                    :capacity="$resource->capacity"
                    :hourlyRate="$resource->hourly_rate ? number_format($resource->hourly_rate, 0) . ' ' . $resource->currency : null"
                    :dailyRate="$resource->daily_rate ? number_format($resource->daily_rate, 0) . ' ' . $resource->currency : null"
                    :image="$resource->image ? asset('storage/' . $resource->image) : null"
                    :amenities="$resource->attributes['amenities'] ?? []"
                    :available="$resource->is_active"
                    action="Book Now"
                    :actionUrl="route('spaces.show', $resource->slug)"
                />
            @empty
                <!-- Fallback sample spaces if none in database -->
                <x-cards.space-card
                    name="Hot Desk"
                    description="Flexible workspace in our open-plan area. Perfect for independent work or casual collaboration."
                    type="Hot Desk"
                    :capacity="1"
                    hourlyRate="50 EGP"
                    dailyRate="150 EGP"
                    :amenities="['WiFi', 'Power Outlets', 'Ergonomic Chair', 'Natural Light']"
                    action="Book Now"
                    :actionUrl="route('coworking')"
                />

                <x-cards.space-card
                    name="Meeting Room - Small"
                    description="Intimate meeting room perfect for client calls, interviews, or small team discussions."
                    type="Meeting Room"
                    :capacity="4"
                    hourlyRate="100 EGP"
                    dailyRate="600 EGP"
                    :amenities="['TV Screen', 'Whiteboard', 'Video Conferencing', 'Air Conditioning']"
                    action="Book Now"
                    :actionUrl="route('coworking')"
                />

                <x-cards.space-card
                    name="Meeting Room - Large"
                    description="Spacious conference room for team meetings, workshops, or presentations."
                    type="Meeting Room"
                    :capacity="12"
                    hourlyRate="200 EGP"
                    dailyRate="1200 EGP"
                    :amenities="['Projector', 'Whiteboard', 'Video Conferencing', 'Air Conditioning', 'Sound System']"
                    action="Book Now"
                    :actionUrl="route('coworking')"
                />

                <x-cards.space-card
                    name="Private Office - 2 Person"
                    description="Enclosed office space for focused work. Ideal for freelancers or small teams."
                    type="Private Office"
                    :capacity="2"
                    dailyRate="300 EGP"
                    :amenities="['Dedicated Desk', 'Locker', 'WiFi', 'Air Conditioning', '24/7 Access']"
                    action="Inquire"
                    :actionUrl="route('contact')"
                />

                <x-cards.space-card
                    name="Private Office - 4 Person"
                    description="Team office with private space for collaboration and focused work."
                    type="Private Office"
                    :capacity="4"
                    dailyRate="500 EGP"
                    :amenities="['4 Dedicated Desks', 'Lockers', 'WiFi', 'Air Conditioning', '24/7 Access', 'Meeting Room Credits']"
                    action="Inquire"
                    :actionUrl="route('contact')"
                />

                <x-cards.space-card
                    name="Event Space"
                    description="Large multi-purpose space for workshops, networking events, or presentations."
                    type="Event Space"
                    :capacity="50"
                    hourlyRate="500 EGP"
                    :amenities="['Projector', 'Sound System', 'Flexible Seating', 'Air Conditioning', 'Catering Area']"
                    action="Inquire"
                    :actionUrl="route('contact')"
                />
            @endforelse
        </div>

        @if($resources->isEmpty() && ($selectedType || $selectedCapacity))
            <div class="text-center py-12">
                <svg class="h-16 w-16 text-muted-foreground mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-lg font-semibold mb-2">No spaces found</h3>
                <p class="text-muted-foreground mb-4">Try adjusting your filters to see more results.</p>
                <x-ui.button wire:click="$set('selectedType', ''); $set('selectedCapacity', 0)" variant="outline">
                    Clear Filters
                </x-ui.button>
            </div>
        @endif
    </x-sections.content>

    <!-- Booking Process Section -->
    <x-sections.content :muted="true" title="How Booking Works" subtitle="Simple steps to reserve your space">
        <div class="grid gap-8 md:grid-cols-4">
            <div class="text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-primary text-primary-foreground flex items-center justify-center mx-auto text-2xl font-bold">
                    1
                </div>
                <h3 class="font-semibold">Choose Your Space</h3>
                <p class="text-sm text-muted-foreground">Browse our available rooms and select the one that fits your needs.</p>
            </div>

            <div class="text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-primary text-primary-foreground flex items-center justify-center mx-auto text-2xl font-bold">
                    2
                </div>
                <h3 class="font-semibold">Pick Date & Time</h3>
                <p class="text-sm text-muted-foreground">Select your preferred date and time slot from the available options.</p>
            </div>

            <div class="text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-primary text-primary-foreground flex items-center justify-center mx-auto text-2xl font-bold">
                    3
                </div>
                <h3 class="font-semibold">Confirm & Pay</h3>
                <p class="text-sm text-muted-foreground">Review your booking details and complete the payment securely.</p>
            </div>

            <div class="text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-primary text-primary-foreground flex items-center justify-center mx-auto text-2xl font-bold">
                    4
                </div>
                <h3 class="font-semibold">Show Up & Work</h3>
                <p class="text-sm text-muted-foreground">Arrive at your booked time and enjoy your productive workspace.</p>
            </div>
        </div>
    </x-sections.content>

    <x-sections.cta
        title="Need a Custom Solution?"
        subtitle="We can accommodate special requests for events, team offsites, or long-term arrangements."
        primaryAction="Contact Us"
        :primaryUrl="route('contact')"
        secondaryAction="View Pricing"
        :secondaryUrl="route('pricing')"
    />
</main>
