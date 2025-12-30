<x-layouts.app>
    <div class="flex flex-col gap-8 items-center w-full min-h-screen bg-white dark:bg-gray-900 p-4 sm:p-6" dir="rtl">
        <!-- Payment Error Container -->
        <div class="flex flex-col gap-6 items-center w-full max-w-sm">
            <!-- Title Section -->
            <div class="flex gap-2 items-center justify-center w-full">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-500 dark:text-amber-400" />
                <h1 class="font-semibold text-xl text-amber-600 dark:text-amber-500">
                    حدث خطأ
                </h1>
            </div>

            <!-- Error Message Card -->
            <div class="flex flex-col gap-2 items-end w-full">
                <div class="bg-white dark:bg-gray-800 border border-amber-100 dark:border-amber-900/30 rounded-lg w-full">
                    <div class="flex flex-col gap-3 p-6">
                        <!-- Error Message -->
                        <div class="flex flex-col gap-2 items-center w-full text-center">
                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-4 py-3 w-full">
                                <p class="font-semibold text-sm text-amber-700 dark:text-amber-300">
                                    {{ session('error') ?? session('warning') ?? 'حدث خطأ أثناء معالجة عملية الدفع' }}
                                </p>
                            </div>
                            <p class="font-medium text-sm text-gray-600 dark:text-gray-400">
                                نعتذر عن هذا الإزعاج، يرجى المحاولة مرة أخرى لاحقاً
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

                <!-- Troubleshooting Tips Card -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <div class="flex flex-col gap-3 p-6">
                        <p class="font-semibold text-base text-gray-900 dark:text-white text-center">
                            نصائح لحل المشكلة
                        </p>
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2 items-start">
                                <x-heroicon-o-check-circle class="w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-0.5" />
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    تأكد من اتصال الإنترنت لديك
                                </p>
                            </div>
                            <div class="flex gap-2 items-start">
                                <x-heroicon-o-check-circle class="w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-0.5" />
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    جرب استخدام متصفح آخر
                                </p>
                            </div>
                            <div class="flex gap-2 items-start">
                                <x-heroicon-o-check-circle class="w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0 mt-0.5" />
                                <p class="font-medium text-sm text-gray-600 dark:text-gray-400 text-right">
                                    امسح ذاكرة التخزين المؤقت للمتصفح
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

            <!-- Support Link -->
            <a 
                href="https://arabicforall.net/ar/contact"
                class="rounded-full flex gap-2 items-center justify-center px-4 py-2 font-medium text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
            >
                <span>تواصل مع الدعم الفني</span>
            </a>
        </div>
    </div>
</x-layouts.app>