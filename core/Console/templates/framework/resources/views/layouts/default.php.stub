<!DOCTYPE html>
<html lang="{{ $seoConfig['language'] ?? 'en' }}">

<head>
    <meta charset="UTF-8">
    {{-- Render all meta tags (title, description, OG, Twitter, etc.) --}}
    @metatags

    {{-- Critical CSS for above-the-fold content --}}
    @yield('styles')

    @dnsPrefetch('https://fonts.googleapis.com')
    @preload('/css/critical.css', 'style')
    @prefetch('/page/next')
    @hreflang('https://example.com/fr', 'fr')
    @feed('/rss.xml', 'RSS Feed')

    {{-- Stylesheets --}}
    @css('css/app.css')

    {{-- Additional head content --}}
    @yield('head')
</head>

<body>
    @yield('content')

    {{-- JavaScript --}}
    @js('js/app.js', ['defer' => true])

    {{-- Stacks for additional scripts --}}
    @yield('scripts')
</body>

</html>