<?php

declare(strict_types=1);

/**
 * The icons a browser, a phone and a launcher ask for.
 *
 * They shipped as the framework's own logo for a while, which is the kind of
 * thing nobody notices in review and everybody notices in a tab, so the set is
 * held here: it exists, it is the right size, and it is ours.
 */
it('serves an icon in every format a browser asks for', function (): void {
    $icons = [
        'favicon.ico' => null,
        'favicon.svg' => null,
        'apple-touch-icon.png' => [180, 180],
        'icon-192.png' => [192, 192],
        'icon-512.png' => [512, 512],
        'icon-maskable-512.png' => [512, 512],
    ];

    foreach ($icons as $file => $size) {
        $path = public_path($file);

        expect(is_file($path))->toBeTrue("{$file} is missing");

        if ($size !== null) {
            [$width, $height] = getimagesize($path);

            expect([$width, $height])->toBe($size, "{$file} is {$width}x{$height}");
        }
    }
});

it('draws the icons in the colours of the site, not the framework', function (): void {
    $svg = (string) file_get_contents(public_path('favicon.svg'));

    expect($svg)
        ->toContain('#07080a')
        ->toContain('#f2efe9')
        ->not->toContain('FF2D20');
});

it('points every page at the icons and the manifest', function (): void {
    $html = (string) $this->get('/history')->assertOk()->getContent();

    expect($html)
        ->toContain('<link rel="icon" href="/favicon.ico" sizes="any">')
        ->toContain('<link rel="icon" href="/favicon.svg" type="image/svg+xml">')
        ->toContain('<link rel="apple-touch-icon" href="/apple-touch-icon.png">')
        ->toContain('<link rel="manifest" href="/site.webmanifest">');
});

it('describes the site in a manifest a launcher can install', function (): void {
    $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true);

    expect($manifest['name'])->toBe(config('app.name'));
    expect($manifest['theme_color'])->toBe('#07080a');

    $purposes = array_column($manifest['icons'], 'purpose');

    expect($purposes)->toContain('maskable');

    foreach ($manifest['icons'] as $icon) {
        expect(is_file(public_path(ltrim($icon['src'], '/'))))
            ->toBeTrue("{$icon['src']} is listed in the manifest but not on disk");
    }
});
