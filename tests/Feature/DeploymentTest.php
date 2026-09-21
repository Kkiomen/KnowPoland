<?php

declare(strict_types=1);

use App\Support\PageSeo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;

/**
 * What has to hold before the site is put on a server.
 *
 * These are not features a reader would name. They are the things that decide
 * whether the site works at all once it is live: the page a dead link lands
 * on, what a crawler is allowed to index, the settings a deployment copies,
 * and the weight of a page on a phone.
 */
it('answers a dead address with the site page, in both languages', function (): void {
    $titles = [
        'en' => 'There is nothing at this address',
        'pl' => 'Pod tym adresem nic nie ma',
    ];

    foreach ($titles as $locale => $expected) {
        $address = '/no-such-page'.($locale === 'en' ? '' : '?lang='.$locale);

        $this->get($address)
            ->assertStatus(404)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component(PageSeo::ERROR_COMPONENT)
                ->where('status', 404)
                // the copy travels with the page, or it renders its own keys
                ->has('translations.notfound.doors', 4)
                ->where('translations.notfound.lost.title', $expected)
            );
    }
});

it('keeps the error page out of the index while the rest of the site stays in it', function (): void {
    $html = (string) $this->get('/no-such-page')->getContent();

    expect($html)->toContain('<meta name="robots" content="noindex, follow">');

    expect((string) $this->get('/')->getContent())
        ->toContain('<meta name="robots" content="index, follow');
});

it('never lists the error page as a page of the site', function (): void {
    expect(PageSeo::pages())->not->toHaveKey(PageSeo::ERROR_COMPONENT);

    expect((string) $this->get('/sitemap.xml')->getContent())
        ->not->toContain('no-such-page');
});

it('keeps the design mockups out of search results', function (): void {
    $robots = (string) file_get_contents(public_path('robots.txt'));

    expect($robots)->toContain('Disallow: /mockups/')
        ->and($robots)->toContain('Sitemap: https://knowpoland.com/sitemap.xml');
});

it('ships an example environment a production server can copy', function (): void {
    $example = (string) file_get_contents(base_path('.env.example'));

    // a debug page on the live site prints file paths and configuration
    expect($example)->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('APP_URL=https://knowpoland.com');
});

it('cannot be pointed at a laptop by a stray hot file', function (): void {
    // public/hot is written by the dev server; uploaded by accident it would
    // send every visitor to localhost for the stylesheet and the scripts
    expect((string) file_get_contents(app_path('Providers/AppServiceProvider.php')))
        ->toContain("Vite::useHotFile(storage_path('framework/vite.hot'))");
});

it('fetches a full size picture only when the reader asks for it', function (): void {
    $viewer = (string) file_get_contents(resource_path('js/components/ExhibitImage.vue'));

    // a closed dialog still downloads what its img carries, so the address of
    // the large file is written only once the viewer has been opened
    expect($viewer)->toContain('const wanted = ref(false);')
        ->toContain('wanted.value = true;')
        ->toContain('<picture v-if="wanted"')
        ->toContain(':src="props.full"');
});

it('writes an AVIF beside every photograph, and a smaller one', function (): void {
    $missing = [];
    $useless = [];

    foreach (glob(public_path('images/*.jpg')) as $jpeg) {
        $avif = substr($jpeg, 0, -4).'.avif';

        if (! is_file($avif)) {
            // a source element pointing at a file that is not there shows
            // nothing at all, it does not fall back to the img beside it
            $missing[] = basename($jpeg);

            continue;
        }

        if (filesize($avif) >= filesize($jpeg)) {
            $useless[] = basename($jpeg);
        }
    }

    expect($missing)->toBe([])
        ->and($useless)->toBe([]);
});

it('offers the AVIF first wherever it shows a photograph', function (): void {
    $loose = [];

    foreach (glob(resource_path('js/**/*.vue'), GLOB_BRACE) as $file) {
        $markup = (string) file_get_contents($file);

        foreach (explode('<img', $markup) as $index => $part) {
            if ($index === 0) {
                continue;
            }

            $before = implode('<img', array_slice(explode('<img', $markup), 0, $index));

            // every img that carries a photograph sits inside a picture, so
            // that a browser which knows AVIF never downloads the JPEG
            if (strrpos($before, '<picture') <= strrpos($before, '</picture>')) {
                $loose[] = basename($file);
            }
        }
    }

    expect(array_unique($loose))->toBe([]);
});

it('can fill the caches a deployment leaves empty', function (): void {
    Http::fake([
        '*api.nbp.pl*' => Http::response(['rates' => [['mid' => 4.25, 'effectiveDate' => '2026-09-21']]]),
        '*' => Http::response(['results' => [['values' => [['val' => 5.1, 'year' => '2026']]]]]),
    ]);

    Cache::flush();

    $this->artisan('poland:warm')->assertSuccessful();

    expect(Cache::get('poland.rates'))->not->toBeNull();
});

it('loads no counting script until one is configured', function (): void {
    expect((string) $this->get('/')->getContent())->not->toContain('data-domain');

    config(['site.analytics' => ['script' => 'https://example.test/js/script.js', 'domain' => 'knowpoland.com']]);

    expect((string) $this->get('/')->getContent())
        ->toContain('data-domain="knowpoland.com"')
        ->toContain('https://example.test/js/script.js');
});

it('says why the live figures could not be fetched', function (): void {
    Http::fake(['*' => Http::response('Forbidden', 403)]);

    Cache::flush();

    // on a production log level the lookups' own notes are dropped, so the
    // command itself has to name the reason
    $this->artisan('poland:warm')
        ->expectsOutputToContain('0 exchange rates and 0 statistics cached.')
        ->expectsOutputToContain('National Bank of Poland: HTTP 403')
        ->expectsOutputToContain('Statistics Poland: HTTP 403')
        ->assertSuccessful();
});

it('lets the browser fetch the rates when the server could not', function (): void {
    // the live host lets the server reach only a few addresses, and the bank
    // is not one of them, while its API answers any browser
    Http::fake(['*' => Http::response('', 500)]);

    Cache::flush();

    $this->get('/poland-today')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('LifeNow')
            ->where('rates', [])
            ->where('rateSource.endpoint', config('poland.rates.endpoint'))
            ->where('rateSource.codes', config('poland.rates.codes'))
        );

    expect((string) file_get_contents(resource_path('js/pages/LifeNow.vue')))
        ->toContain("props.rateSource.endpoint.replace(':code', code)");
});
