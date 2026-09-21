<?php

declare(strict_types=1);

use App\Support\LiveCache;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Numbers fetched from somebody else's server.
 *
 * The live site could not reach the bank on its first request, and the empty
 * answer was then kept for hours. These pin down what should happen instead.
 */
beforeEach(fn () => Cache::flush());

/** @return array{CarbonInterface, CarbonInterface} */
function liveTtl(): array
{
    return [now()->addHours(4), now()->addDay()];
}

it('does not keep an empty answer as if it were the value', function (): void {
    $calls = 0;

    LiveCache::remember('test.rates', liveTtl(), function () use (&$calls): array {
        $calls++;

        return [];
    });

    expect(Cache::has('test.rates'))->toBeFalse()
        ->and(Cache::has('test.rates.failed'))->toBeTrue();

    // within the hold-off nobody waits on the dead service again
    LiveCache::remember('test.rates', liveTtl(), function () use (&$calls): array {
        $calls++;

        return [];
    });

    expect($calls)->toBe(1);
});

it('tries again once the hold-off has passed', function (): void {
    LiveCache::remember('test.rates', liveTtl(), fn (): array => []);

    $this->travel(11)->minutes();

    $value = LiveCache::remember('test.rates', liveTtl(), fn (): array => ['EUR' => '4,25']);

    expect($value)->toBe(['EUR' => '4,25']);
});

it('keeps the last good figures when a later fetch fails', function (): void {
    LiveCache::remember('test.rates', liveTtl(), fn (): array => ['EUR' => '4,25']);

    // the stored value expires, and the service is down when it is refetched
    Cache::forget('test.rates');

    $value = LiveCache::remember('test.rates', liveTtl(), fn (): array => []);

    expect($value)->toBe(['EUR' => '4,25']);
});
