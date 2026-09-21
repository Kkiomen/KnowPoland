<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * A cache for numbers fetched from somebody else's server.
 *
 * The exchange rates and the statistics come from public APIs that can be
 * slow, down, or unreachable from a given host. A plain cache handles none of
 * that well: it keeps an empty answer for as long as a good one, and a single
 * failed refresh replaces good figures with nothing. So an empty answer here
 * is never stored as the value. The last good answer is kept separately and
 * served instead, and when there has never been one, the next attempt waits a
 * few minutes rather than making every reader wait on a dead service.
 */
final class LiveCache
{
    /** How long a failed attempt holds off the next one. */
    private const RETRY_MINUTES = 10;

    /**
     * @template T of array
     *
     * @param  array{CarbonInterface, CarbonInterface}  $ttl  fresh until, then served stale until
     * @param  callable(): T  $fetch
     * @return T|array{}
     */
    public static function remember(string $key, array $ttl, callable $fetch): array
    {
        $lastGood = "{$key}.last";
        $holdOff = "{$key}.failed";

        if (Cache::has($holdOff)) {
            return Cache::get($lastGood, []);
        }

        $value = Cache::flexible($key, $ttl, function () use ($fetch, $lastGood): array {
            $fresh = $fetch();

            if ($fresh !== []) {
                Cache::forever($lastGood, $fresh);

                return $fresh;
            }

            return Cache::get($lastGood, []);
        });

        if ($value === []) {
            // nothing has ever come back: forget the empty answer so it is not
            // served for hours, and try again once the hold-off has passed
            Cache::forget($key);
            Cache::put($holdOff, true, now()->addMinutes(self::RETRY_MINUTES));
        }

        return $value;
    }
}
