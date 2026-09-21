<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use App\Support\PageSeo;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

/**
 * What a crawler and a link scraper actually receive.
 *
 * The application has no server-side rendering, so everything a machine reads
 * has to be in the HTML the server printed. These tests fetch real pages and
 * read the head back, in every language, rather than trusting the template.
 */

/** @return array<int, array{string, string}> Every public address, in every language. */
function publicAddresses(): array
{
    $default = PageSeo::defaultLocale();
    $addresses = [];

    foreach (PageSeo::pages() as $entry) {
        $path = parse_url(route($entry['route']), PHP_URL_PATH) ?: '/';

        foreach (SetLocale::availableLocales() as $locale) {
            $addresses[] = [
                $locale === $default ? $path : $path.'?lang='.$locale,
                $locale,
            ];
        }
    }

    return $addresses;
}

/** The content of one meta tag, or null when the page does not carry it. */
function metaContent(string $html, string $attribute, string $value): ?string
{
    $pattern = sprintf('/<meta[^>]*%s="%s"[^>]*content="([^"]*)"/i', $attribute, preg_quote($value, '/'));

    return preg_match($pattern, $html, $found) === 1 ? html_entity_decode($found[1]) : null;
}

it('gives every page a title, a description and a canonical of its own', function (): void {
    foreach (publicAddresses() as [$address, $locale]) {
        $html = $this->get($address)->assertOk()->getContent();

        expect($html)->toBeString();

        preg_match('/<title>(.*?)<\/title>/s', (string) $html, $title);
        preg_match('/<link rel="canonical" href="([^"]+)"/', (string) $html, $canonical);

        expect($title[1] ?? '')->toContain((string) config('app.name'));
        expect($canonical[1] ?? '')->toBe(url($address), "{$address}: wrong canonical");
        expect(metaContent((string) $html, 'name', 'description'))
            ->not->toBeNull("{$address}: no description")
            ->not->toBe('', "{$address}: empty description");
    }
});

it('writes the head in the language the page was asked for', function (): void {
    $english = $this->get('/history/katyn')->getContent();
    $polish = $this->get('/history/katyn?lang=pl')->getContent();

    expect(metaContent((string) $english, 'property', 'og:locale'))->toBe('en');
    expect(metaContent((string) $polish, 'property', 'og:locale'))->toBe('pl');
    expect(metaContent((string) $english, 'name', 'description'))
        ->not->toBe(metaContent((string) $polish, 'name', 'description'));
});

it('points every language at itself and at the others', function (): void {
    $html = (string) $this->get('/places/krakow?lang=pl')->getContent();

    expect($html)
        ->toContain('<link rel="alternate" hreflang="en" href="'.url('/places/krakow').'">')
        ->toContain('<link rel="alternate" hreflang="pl" href="'.url('/places/krakow?lang=pl').'">')
        ->toContain('<link rel="alternate" hreflang="x-default" href="'.url('/places/krakow').'">')
        ->toContain('<link rel="canonical" href="'.url('/places/krakow?lang=pl').'">');
});

it('drops everything but the language from the canonical address', function (): void {
    $html = (string) $this->get('/food?utm_source=newsletter&utm_campaign=spring')->getContent();

    expect($html)->toContain('<link rel="canonical" href="'.url('/food').'">');
});

it('sends a social card that exists, at the size the networks expect', function (): void {
    foreach (PageSeo::pages() as $component => $entry) {
        $card = public_path(ltrim(PageSeo::cardPath($entry['group']), '/'));

        expect(is_file($card))->toBeTrue("{$component}: no social card");

        [$width, $height] = getimagesize($card);

        expect($width)->toBe(PageSeo::CARD_WIDTH, "{$component}: card is {$width} wide");
        expect($height)->toBe(PageSeo::CARD_HEIGHT, "{$component}: card is {$height} tall");
    }
});

it('names the card and the page in the Open Graph tags', function (): void {
    $html = (string) $this->get('/history/grunwald-1410')->getContent();

    expect(metaContent($html, 'property', 'og:type'))->toBe('article');
    expect(metaContent($html, 'property', 'og:title'))->toBe(trans('site.grunwald.meta.title'));
    expect(metaContent($html, 'property', 'og:image'))->toBe(url('/images/cards/grunwald.jpg'));
    expect(metaContent($html, 'property', 'og:image:width'))->toBe('1200');
    expect(metaContent($html, 'property', 'og:image:height'))->toBe('630');
    expect(metaContent($html, 'name', 'twitter:card'))->toBe('summary_large_image');
    expect(metaContent($html, 'property', 'og:type'))->not->toBe('website');
    expect(metaContent($this->get('/places')->getContent(), 'property', 'og:type'))->toBe('website');
});

it('asks to be indexed', function (): void {
    $html = (string) $this->get('/')->getContent();

    expect(metaContent($html, 'name', 'robots'))->toContain('index')->toContain('max-image-preview:large');
});

it('publishes structured data that parses and describes the page', function (): void {
    foreach (['/', '/history', '/history/katyn', '/places/krakow', '/food'] as $address) {
        $html = (string) $this->get($address)->getContent();

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $found);

        $graph = json_decode($found[1] ?? '', true);

        expect($graph)->toBeArray();
        expect($graph['@context'])->toBe('https://schema.org');

        $types = array_column($graph['@graph'], '@type');

        expect($types)->toContain('Organization')->toContain('WebSite')->toContain('ImageObject');

        foreach ($graph['@graph'] as $node) {
            expect($node)->toHaveKey('@type');
        }
    }
});

it('describes an article as an article and a city as a destination', function (): void {
    $article = json_decode(structuredData($this->get('/history/enigma')->getContent()), true);
    $city = json_decode(structuredData($this->get('/places/torun')->getContent()), true);

    expect(array_column($article['@graph'], '@type'))->toContain('Article')->toContain('BreadcrumbList');
    expect(array_column($city['@graph'], '@type'))->toContain('TouristDestination');

    $destination = collect($city['@graph'])->firstWhere('@type', 'TouristDestination');

    expect($destination['geo']['latitude'])->toBe(53.0138);
    expect($destination['address']['addressCountry'])->toBe('PL');
});

it('walks the breadcrumb from the home page down to a wartime article', function (): void {
    $graph = json_decode(structuredData($this->get('/history/katyn')->getContent()), true);
    $trail = collect($graph['@graph'])->firstWhere('@type', 'BreadcrumbList');
    $names = array_column($trail['itemListElement'], 'item');

    expect($names)->toBe([
        url('/'),
        url('/history'),
        url('/history/second-world-war'),
        url('/history/katyn'),
    ]);
});

it('lists every public page in the sitemap, once per language', function (): void {
    $xml = (string) $this->get('/sitemap.xml')->assertOk()->getContent();

    foreach (PageSeo::pages() as $component => $entry) {
        $path = parse_url(route($entry['route']), PHP_URL_PATH) ?: '/';

        expect($xml)->toContain('<loc>'.e(url($path)).'</loc>');
        expect($xml)->toContain('<loc>'.e(url($path.'?lang=pl')).'</loc>');
    }
});

it('registers every page that has a route', function (): void {
    $routed = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => in_array('GET', $route->methods(), true))
        ->map(fn (RoutingRoute $route): ?string => $route->getName())
        ->filter()
        ->reject(fn (string $name): bool => in_array($name, ['sitemap', 'storage.local'], true))
        ->values();

    $registered = collect(PageSeo::pages())->pluck('route');

    expect($routed->diff($registered)->all())->toBe([], 'a route with no entry in PageSeo');
});

/** The JSON-LD of a response, as a string. */
function structuredData(string|false $html): string
{
    preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', (string) $html, $found);

    return $found[1] ?? '';
}
