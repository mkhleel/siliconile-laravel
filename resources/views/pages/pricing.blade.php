<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Modules\Membership\Models\Plan;

new
#[Layout('layouts.app')]
#[Title('Siliconile | Membership {{ __('Plans') }} & Pricing')]
class extends Component
{
    public function with(): array
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return [
            'plans' => $plans,
        ];
    }
};

?>

<main class="flex-1">
    <x-sections.hero
        title="{{ __('Membership <span class=') }}"text-primary'>Plans</span>"
        subtitle="{{ __('Choose the perfect membership plan for your startup journey. From daily passes to dedicated office spaces, we have options for every stage of your growth.') }}"
    />

    <!-- Pricing Section -->
    <x-sections.content title="{{ __('Choose Your Plan') }}">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($plans as $plan)
                <x-cards.pricing-card
                    :icon="$plan->private_desk ? 'desk' : ($plan->meeting_room_access ? 'meeting' : 'wifi')"
                    :title="$plan->name"
                    :description="$plan->description"
                    :price="number_format($plan->price, 0) . ' ' . $plan->currency"
                    :priceUnit="'/' . $plan->type->getLabel()"
                    :features="[
                        ['text' => 'WiFi Access', 'included' => $plan->wifi_access],
                        ['text' => 'Meeting Room Access', 'included' => $plan->meeting_room_access],
                        $plan->meeting_hours_included ? $plan->meeting_hours_included . ' meeting hours included' : ['text' => 'Meeting hours', 'included' => false],
                        ['text' => 'Private Desk', 'included' => $plan->private_desk],
                        ['text' => 'Locker Access', 'included' => $plan->locker_access],
                        $plan->guest_passes ? $plan->guest_passes . ' guest passes' : ['text' => 'Guest passes', 'included' => false],
                    ]"
                    action="Get Started"
                    :actionUrl="route('contact')"
                    :featured="$plan->is_featured"
                    :badge="$plan->type->getLabel()"
                />
            @empty
                <!-- Fallback static plans if no plans in database -->
                <x-cards.pricing-card
                    icon="wifi"
                    title="{{ __('Day Pass') }}"
                    description="{{ __('Perfect for trying out our space') }}"
                    price="150 EGP"
                    priceUnit="/day"
                    :features="[
                        'WiFi Access',
                        'Shared Workspace',
                        'Coffee & Tea',
                        ['text' => 'Meeting Room', 'included' => false],
                        ['text' => 'Private Desk', 'included' => false],
                    ]"
                    action="Get Started"
                    :actionUrl="route('contact')"
                />

                <x-cards.pricing-card
                    icon="desk"
                    title="{{ __('Weekly Pass') }}"
                    description="{{ __('Great for short-term projects') }}"
                    price="600 EGP"
                    priceUnit="/week"
                    :features="[
                        'WiFi Access',
                        'Flexible Desk',
                        'Coffee & Tea',
                        '2 meeting room hours',
                        ['text' => 'Private Desk', 'included' => false],
                    ]"
                    action="Get Started"
                    :actionUrl="route('contact')"
                />

                <x-cards.pricing-card
                    icon="meeting"
                    title="{{ __('Monthly Member') }}"
                    description="{{ __('For committed entrepreneurs') }}"
                    price="1,800 EGP"
                    priceUnit="/month"
                    :features="[
                        'WiFi Access',
                        'Dedicated Desk',
                        'Unlimited Coffee & Tea',
                        '8 meeting room hours',
                        'Locker Access',
                        '2 Guest Passes',
                    ]"
                    action="Get Started"
                    :actionUrl="route('contact')"
                    :featured="true"
                />

                <x-cards.pricing-card
                    icon="locker"
                    title="{{ __('Private Office') }}"
                    description="{{ __('For teams that need privacy') }}"
                    price="4,500 EGP"
                    priceUnit="/month"
                    :features="[
                        'WiFi Access',
                        'Private Office Space',
                        'Unlimited Coffee & Tea',
                        '16 meeting room hours',
                        'Locker Access',
                        '5 Guest Passes',
                    ]"
                    action="Contact Us"
                    :actionUrl="route('contact')"
                />
            @endforelse
        </div>
    </x-sections.content>

    <!-- Comparison Section -->
    <x-sections.content :muted="true" title="What's Included" subtitle="{{ __('All our membership plans include essential amenities to help you work effectively') }}">
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="flex flex-col items-center text-center space-y-4 p-6">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('High-Speed Internet') }}</h3>
                <p class="text-sm text-muted-foreground">{{ __('Fiber-optic connection for seamless work') }}</p>
            </div>

            <div class="flex flex-col items-center text-center space-y-4 p-6">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Community Events') }}</h3>
                <p class="text-sm text-muted-foreground">{{ __('Network with fellow entrepreneurs') }}</p>
            </div>

            <div class="flex flex-col items-center text-center space-y-4 p-6">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Printing & Scanning') }}</h3>
                <p class="text-sm text-muted-foreground">{{ __('Professional business services') }}</p>
            </div>

            <div class="flex flex-col items-center text-center space-y-4 p-6">
                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center">
                    <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold">{{ __('Mail Handling') }}</h3>
                <p class="text-sm text-muted-foreground">{{ __('Professional business address') }}</p>
            </div>
        </div>
    </x-sections.content>

    <!-- FAQ Section -->
    <x-sections.content title="{{ __('Frequently Asked Questions') }}">
        <div class="max-w-3xl mx-auto space-y-6">
            <div x-data="{ open: false }" class="border rounded-lg">
                <button @click="open = !open" class="w-full flex items-center justify-between p-6 text-left">
                    <span class="font-semibold">{{ __('Can I switch between plans?') }}</span>
                    <svg class="h-5 w-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-collapse class="px-6 pb-6 text-muted-foreground">
                    Yes! You can upgrade or downgrade your plan at any time. The change will take effect at the start of your next billing cycle.
                </div>
            </div>

            <div x-data="{ open: false }" class="border rounded-lg">
                <button @click="open = !open" class="w-full flex items-center justify-between p-6 text-left">
                    <span class="font-semibold">{{ __('What are guest passes?') }}</span>
                    <svg class="h-5 w-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-collapse class="px-6 pb-6 text-muted-foreground">
                    Guest passes allow you to bring colleagues or clients to work with you for a day. Each pass is valid for one person for one full day.
                </div>
            </div>

            <div x-data="{ open: false }" class="border rounded-lg">
                <button @click="open = !open" class="w-full flex items-center justify-between p-6 text-left">
                    <span class="font-semibold">{{ __('What are the operating hours?') }}</span>
                    <svg class="h-5 w-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-collapse class="px-6 pb-6 text-muted-foreground">
                    Our coworking space is open from 9:00 AM to 10:00 PM, Sunday through Thursday. Private office members have 24/7 access.
                </div>
            </div>

            <div x-data="{ open: false }" class="border rounded-lg">
                <button @click="open = !open" class="w-full flex items-center justify-between p-6 text-left">
                    <span class="font-semibold">{{ __('Is there parking available?') }}</span>
                    <svg class="h-5 w-5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-collapse class="px-6 pb-6 text-muted-foreground">
                    Yes, we have free parking available for all members. Monthly and private office members get dedicated parking spots.
                </div>
            </div>
        </div>
    </x-sections.content>

    <x-sections.cta
        title="{{ __('Ready to Join Our Community?') }}"
        subtitle="{{ __('Start your journey with Siliconile today and become part of Luxor') }}"s thriving tech ecosystem."
        primaryAction="Get Started"
        :primaryUrl="route('contact')"
        secondaryAction="Book a Tour"
        :secondaryUrl="route('coworking')"
    />
</main>
