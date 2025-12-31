<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Modules\Membership\Models\Subscription;
use Modules\Membership\Models\Plan;

new
#[Layout('components.layouts.app', ['title' => 'My Subscription'])]
#[Title('My Subscription')]
class extends Component {
    public ?Subscription $subscription = null;
    public array $plans = [];

    public function mount(): void
    {
        $this->subscription = Subscription::where('member_id', auth()->id())
            ->with(['plan'])
            ->latest()
            ->first();

        $this->plans = Plan::where('is_active', true)
            ->orderBy('price')
            ->get()
            ->toArray();
    }

    public function cancelSubscription(): void
    {
        if (!$this->subscription || $this->subscription->status !== 'active') {
            session()->flash('error', 'No active subscription to cancel.');
            return;
        }

        $this->subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        session()->flash('success', 'Your subscription has been cancelled. You will retain access until ' . $this->subscription->ends_at->format('F j, Y') . '.');
        
        $this->subscription = $this->subscription->fresh();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <!-- Page Header -->
    <div class="relative mb-2 w-full">
        <flux:heading size="xl" level="1">{{ __('My Subscription') }}</flux:heading>
        <flux:subheading size="lg">{{ __('Manage your membership plan and billing') }}</flux:subheading>
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

    <div class="grid gap-4 lg:grid-cols-3">
        <!-- Current Subscription -->
        <div class="lg:col-span-2">
            @if($subscription)
                @php
                    $isActive = $subscription->status === 'active';
                    $isCancelled = $subscription->status === 'cancelled';
                    $isExpired = $subscription->ends_at && $subscription->ends_at->isPast();
                @endphp
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <!-- Status Header -->
                    <div class="border-b border-neutral-200 p-6 dark:border-neutral-700 {{ $isActive ? 'bg-green-50 dark:bg-green-950/20' : ($isCancelled ? 'bg-yellow-50 dark:bg-yellow-950/20' : 'bg-neutral-50 dark:bg-neutral-800/50') }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full {{ $isActive ? 'bg-green-100 dark:bg-green-900/30' : 'bg-neutral-200 dark:bg-neutral-700' }}">
                                    @if($isActive)
                                        <flux:icon.check class="size-5 text-green-600 dark:text-green-400" />
                                    @elseif($isCancelled)
                                        <flux:icon.exclamation-triangle class="size-5 text-yellow-600 dark:text-yellow-400" />
                                    @else
                                        <flux:icon.x-mark class="size-5 text-neutral-500" />
                                    @endif
                                </div>
                                <div>
                                    <p class="font-semibold text-neutral-900 dark:text-white">
                                        @if($isActive)
                                            {{ __('Active Subscription') }}
                                        @elseif($isCancelled)
                                            {{ __('Cancelled (Access until :date)', ['date' => $subscription->ends_at?->format('M j, Y')]) }}
                                        @else
                                            {{ ucfirst($subscription->status) }}
                                        @endif
                                    </p>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $subscription->plan?->name ?? 'Unknown Plan' }}</p>
                                </div>
                            </div>
                            @if($isActive)
                                <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                            @elseif($isCancelled)
                                <flux:badge color="yellow" size="sm">{{ __('Cancelled') }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <!-- Subscription Details -->
                    <div class="p-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Plan') }}</p>
                                    <p class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $subscription->plan?->name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Billing Cycle') }}</p>
                                    <p class="font-medium text-neutral-900 dark:text-white">{{ ucfirst($subscription->plan?->type?->value ?? 'monthly') }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Price') }}</p>
                                    <p class="text-lg font-semibold text-neutral-900 dark:text-white">
                                        {{ $subscription->plan?->currency ?? 'EGP' }} {{ number_format($subscription->plan?->price ?? 0, 0) }}
                                        <span class="text-sm font-normal text-neutral-500">/{{ $subscription->plan?->type?->value ?? 'month' }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Started On') }}</p>
                                    <p class="font-medium text-neutral-900 dark:text-white">{{ $subscription->starts_at?->format('F j, Y') ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ $isCancelled ? __('Access Until') : __('Next Billing Date') }}</p>
                                    <p class="font-medium text-neutral-900 dark:text-white">{{ $subscription->ends_at?->format('F j, Y') ?? 'N/A' }}</p>
                                </div>
                                @if($isActive && $subscription->ends_at)
                                    <div>
                                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Days Remaining') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ now()->diffInDays($subscription->ends_at) }} {{ __('days') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Plan Features -->
                        @if($subscription->plan)
                            <flux:separator class="my-6" />
                            <flux:heading size="sm" class="mb-4">{{ __('Plan Features') }}</flux:heading>
                            <div class="grid gap-3 md:grid-cols-2">
                                @if($subscription->plan->wifi_access)
                                    <div class="flex items-center gap-2 text-sm">
                                        <flux:icon.check class="size-5 text-green-500" />
                                        <span class="text-neutral-700 dark:text-neutral-300">{{ __('WiFi Access') }}</span>
                                    </div>
                                @endif
                                @if($subscription->plan->meeting_room_access)
                                    <div class="flex items-center gap-2 text-sm">
                                        <flux:icon.check class="size-5 text-green-500" />
                                        <span class="text-neutral-700 dark:text-neutral-300">{{ __('Meeting Room Access') }}</span>
                                    </div>
                                @endif
                                @if($subscription->plan->printing_pages)
                                    <div class="flex items-center gap-2 text-sm">
                                        <flux:icon.check class="size-5 text-green-500" />
                                        <span class="text-neutral-700 dark:text-neutral-300">{{ $subscription->plan->printing_pages }} {{ __('Printing Pages') }}</span>
                                    </div>
                                @endif
                                @if($subscription->plan->locker_access)
                                    <div class="flex items-center gap-2 text-sm">
                                        <flux:icon.check class="size-5 text-green-500" />
                                        <span class="text-neutral-700 dark:text-neutral-300">{{ __('Locker Access') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Actions -->
                        @if($isActive)
                            <flux:separator class="my-6" />
                            <div class="flex flex-wrap gap-3">
                                <flux:button href="{{ route('pricing') }}" variant="outline">{{ __('Change Plan') }}</flux:button>
                                <flux:button 
                                    wire:click="cancelSubscription"
                                    wire:confirm="{{ __('Are you sure you want to cancel your subscription? You will retain access until the end of your current billing period.') }}"
                                    variant="ghost"
                                    class="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950/20"
                                >
                                    {{ __('Cancel Subscription') }}
                                </flux:button>
                            </div>
                        @elseif($isCancelled || $isExpired)
                            <flux:separator class="my-6" />
                            <flux:button href="{{ route('pricing') }}">{{ __('Reactivate Subscription') }}</flux:button>
                        @endif
                    </div>
                </div>
            @else
                <!-- No Subscription -->
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="p-8 text-center">
                        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                            <flux:icon.ticket class="size-8 text-neutral-400" />
                        </div>
                        <flux:heading size="lg" class="mb-2">{{ __('No Active Subscription') }}</flux:heading>
                        <flux:subheading class="mb-6">{{ __('Start your journey with Siliconile by choosing a membership plan.') }}</flux:subheading>
                        <flux:button href="{{ route('pricing') }}" variant="primary">{{ __('View Membership Plans') }}</flux:button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-4 lg:col-span-1">
            <!-- Quick Actions -->
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                    <flux:heading size="sm">{{ __('Quick Actions') }}</flux:heading>
                </div>
                <div class="p-4">
                    <div class="space-y-2">
                        <a href="{{ route('member.orders') }}" class="flex items-center gap-3 rounded-lg p-3 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                <flux:icon.document-text class="size-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-neutral-900 dark:text-white">{{ __('View Invoices') }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Billing history') }}</p>
                            </div>
                            <flux:icon.chevron-right class="size-5 text-neutral-400" />
                        </a>
                        <a href="{{ route('member.bookings') }}" class="flex items-center gap-3 rounded-lg p-3 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                                <flux:icon.calendar class="size-5 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-neutral-900 dark:text-white">{{ __('My Bookings') }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Space reservations') }}</p>
                            </div>
                            <flux:icon.chevron-right class="size-5 text-neutral-400" />
                        </a>
                        <a href="{{ route('contact') }}" class="flex items-center gap-3 rounded-lg p-3 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                                <flux:icon.question-mark-circle class="size-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-neutral-900 dark:text-white">{{ __('Get Support') }}</p>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ __('Contact us') }}</p>
                            </div>
                            <flux:icon.chevron-right class="size-5 text-neutral-400" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="rounded-xl border border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/20">
                <div class="p-6">
                    <flux:heading size="sm" class="mb-2">{{ __('Need Help?') }}</flux:heading>
                    <flux:subheading class="mb-4">{{ __('Our team is here to help you with any questions about your membership.') }}</flux:subheading>
                    <flux:button href="{{ route('contact') }}" variant="outline" class="w-full">{{ __('Contact Support') }}</flux:button>
                </div>
            </div>
        </div>
    </div>
</div>