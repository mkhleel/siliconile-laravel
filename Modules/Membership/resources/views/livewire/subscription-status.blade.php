<?php

use Modules\Membership\app\Models\Member;
use Modules\Membership\app\Services\SubscriptionService;
use Livewire\Volt\Component;

new class extends Component {
    public ?Member $member = null;
    public array $subscriptionSummary = [];

    public function mount(): void
    {
        // Get the member for the authenticated user
        $this->member = Member::where('user_id', auth()->id())->first();
        
        if ($this->member) {
            $subscriptionService = app(SubscriptionService::class);
            $this->subscriptionSummary = $subscriptionService->getSubscriptionSummary($this->member);
        }
    }

    public function with(): array
    {
        return [
            'hasActiveSub' => $this->subscriptionSummary['has_active_subscription'] ?? false,
            'subscription' => $this->subscriptionSummary['current_subscription'] ?? null,
            'daysRemaining' => $this->subscriptionSummary['days_remaining'] ?? 0,
            'isExpiring' => $this->subscriptionSummary['is_expiring_soon'] ?? false,
            'isGracePeriod' => $this->subscriptionSummary['is_in_grace_period'] ?? false,
            'graceDaysRemaining' => $this->subscriptionSummary['grace_period_days_remaining'] ?? 0,
            'autoRenew' => $this->subscriptionSummary['auto_renew_enabled'] ?? false,
        ];
    }
};
?>

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ __('My Subscription') }}
        </h3>
        
        @if($hasActiveSub)
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                {{ $isGracePeriod ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 
                   ($isExpiring ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                   'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200') }}">
                {{ $subscription?->status->getLabel() }}
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                {{ __('No Active Plan') }}
            </span>
        @endif
    </div>

    @if($hasActiveSub && $subscription)
        <div class="space-y-4">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Current Plan') }}</p>
                <p class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ $subscription->plan->name }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Start Date') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $subscription->start_date->format('M d, Y') }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('End Date') }}</p>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $subscription->end_date->format('M d, Y') }}
                    </p>
                </div>
            </div>

            @if($isGracePeriod)
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-orange-500 mr-2 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-orange-800 dark:text-orange-200">
                                {{ __('Grace Period Active') }}
                            </p>
                            <p class="text-sm text-orange-700 dark:text-orange-300 mt-1">
                                {{ __('You have') }} {{ $graceDaysRemaining }} {{ __('day(s) remaining to renew your subscription.') }}
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($isExpiring)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <x-heroicon-s-exclamation-circle class="w-5 h-5 text-yellow-500 mr-2 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                {{ __('Expiring Soon') }}
                            </p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                {{ __('Your subscription expires in') }} {{ $daysRemaining }} {{ __('day(s).') }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                {{ __('Days Remaining') }}
                            </p>
                            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                                {{ $daysRemaining }}
                            </p>
                        </div>
                        <x-heroicon-s-calendar-days class="w-12 h-12 text-blue-300 dark:text-blue-700" />
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Auto Renewal') }}</span>
                <span class="text-sm font-medium {{ $autoRenew ? 'text-green-600 dark:text-green-400' : 'text-gray-500' }}">
                    {{ $autoRenew ? 'Enabled' : 'Disabled' }}
                </span>
            </div>

            <div class="pt-4">
                <a href="{{ route('dashboard.subscription.manage') }}" 
                   class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                    {{ __('Manage Subscription') }}
                </a>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <x-heroicon-o-calendar-days class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                {{ __('You don\'t have an active subscription yet.') }}
            </p>
            <a href="{{ route('pricing') }}" 
               class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-200">
                {{ __('Browse Plans') }}
            </a>
        </div>
    @endif
</div>
