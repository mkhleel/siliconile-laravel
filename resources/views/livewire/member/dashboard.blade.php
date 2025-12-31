<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Modules\Membership\Models\Subscription;
use Modules\SpaceBooking\Models\Booking;
use Modules\Billing\Models\Order;

new class extends Component
{
    public function with(): array
    {
        $user = Auth::user();

        // Get active subscription
        $activeSubscription = Subscription::query()
            ->where('member_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        // Get upcoming bookings
        $upcomingBookings = Booking::query()
            ->where('bookable_id', $user->id)
            ->where('bookable_type', get_class($user))
            ->where('start_time', '>=', now())
            ->whereIn('status', ['confirmed', 'pending'])
            ->with('spaceResource')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        // Get recent orders
        $recentOrders = Order::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Quick stats
        $stats = [
            'total_bookings' => Booking::where('bookable_id', $user->id)
                ->where('bookable_type', get_class($user))
                ->count(),
            'this_month_bookings' => Booking::where('bookable_id', $user->id)
                ->where('bookable_type', get_class($user))
                ->whereMonth('start_time', now()->month)
                ->count(),
            'membership_days_left' => $activeSubscription?->end_date?->diffInDays(now()) ?? 0,
        ];

        return [
            'user' => $user,
            'activeSubscription' => $activeSubscription,
            'upcomingBookings' => $upcomingBookings,
            'recentOrders' => $recentOrders,
            'stats' => $stats,
        ];
    }
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <!-- Page Header -->
    <div class="relative mb-2 w-full">
        <flux:heading size="xl" level="1">{{ __('Welcome back, :name!', ['name' => $user->name]) }}</flux:heading>
        <flux:subheading size="lg" class="mb-4">{{ __("Here's an overview of your Siliconile membership") }}</flux:subheading>
    </div>

        <!-- Quick Stats -->
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon.calendar class="size-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __("This Month's Bookings") }}</p>
                        <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['this_month_bookings'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                        <flux:icon.clipboard-document-list class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Total Bookings') }}</p>
                        <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['total_bookings'] }}</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                        <flux:icon.clock class="size-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Membership Days Left') }}</p>
                        <p class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $stats['membership_days_left'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid gap-4 lg:grid-cols-3">
            <!-- Active Membership Card -->
            <div class="lg:col-span-1">
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="border-b border-neutral-200 p-4 dark:border-neutral-700">
                        <flux:heading size="sm">{{ __('Your Membership') }}</flux:heading>
                    </div>
                    <div class="p-4">
                        @if($activeSubscription)
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $activeSubscription->plan->name }}</span>
                                    <flux:badge color="green" size="sm">{{ __('Active') }}</flux:badge>
                                </div>

                                <flux:separator variant="subtle" />

                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500 dark:text-neutral-400">{{ __('Plan Type') }}</span>
                                        <span class="font-medium text-neutral-900 dark:text-white">{{ $activeSubscription->plan->type->getLabel() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500 dark:text-neutral-400">{{ __('Start Date') }}</span>
                                        <span class="font-medium text-neutral-900 dark:text-white">{{ $activeSubscription->start_date->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500 dark:text-neutral-400">{{ __('End Date') }}</span>
                                        <span class="font-medium text-neutral-900 dark:text-white">{{ $activeSubscription->end_date->format('M d, Y') }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-neutral-500 dark:text-neutral-400">{{ __('Auto Renew') }}</span>
                                        <span class="font-medium text-neutral-900 dark:text-white">{{ $activeSubscription->auto_renew ? __('Yes') : __('No') }}</span>
                                    </div>
                                </div>

                                <flux:separator variant="subtle" />

                                <flux:button href="{{ route('pricing') }}" variant="ghost" class="w-full">
                                    {{ __('Upgrade Plan') }}
                                </flux:button>
                            </div>
                        @else
                            <div class="py-6 text-center">
                                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                    <flux:icon.credit-card class="size-8 text-neutral-400" />
                                </div>
                                <flux:heading size="sm" class="mb-2">{{ __('No Active Membership') }}</flux:heading>
                                <flux:subheading class="mb-4">{{ __('Subscribe to a plan to unlock all features.') }}</flux:subheading>
                                <flux:button href="{{ route('pricing') }}" variant="primary" class="w-full">
                                    {{ __('View Plans') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Upcoming Bookings -->
            <div class="lg:col-span-2">
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="flex items-center justify-between border-b border-neutral-200 p-4 dark:border-neutral-700">
                        <flux:heading size="sm">{{ __('Upcoming Bookings') }}</flux:heading>
                        <flux:button href="{{ route('member.bookings') }}" variant="ghost" size="sm">
                            {{ __('View All') }}
                        </flux:button>
                    </div>
                    <div class="p-4">
                        @if($upcomingBookings->count() > 0)
                            <div class="space-y-3">
                                @foreach($upcomingBookings as $booking)
                                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                                        <div class="flex items-center gap-4">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                                <flux:icon.building-office class="size-5 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div>
                                                <p class="font-medium text-neutral-900 dark:text-white">{{ $booking->spaceResource->name }}</p>
                                                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $booking->start_time->format('M d, Y') }} •
                                                    {{ $booking->start_time->format('g:i A') }} - {{ $booking->end_time->format('g:i A') }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <flux:badge :color="$booking->status->value === 'confirmed' ? 'green' : 'yellow'" size="sm">
                                                {{ ucfirst($booking->status->value) }}
                                            </flux:badge>
                                            <flux:button href="{{ route('member.bookings.show', $booking) }}" variant="ghost" size="sm">
                                                {{ __('Details') }}
                                            </flux:button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-8 text-center">
                                <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                                    <flux:icon.calendar class="size-6 text-neutral-400" />
                                </div>
                                <flux:heading size="sm" class="mb-2">{{ __('No Upcoming Bookings') }}</flux:heading>
                                <flux:subheading class="mb-4">{{ __('Book a space for your next meeting or work session.') }}</flux:subheading>
                                <flux:button href="{{ route('spaces') }}" variant="primary">
                                    {{ __('Book a Space') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-neutral-200 p-4 dark:border-neutral-700">
                <flux:heading size="sm">{{ __('Recent Transactions') }}</flux:heading>
                <flux:button href="{{ route('member.orders') }}" variant="ghost" size="sm">
                    {{ __('View All') }}
                </flux:button>
            </div>
            <div class="p-4">
                @if($recentOrders->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                                    <th class="pb-3 text-left text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Order #') }}</th>
                                    <th class="pb-3 text-left text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Date') }}</th>
                                    <th class="pb-3 text-left text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Amount') }}</th>
                                    <th class="pb-3 text-left text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</th>
                                    <th class="pb-3 text-right text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                                @foreach($recentOrders as $order)
                                    <tr>
                                        <td class="py-3 font-medium text-neutral-900 dark:text-white">{{ $order->order_number }}</td>
                                        <td class="py-3 text-neutral-500 dark:text-neutral-400">{{ $order->created_at->format('M d, Y') }}</td>
                                        <td class="py-3 text-neutral-900 dark:text-white">{{ number_format($order->total, 2) }} {{ $order->currency }}</td>
                                        <td class="py-3">
                                            <flux:badge :color="match($order->status->value) {
                                                'completed', 'paid' => 'green',
                                                'pending' => 'yellow',
                                                'cancelled', 'failed' => 'red',
                                                default => 'zinc'
                                            }" size="sm">
                                                {{ ucfirst($order->status->value) }}
                                            </flux:badge>
                                        </td>
                                        <td class="py-3 text-right">
                                            <flux:button href="{{ route('member.orders.show', $order) }}" variant="ghost" size="sm">
                                                {{ __('View') }}
                                            </flux:button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-8 text-center">
                        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                            <flux:icon.clipboard-document-list class="size-6 text-neutral-400" />
                        </div>
                        <flux:heading size="sm" class="mb-2">{{ __('No Transactions Yet') }}</flux:heading>
                        <flux:subheading>{{ __('Your transaction history will appear here.') }}</flux:subheading>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid gap-4 md:grid-cols-4">
            <a href="{{ route('spaces') }}" class="group">
                <div class="rounded-xl border border-neutral-200 bg-white p-6 text-center transition-all hover:border-blue-300 hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900 dark:hover:border-blue-700">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 transition-colors group-hover:bg-blue-200 dark:bg-blue-900/30 dark:group-hover:bg-blue-900/50">
                        <flux:icon.calendar class="size-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <p class="font-semibold text-neutral-900 dark:text-white">{{ __('Book a Space') }}</p>
                </div>
            </a>

            <a href="{{ route('events') }}" class="group">
                <div class="rounded-xl border border-neutral-200 bg-white p-6 text-center transition-all hover:border-green-300 hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900 dark:hover:border-green-700">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 transition-colors group-hover:bg-green-200 dark:bg-green-900/30 dark:group-hover:bg-green-900/50">
                        <flux:icon.users class="size-6 text-green-600 dark:text-green-400" />
                    </div>
                    <p class="font-semibold text-neutral-900 dark:text-white">{{ __('View Events') }}</p>
                </div>
            </a>

            <a href="{{ route('profile.edit') }}" class="group">
                <div class="rounded-xl border border-neutral-200 bg-white p-6 text-center transition-all hover:border-purple-300 hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900 dark:hover:border-purple-700">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 transition-colors group-hover:bg-purple-200 dark:bg-purple-900/30 dark:group-hover:bg-purple-900/50">
                        <flux:icon.user class="size-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <p class="font-semibold text-neutral-900 dark:text-white">{{ __('Edit Profile') }}</p>
                </div>
            </a>

            <a href="{{ route('contact') }}" class="group">
                <div class="rounded-xl border border-neutral-200 bg-white p-6 text-center transition-all hover:border-amber-300 hover:shadow-md dark:border-neutral-700 dark:bg-zinc-900 dark:hover:border-amber-700">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 transition-colors group-hover:bg-amber-200 dark:bg-amber-900/30 dark:group-hover:bg-amber-900/50">
                        <flux:icon.question-mark-circle class="size-6 text-amber-600 dark:text-amber-400" />
                    </div>
                    <p class="font-semibold text-neutral-900 dark:text-white">{{ __('Get Support') }}</p>
                </div>
            </a>
        </div>
    </div>
</div>