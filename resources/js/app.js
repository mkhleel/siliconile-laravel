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
