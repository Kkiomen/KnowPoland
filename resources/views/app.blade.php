@php
    /**
     * Search engines and link previews are the main way people arrive here, and
     * this application renders without SSR, so anything a scraper needs has to
     * be written server side. What each page says about itself lives in
     * App\Support\PageSeo, and its copy in lang/<locale>/site.php, so the head
     * and the page can never drift apart.
     */
    $seo = \App\Support\PageSeo::head($page['component'], request());
    $structuredData = \App\Support\StructuredData::json($seo);
    $default = \App\Support\PageSeo::defaultLocale();
    $locale = $seo['locale'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="{{ $seo['robots'] }}">
        <meta name="theme-color" content="#07080a">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        {{-- The description carries the key Inertia matches on, so the client
             replaces this tag on navigation instead of adding a second one. --}}
        <meta data-inertia="description" name="description" content="{{ $seo['description'] }}">
        <link rel="canonical" href="{{ $seo['canonical'] }}">
        @foreach ($seo['alternates'] as $language => $address)
            <link rel="alternate" hreflang="{{ $language }}" href="{{ $address }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $seo['defaultAddress'] }}">

        <meta property="og:type" content="{{ $seo['ogType'] }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="{{ $locale }}">
        @foreach ($seo['alternates'] as $language => $address)
            @if ($language !== $locale)
                <meta property="og:locale:alternate" content="{{ $language }}">
            @endif
        @endforeach
        <meta property="og:url" content="{{ $seo['canonical'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta property="og:image:width" content="{{ $seo['imageWidth'] }}">
        <meta property="og:image:height" content="{{ $seo['imageHeight'] }}">
        <meta property="og:image:alt" content="{{ $seo['title'] }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
        <meta name="twitter:image:alt" content="{{ $seo['title'] }}">

        <script type="application/ld+json">{!! $structuredData !!}</script>

        {{-- A reader counter, only if one has been configured. Nothing here
             sets a cookie, so the site still needs no consent banner. --}}
        @if (config('site.analytics.script') && config('site.analytics.domain'))
            <script
                defer
                data-domain="{{ config('site.analytics.domain') }}"
                src="{{ config('site.analytics.script') }}"
            ></script>
        @endif

        {{-- Google Analytics sets cookies, so it starts with every kind of
             storage denied. The cookie banner in SiteFooter stores the reader's
             answer under this key and grants analytics storage only on a yes. --}}
        @if (config('site.google_analytics'))
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('consent', 'default', {
                    ad_storage: 'denied',
                    ad_user_data: 'denied',
                    ad_personalization: 'denied',
                    analytics_storage: 'denied',
                });
                try {
                    if (localStorage.getItem('analytics-consent') === 'granted') {
                        gtag('consent', 'update', { analytics_storage: 'granted' });
                    }
                } catch (error) {}
                gtag('js', new Date());
                gtag('config', @json(config('site.google_analytics')));
            </script>
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode(config('site.google_analytics')) }}"></script>
        @endif

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        {{-- The title sits here rather than inside the Inertia head component,
             which renders nothing without SSR, so a crawler used to find a page
             with no title at all. Inertia replaces it client side. --}}
        <title>{{ $seo['title'] }} - {{ config('app.name') }}</title>
        <x-inertia::head />
    </head>
    <body class="bg-ink font-sans text-bone antialiased">
        <x-inertia::app />
    </body>
</html>
