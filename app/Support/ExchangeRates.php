<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Today's zloty rates, from the National Bank of Poland.
 *
 * A visitor reading about prices needs to know what a zloty is worth, and that
 * number changes daily, so printing it in the copy would mean printing
 * something wrong most of the time. The bank publishes one table per working
 * day and the API needs no key, so the page asks for it and caches the answer.
 *
 * If the bank cannot be reached the page simply shows no rates rather than a
 * stale or invented one.
 */
class ExchangeRates
{
    /**
     * @return array<int, array{code: string, rate: string, date: string}>
     */
    public function all(): array
    {
        /** @var array<int, array{code: string, rate: string, date: string}> */
        return Cache::flexible(
            'poland.rates',
            [
                now()->addMinutes((int) config('poland.rates.cache_minutes', 240)),
                now()->addDay(),
            ],
            fn (): array => $this->fetch(),
        );
    }

    /**
     * @return array<int, array{code: string, rate: string, date: string}>
     */
    private function fetch(): array
    {
        $rates = [];

        foreach ((array) config('poland.rates.codes', []) as $code) {
            $url = str_replace(':code', $code, (string) config('poland.rates.endpoint'));

            try {
                $response = Http::timeout(4)->acceptJson()->get($url);
            } catch (\Throwable $exception) {
                Log::info('Exchange rate lookup failed', ['code' => $code, 'error' => $exception->getMessage()]);

                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $mid = $response->json('rates.0.mid');
            $date = $response->json('rates.0.effectiveDate');

            if (! is_numeric($mid) || ! is_string($date)) {
                continue;
            }

            $rates[] = [
                'code' => strtoupper((string) $code),
                'rate' => number_format((float) $mid, 2, ',', ' '),
                'date' => $date,
            ];
        }

        return $rates;
    }
}
