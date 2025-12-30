<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-seo-meta 
        :title="$title ?? null"
        :description="$description ?? null"
        :keywords="$keywords ?? null"
        :image="$image ?? null"
        :canonical="$canonical ?? null"
        :type="$type ?? 'website'"
        :noindex="$noindex ?? false"
    />
    
    {{-- JSON-LD Structured Data --}}
    <x-schema-org :schemas="$schemas ?? []" />

    @vite(['resources/css/theme.css', 'resources/js/theme.js'])
    
    {{ $styles ?? '' }}
</head>
<body class="antialiased min-h-screen flex flex-col">
    <x-header />

    <main class="flex-1">
        {{ $slot }}
    </main>

    <x-footer />
    
    {{ $scripts ?? '' }}

    {{-- Intercom Chat Widget --}}
    <script>
        window.intercomSettings = {
            api_base: "https://api-iam.intercom.io",
            app_id: "da00r27i",
        };
    </script>
    <script>
        // We pre-filled your app ID in the widget URL: 'https://widget.intercom.io/widget/da00r27i'
        (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',w.intercomSettings);}else{var d=document;var i=function(){i.c(arguments);};i.q=[];i.c=function(args){i.q.push(args);};w.Intercom=i;var l=function(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/da00r27i';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);};if(document.readyState==='complete'){l();}else if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();
    </script>
    
</body>
</html>
