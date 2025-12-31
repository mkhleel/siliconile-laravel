<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Modules\Billing\Models\Order;

new
#[Layout('components.layouts.app', ['title' => 'My Orders'])]
#[Title('My Orders')]
class extends Component {
    use WithPagination;

    public string $filterStatus = '';

    public function with(): array
    {
        $orders = Order::where('user_id', auth()->id())
            ->with(['items'])
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        return [
            'orders' => $orders,
        ];
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <!-- Page Header -->
    <div class="relative mb-2 w-full">
        <flux:heading size="xl" level="1">{{ __('My Orders') }}</flux:heading>
        <flux:subheading size="lg">{{ __('View your order history and invoices') }}</flux:subheading>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="w-full md:w-48">
                <flux:select wire:model.live="filterStatus">
                    <flux:select.option value="">{{ __('All Statuses') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                    <flux:select.option value="processing">{{ __('Processing') }}</flux:select.option>
                    <flux:select.option value="completed">{{ __('Completed') }}</flux:select.option>
                    <flux:select.option value="cancelled">{{ __('Cancelled') }}</flux:select.option>
                    <flux:select.option value="refunded">{{ __('Refunded') }}</flux:select.option>
                </flux:select>
            </div>
        </div>
    </div>

    <!-- Orders List -->
    @if($orders->count() > 0)
        <div class="space-y-4">
            @foreach($orders as $order)
                <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                    <div class="p-6">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                            <!-- Order Info -->
                            <div class="min-w-0 flex-1">
                                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('Order #') }}</span>
                                        <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">{{ $order->order_number ?? $order->id }}</h3>
                                    </div>
                                    <flux:badge :color="match($order->status ?? 'pending') {
                                        'pending' => 'yellow',
                                        'processing' => 'blue',
                                        'completed' => 'green',
                                        'cancelled' => 'red',
                                        'refunded' => 'zinc',
                                        default => 'zinc'
                                    }" size="sm">
                                        {{ ucfirst($order->status ?? 'pending') }}
                                    </flux:badge>
                                </div>

                                <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Date') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ $order->created_at->format('M j, Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Items') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Payment') }}</p>
                                        <p class="font-medium text-neutral-900 dark:text-white">{{ ucfirst($order->payment_status ?? 'unpaid') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-neutral-500 dark:text-neutral-400">{{ __('Total') }}</p>
                                        <p class="font-semibold text-neutral-900 dark:text-white">{{ $order->currency ?? 'EGP' }} {{ number_format($order->total ?? 0, 2) }}</p>
                                    </div>
                                </div>

                                <!-- Order Items Preview -->
                                @if($order->items->count() > 0)
                                    <div class="mt-4 border-t border-neutral-200 pt-4 dark:border-neutral-700">
                                        <p class="mb-2 text-sm text-neutral-500 dark:text-neutral-400">{{ __('Items:') }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($order->items->take(3) as $item)
                                                <span class="rounded bg-neutral-100 px-2 py-1 text-xs dark:bg-neutral-800">
                                                    {{ $item->name ?? $item->description ?? 'Item' }}
                                                    @if($item->quantity > 1)
                                                        <span class="text-neutral-500 dark:text-neutral-400">×{{ $item->quantity }}</span>
                                                    @endif
                                                </span>
                                            @endforeach
                                            @if($order->items->count() > 3)
                                                <span class="px-2 py-1 text-xs text-neutral-500 dark:text-neutral-400">+{{ $order->items->count() - 3 }} {{ __('more') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="flex shrink-0 flex-row gap-2 lg:flex-col">
                                <flux:button href="{{ route('member.orders.show', $order) }}" variant="outline" size="sm">{{ __('View Details') }}</flux:button>
                                @if($order->status === 'pending' && ($order->payment_status ?? 'unpaid') === 'unpaid')
                                    <flux:button href="#" size="sm">{{ __('Pay Now') }}</flux:button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-2">
            {{ $orders->links() }}
        </div>
    @else
        <!-- Empty State -->
        <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
            <div class="p-12 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
                    <flux:icon.clipboard-document-list class="size-8 text-neutral-400" />
                </div>
                <flux:heading size="lg" class="mb-2">{{ __('No orders yet') }}</flux:heading>
                <flux:subheading class="mb-6">{{ __("You haven't placed any orders yet.") }}</flux:subheading>
                <flux:button href="{{ route('pricing') }}">{{ __('Browse Membership Plans') }}</flux:button>
            </div>
        </div>
    @endif
</div>
