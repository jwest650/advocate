<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array($userLanguage ?? 'en', ['ar', 'he']) ? 'rtl' : 'ltr' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <script src="{{ asset('js/jquery.min.js') }}"></script>
        @routes
        @if (app()->environment('local'))
            @viteReactRefresh
        @endif
        @vite(['resources/js/app.tsx'])
        <script>
            // Ensure base URL is correctly set for assets
            window.baseUrl = '{{ url('/') }}';
            window.APP_URL = '{{ config('app.url') }}';
            window.appConfig = {
                is_demo: {{ config('app.is_demo') ? 'true' : 'false' }}
            };

            // Set initial locale synchronously
            window.initialLocale = '{{ $userLanguage ?? "en" }}';
            
            // Apply RTL direction immediately based on current language
            const currentLang = '{{ $userLanguage ?? "en" }}';
            const isRtl = ['ar', 'he'].includes(currentLang);
            if (isRtl) {
                document.documentElement.dir = 'rtl';
                document.documentElement.setAttribute('dir', 'rtl');
            } else {
                document.documentElement.dir = 'ltr';
                document.documentElement.setAttribute('dir', 'ltr');
            }
            
            console.log('Applied language:', currentLang, 'Direction:', isRtl ? 'rtl' : 'ltr');
            
            // Set user data for language detection
            @if(auth()->check())
            window.authUser = {
                lang: '{{ auth()->user()->lang ?? "en" }}',
                type: '{{ auth()->user()->type }}'
            };
            @endif
        </script>
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        <script>
            console.log('Debug - userLanguage:', '{{ $userLanguage ?? "NOT_SET" }}');
            console.log('Debug - auth user:', {{ auth()->check() ? 'true' : 'false' }});
            console.log('Debug - app locale:', '{{ app()->getLocale() }}');
        </script>
        @inertia
    </body>
</html>
