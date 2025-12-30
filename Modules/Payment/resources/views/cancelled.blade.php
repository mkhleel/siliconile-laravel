<x-layouts.app>
    <div class="flex flex-col gap-8 items-center w-full min-h-screen bg-white dark:bg-gray-900 p-4 sm:p-6" dir="rtl">
        <!-- Payment Cancelled Container -->
        <div class="flex flex-col gap-6 items-center w-full max-w-sm">
            <!-- Title Section -->
            <div class="flex gap-2 items-center justify-center w-full">
                <x-heroicon-o-x-circle class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                <h1 class="font-semibold text-xl text-gray-900 dark:text-white">
                    تم إلغاء عملية الدفع
                </h1>
            </div>

            <!-- Cancelled Message Card -->
            <div class="flex flex-col gap-2 items-end w-full">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <div class="flex flex-col gap-3 p-6">
                        <!-- Cancelled Message -->
                        <div class="flex flex-col gap-2 items-center w-full text-center">
                            <div class="bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 w-full">
                                <p class="font-semibold text-sm text-gray-700 dark:text-gray-300">
                                    {{ session('info') ?? 'تم إلغاء عملية الدفع بناءً على طلبك' }}
                                </p>
                            </div>
                            <p class="font-medium text-sm text-gray-600 dark:text-gray-400">
                                لم يتم خصم أي مبلغ من حسابك
                            </p>
                        </div>

                        @if(session('payment_id'))
                        <!-- Reference Number Row -->
                        <div class="flex items-center justify-between w-full pt-3 border-t border-gray-100 dark:border-gray-700">
                            <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                الرقم المرجعي
                            </p>
                            <p class="font-semibold text-sm text-gray-900 dark:text-white">
                                {{ session('payment_id') }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Information Card -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <div class="flex flex-col gap-3 p-6">
                        <div class="flex flex-col gap-2 items-center text-center">
                            <x-heroicon-o-question-mark-circle class="w-10 h-10 text-gray-600 dark:text-gray-400" />
                            <p class="font-semibold text-base text-gray-900 dark:text-white">
                                هل تغيرت رأيك؟
                            </p>
                            <p class="font-medium text-sm text-gray-600 dark:text-gray-400">
                                يمكنك إكمال عملية الدفع في أي وقت
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Why Cancel Card -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <div class="flex flex-col gap-3 p-6">
                        <p class="font-semibold text-sm text-gray-900 dark:text-white text-center">
                            أسباب شائعة للإلغاء
                        </p>
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-start">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="2"/>
                                </svg>
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    تريد استخدام طريقة دفع مختلفة
                                </p>
                            </div>
                            <div class="flex gap-2 items-start">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="2"/>
                                </svg>
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    مراجعة تفاصيل الطلب مرة أخرى
                                </p>
                            </div>
                            <div class="flex gap-2 items-start">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="2"/>
                                </svg>
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    حدوث خطأ تقني أثناء الدفع
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Buttons -->
        <div class="flex flex-col gap-3 items-center w-full max-w-sm">
            <!-- Home Button -->
            <a 
                href="/"
                class="rounded-full flex gap-2 items-center justify-center px-5 py-3 w-full font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
            >
                <x-heroicon-o-home class="w-5 h-5" />
                <span>العودة للرئيسية</span>
            </a>

            <!-- Browse Products Link -->
            <a 
                href="/shop"
                class="rounded-full flex gap-2 items-center justify-center px-4 py-2 font-medium text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
            >
                <span>تصفح المنتجات الأخرى</span>
            </a>
        </div>
    </div>
</x-layouts.app>