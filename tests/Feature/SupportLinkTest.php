<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use Inertia\Testing\AssertableInertia;

/**
 * The only thing the site ever asks a reader for.
 *
 * There are no ads and nothing for sale, so the donation address is the one
 * outward link that matters. It is shared by the server rather than written
 * into a component, so the home page and the footer of every other page can
 * never point at two different places.
 */
it('shares one donation address with every page', function (): void {
    $this->get('/history/katyn')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('supportUrl', config('site.support_url'))
        );
});

it('sends the reader to the real donation page', function (): void {
    expect(config('site.support_url'))->toBe('https://buymeacoffee.com/owsianka');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('supportUrl', 'https://buymeacoffee.com/owsianka')
        );
});

it('names the donation link in every language', function (): void {
    foreach (SetLocale::availableLocales() as $locale) {
        $line = trans('site.footer.support', [], $locale);

        expect($line)
            ->not->toBe('site.footer.support', "{$locale}: the footer link has no copy")
            ->not->toBe('');
    }
});
