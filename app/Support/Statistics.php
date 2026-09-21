<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Figures the Central Statistical Office publishes, fetched rather than typed.
 *
 * The everyday life page prints numbers that age. Some of them have an open
 * source and some do not, and the difference matters to a reader: a figure the
 * page fetched today is worth more than one somebody typed in once. This class
 * covers the fetchable half, from the Bank Danych Lokalnych API of Glowny Urzad
 * Statystyczny, which needs no key.
 *
 * The cache is flexible on purpose. A visitor arriving after the value has gone
 * stale is served the old one straight away and the refresh happens behind the
 * request, so nobody ever waits on a government API to answer.
 */
class Statistics
{
    /**
     * Live values keyed the same way as the entries in config/poland.php.
     *
     * @return array<string, array{value: string, year: string}>
     */
    public function all(): array
    {
        /** @var array<string, array{value: string, year: string}> */
        return Cache::flexible(
            'poland.statistics',
            [
                now()->addHours((int) config('poland.statistics.fresh_hours', 12)),
                now()->addDays((int) config('poland.statistics.stale_days', 7)),
            ],
            fn (): array => $this->fetch(),
        );
    }

    /**
     * @return array<string, array{value: string, year: string}>
     */
    private function fetch(): array
    {
        $fetched = [];

        foreach ((array) config('poland.statistics.variables', []) as $key => $variable) {
            $point = $this->latest((int) $variable['id']);

            if ($point === null) {
                continue;
            }

            $fetched[$key] = [
                'value' => sprintf($variable['format'], number_format($point['value'], 1, ',', ' ')),
                'year' => $point['year'],
            ];
        }

        return $fetched;
    }

    /**
     * The most recent point of one national series.
     *
     * @return array{value: float, year: string}|null
     */
    private function latest(int $variable): ?array
    {
        $url = str_replace(
            [':unit', ':variable'],
            [(string) config('poland.statistics.unit'), (string) $variable],
            (string) config('poland.statistics.endpoint'),
        );

        try {
            $response = Http::timeout(6)->acceptJson()->get($url);
        } catch (\Throwable $exception) {
            Log::info('Statistics lookup failed', ['variable' => $variable, 'error' => $exception->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $values = $response->json('results.0.values');

        if (! is_array($values) || $values === []) {
            return null;
        }

        $last = end($values);

        if (! is_array($last) || ! is_numeric($last['val'] ?? null) || ! isset($last['year'])) {
            return null;
        }

        return ['value' => (float) $last['val'], 'year' => (string) $last['year']];
    }
}
