<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new
#[Layout('layouts.app')]
#[Title('Application Submitted | Siliconile')]
class extends Component
{
};

?>

<main class="flex-1">
    <section class="py-20 md:py-32">
        <div class="container px-4 md:px-6">
            <div class="max-w-2xl mx-auto text-center space-y-8">
                <div class="w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto">
                    <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <div class="space-y-4">
                    <h1 class="text-3xl md:text-4xl font-bold">{{ __('Application Submitted!') }}</h1>
                    <p class="text-xl text-muted-foreground">
                        {{ __('Thank you for applying to Siliconile. We\'ve received your application and will review it shortly.') }}
                    </p>
                </div>

                <x-ui.card class="text-left">
                    <h2 class="text-lg font-semibold mb-4">{{ __('What happens next?') }}</h2>
                    <ul class="space-y-4">
                        <li class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-sm font-semibold text-primary">1</div>
                            <div>
                                <h3 class="font-medium">{{ __('Application Review') }}</h3>
                                <p class="text-sm text-muted-foreground">{{ __('Our team will review your application within 3-5 business days.') }}</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-sm font-semibold text-primary">2</div>
                            <div>
                                <h3 class="font-medium">{{ __('Interview (if selected)') }}</h3>
                                <p class="text-sm text-muted-foreground">{{ __('We may schedule a brief interview to learn more about your goals.') }}</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-4">
                            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0 text-sm font-semibold text-primary">3</div>
                            <div>
                                <h3 class="font-medium">{{ __('Welcome to Siliconile!') }}</h3>
                                <p class="text-sm text-muted-foreground">{{ __('Once approved, you\'ll receive your membership details and onboarding information.') }}</p>
                            </div>
                        </li>
                    </ul>
                </x-ui.card>

                <p class="text-sm text-muted-foreground">
                    Check your email for a confirmation message. If you have any questions, feel free to <a href="{{ route('contact') }}" class="text-primary hover:underline">{{ __('contact us') }}</a>.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <x-ui.button :href="route('home')">
                        Back to Home
                    </x-ui.button>
                    <x-ui.button :href="route('programs')" variant="outline">
                        Explore Programs
                    </x-ui.button>
                </div>
            </div>
        </div>
    </section>
</main>
