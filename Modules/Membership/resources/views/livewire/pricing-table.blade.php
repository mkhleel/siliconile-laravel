<?php

use Modules\Membership\Models\Plan;
use App\Enums\PlanType;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        return [
            'plans' => Plan::query()
                ->active()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('price')
                ->get()
                ->groupBy(fn ($plan) => $plan->type->value),
            'planTypes' => PlanType::cases(),
        ];
    }
};
?>

<div class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                {{ __('Choose Your Plan') }}
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-400">
                {{ __('Select the perfect membership plan for your coworking needs') }}
            </p>
        </div>

        @foreach($planTypes as $planType)
            @if(isset($plans[$planType->value]) && $plans[$planType->value]->isNotEmpty())
                <div class="mb-12">
                    <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-6">
                        {{ $planType->getLabel() }} {{ __('Plans') }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($plans[$planType->value] as $plan)
                            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden border-2 
                                {{ $plan->is_featured ? 'border-blue-500' : 'border-gray-200 dark:border-gray-700' }}">
                                
                                @if($plan->is_featured)
                                    <div class="bg-blue-500 text-white text-center py-2 text-sm font-semibold">
                                        ⭐ MOST POPULAR
                                    </div>
                                @endif

                                <div class="p-6">
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                                        {{ $plan->name }}
                                    </h4>
                                    
                                    @if($plan->description)
                                        <p class="text-gray-600 dark:text-gray-400 mb-4 text-sm">
                                            {{ $plan->description }}
                                        </p>
                                    @endif

                                    <div class="mb-6">
                                        <span class="text-4xl font-bold text-gray-900 dark:text-white">
                                            {{ number_format($plan->price, 2) }}
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-400 text-lg">
                                            {{ $plan->currency }}
                                        </span>
                                        <span class="text-gray-500 dark:text-gray-500 text-sm">
                                            / {{ $plan->duration_days }} days
                                        </span>
                                    </div>

                                    <div class="space-y-3 mb-6">
                                        <div class="flex items-center text-sm">
                                            <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mr-2" />
                                            <span class="text-gray-700 dark:text-gray-300">
                                                {{ $plan->wifi_access ? 'WiFi Access' : 'No WiFi' }}
                                            </span>
                                        </div>

                                        @if($plan->meeting_room_access)
                                            <div class="flex items-center text-sm">
                                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mr-2" />
                                                <span class="text-gray-700 dark:text-gray-300">
                                                    {{ __('Meeting Room Access (') }}{{ $plan->meeting_hours_included }}{{ __('h included)') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if($plan->private_desk)
                                            <div class="flex items-center text-sm">
                                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mr-2" />
                                                <span class="text-gray-700 dark:text-gray-300">
                                                    {{ __('Private Desk') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if($plan->locker_access)
                                            <div class="flex items-center text-sm">
                                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mr-2" />
                                                <span class="text-gray-700 dark:text-gray-300">
                                                    {{ __('Locker Access') }}
                                                </span>
                                            </div>
                                        @endif

                                        @if($plan->guest_passes > 0)
                                            <div class="flex items-center text-sm">
                                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mr-2" />
                                                <span class="text-gray-700 dark:text-gray-300">
                                                    {{ $plan->guest_passes }} {{ __('Guest Pass') }}{{ $plan->guest_passes > 1 ? 'es' : '' }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($plan->max_members && $plan->current_members >= $plan->max_members)
                                        <button disabled class="w-full bg-gray-300 text-gray-500 font-semibold py-3 px-6 rounded-lg cursor-not-allowed">
                                            {{ __('Sold Out') }}
                                        </button>
                                    @else
                                        <a href="{{ route('register') }}?plan={{ $plan->id }}" 
                                           class="block w-full bg-blue-600 hover:bg-blue-700 text-white text-center font-semibold py-3 px-6 rounded-lg transition duration-200">
                                            Get Started
                                        </a>
                                    @endif

                                    @if($plan->max_members)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2">
                                            {{ $plan->current_members }}/{{ $plan->max_members }} {{ __('spots taken') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
