<?php

declare(strict_types=1);

/**
 * One address per page on the live site.
 *
 * Every page is already published twice, once per language. Letting http,
 * https, www and the bare domain answer as well would turn each page into
 * eight in the eyes of a search engine, so production sends every other form
 * to the one https address on the host APP_URL names.
 */
beforeEach(function (): void {
    config(['app.url' => 'https://knowpoland.com']);
});

/** Run the rest of a test as if it were the live site. */
function asProduction(): void
{
    app()->instance('env', 'production');
    app()['env'] = 'production';
}

it('sends plain http to https, keeping the path and the language', function (): void {
    asProduction();

    $this->get('http://knowpoland.com/history/katyn?lang=pl')
        ->assertStatus(301)
        ->assertRedirect('https://knowpoland.com/history/katyn?lang=pl');
});

it('sends www to the bare domain', function (): void {
    asProduction();

    $this->get('https://www.knowpoland.com/places/krakow')
        ->assertStatus(301)
        ->assertRedirect('https://knowpoland.com/places/krakow');
});

it('answers the canonical address and tells the browser to stay on https', function (): void {
    asProduction();

    $this->get('https://knowpoland.com/')
        ->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

it('believes a proxy that says the reader is on https', function (): void {
    asProduction();

    // TLS ends at Cloudflare or a load balancer, and the request reaches the
    // server as plain http with the original scheme in a header. Redirecting
    // that would loop for ever.
    $this->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get('http://knowpoland.com/')
        ->assertOk();
});

it('leaves the health check alone so a load balancer can reach it over http', function (): void {
    asProduction();

    $this->get('http://knowpoland.com/up')->assertOk();
});

it('does nothing outside production, so a local server keeps working', function (): void {
    $response = $this->get('http://localhost/');

    $response->assertOk();

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
