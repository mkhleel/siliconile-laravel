<x-layouts.app>
    <div class="flex flex-col gap-8 items-center w-full min-h-screen bg-white dark:bg-gray-900 p-4 sm:p-6" dir="rtl">
        <!-- Payment Failed Container -->
        <div class="flex flex-col gap-6 items-center w-full max-w-sm">
            <!-- Title Section -->
            <div class="flex gap-2 items-center justify-center w-full">
                <x-heroicon-o-x-circle class="w-6 h-6 text-red-600 dark:text-red-500" />
                <h1 class="font-semibold text-xl text-red-600 dark:text-red-500">
                    فشلت عملية الدفع
                </h1>
            </div>

            <!-- Failed Message Card -->
            <div class="flex flex-col gap-2 items-end w-full">
                <div class="bg-white dark:bg-gray-800 border border-red-100 dark:border-red-900/30 rounded-lg w-full">
                    <div class="flex flex-col gap-3 p-6">
                        <!-- Error Message -->
                        <div class="flex flex-col gap-2 items-center w-full text-center">
                            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3 w-full">
                                <p class="font-semibold text-sm text-red-700 dark:text-red-300">
                                    {{ session('error') ?? 'عذراً، لم تتم عملية الدفع بنجاح' }}
                                </p>
                            </div>
                            <p class="font-medium text-sm text-gray-600 dark:text-gray-400">
                                الرجاء المحاولة مرة أخرى أو استخدام طريقة دفع أخرى
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

                <!-- Support Card -->
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg w-full">
                    <div class="flex flex-col gap-3 p-6">
                        <div class="flex flex-col gap-2 items-center text-center">
                            <x-heroicon-o-chat-bubble-left-right class="w-10 h-10 text-gray-600 dark:text-gray-400" />
                            <p class="font-semibold text-base text-gray-900 dark:text-white">
                                هل تحتاج إلى مساعدة؟
                            </p>
                            <p class="font-medium text-sm text-gray-600 dark:text-gray-400">
                                فريق الدعم متاح لمساعدتك في حل أي مشكلة
                            </p>
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

            <!-- Contact Support Link -->
            <a 
                href="https://arabicforall.net/ar/contact"
                class="rounded-full flex gap-2 items-center justify-center px-4 py-2 font-medium text-gray-700 dark:text-gray-300 transition-colors hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600"
            >
                <span>تواصل مع الدعم</span>
            </a>
        </div>
    </div>
</x-layouts.app>