<x-layouts.app>


    @php($orderModel = Modules\Billing\Models\Order::find($order))

    @if (!$orderModel)
        <div class="flex items-center justify-center w-full min-h-screen bg-white dark:bg-gray-900 p-6">
            <div class="text-center">
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    الطلب غير موجود
                </p>
            </div>
        </div>
    @else
        <div class="flex flex-col gap-8 items-center w-full min-h-screen bg-white dark:bg-gray-900 p-4 sm:p-6" dir="rtl">
            <!-- Order Summary Container -->
            <div class="flex flex-col gap-6 items-center w-full max-w-sm">
                <!-- Title Section -->
                <div class="flex gap-2 items-center justify-center w-full">
                    <x-heroicon-o-information-circle class="w-6 h-6 text-gray-900 dark:text-white" />
                    <h1 class="font-semibold text-xl text-gray-900 dark:text-white">
                        ملخص الطلب
                    </h1>
                </div>

                <!-- Order Details Cards -->
                <div class="flex flex-col gap-2 items-end w-full">
                    <!-- First Card: Order Info -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                        <div class="flex flex-col gap-3 p-6">
                            <!-- Status Row -->
                            <div class="flex items-center justify-between w-full">
                                <p class="font-semibold text-sm text-gray-900 dark:text-white text-right">
                                    حالة الطلب
                                </p>
                                <div class="bg-amber-100 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-lg px-3 py-1">
                                    <p class="font-bold text-xs text-amber-900 dark:text-amber-200">
                                        {{ $orderModel->status }}
                                    </p>
                                </div>
                            </div>

                            <!-- Created Date Row -->
                            <div class="flex items-center justify-between w-full">
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    تاريخ الإنشاء
                                </p>
                                <p class="font-semibold text-sm text-gray-900 dark:text-white">
                                    {{ $orderModel->created_at->format('d M, Y') }}
                                </p>
                            </div>

                            <!-- Completion Date Row -->
                            <div class="flex items-center justify-between w-full">
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    تاريخ الانتهاء
                                </p>
                                <p class="font-semibold text-sm text-gray-900 dark:text-white">
                                    {{ $orderModel->created_at->addDays(7)->format('d M, Y') }}
                                </p>
                            </div>

                            <!-- Reference Number Row -->
                            <div class="flex items-center justify-between w-full">
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    الرقم المرجعي
                                </p>
                                <p class="font-semibold text-sm text-gray-900 dark:text-white">
                                    {{ $orderModel->order_number }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Second Card: Product Details -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                        <div class="flex flex-col gap-3 p-6">
                            <!-- Product Names -->
                            <div class="flex items-start justify-between w-full gap-4">
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    اسم المنتج
                                </p>
                                <div class="flex flex-col gap-2 items-end">
                                    @forelse($orderModel->items as $item)
                                        <p class="font-semibold text-sm text-gray-900 dark:text-white">
                                            {{ $item->name }}
                                        </p>
                                    @empty
                                        <p class="font-medium text-sm text-gray-500 dark:text-gray-400">
                                            -
                                        </p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Total Amount Row -->
                            <div class="flex items-center justify-between w-full">
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    المبلغ المسدد
                                </p>
                                <div class="flex gap-2 items-center">
                                    <p class="font-semibold text-sm text-gray-900 dark:text-white">
                                        {!! formatCurrency($orderModel->total, 2) !!}
                                    </p>
                                    
                                </div>
                            </div>

                            <!-- Payment Method Row -->
                            <div class="flex items-center justify-between w-full">
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    طريقة الدفع
                                </p>
                                <p class="font-semibold text-sm text-gray-900 dark:text-white">
                                    {{ $orderModel->payment_method }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Buttons -->
            <div class="flex flex-col gap-3 items-center w-full max-w-sm no-print">
                <!-- Home Button -->
                <a 
                    href="/"
                    class="bg-green-600 dark:bg-green-700 rounded-full flex gap-2 items-center justify-center px-5 py-3 w-full font-semibold text-white transition-colors hover:bg-green-700 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                >
                    <x-heroicon-c-arrow-right class="w-6 h-6" />
                    <span>عودة للرئيسية</span>
                </a>

                <!-- Print Summary Button -->
                <button 
                    onclick="window.print()"
                    class="rounded-full flex gap-2 items-center justify-center px-4 py-2 font-medium text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
                >
                    <x-heroicon-o-printer class="w-5 h-5" />
                    <span>طباعة الملخص</span>
                </button>
            </div>
        </div>
    @endif
</x-layouts.app>

<x-slot:styles>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            header, footer{
                display: none !important;
            }
            
            .no-print {
                display: none !important;
            }
            
            #printable-content {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 20px;
            }
        }
    </style>
</x-slot:styles>
