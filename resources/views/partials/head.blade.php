<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

{{-- Initialize Flux appearance on page load and navigation --}}
<script>
        (function() {
        function applyAppearance() {
            const appearance = localStorage.getItem('flux.appearance') || 'light';
            if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
        
        // Set default to light if not set
        if (!localStorage.getItem('flux.appearance')) {
            localStorage.setItem('flux.appearance', 'light');
        }
        
        // Apply immediately
        applyAppearance();
        
        // Re-apply on Livewire navigation
        document.addEventListener('livewire:navigated', applyAppearance);
    })();

</script>

@fluxAppearance
