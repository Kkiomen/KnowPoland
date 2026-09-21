<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ExchangeRates;
use App\Support\Statistics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Fill the caches the page about Poland today reads from.
 *
 * Two of its numbers come from outside: the zloty rates from the National
 * Bank of Poland and the unemployment figure from the statistics office.
 * Both are cached, but the first request after a deployment finds the cache
 * empty and waits for those two services to answer. Run this at the end of a
 * deployment, and on a schedule if you want, so that the waiting is done by a
 * command rather than by a reader.
 */
class WarmFigures extends Command
{
    protected $signature = 'poland:warm';

    protected $description = 'Fetch the exchange rates and statistics the Poland today page prints';

    public function handle(ExchangeRates $rates, Statistics $statistics): int
    {
        // Somebody running this by hand wants a real attempt now, not the
        // ten minute pause a failed request from a reader leaves behind.
        Cache::forget('poland.rates.failed');
        Cache::forget('poland.statistics.failed');

        $fetched = $rates->all();
        $figures = $statistics->all();

        $this->info(sprintf(
            '%d exchange rates and %d statistics cached.',
            count($fetched),
            count($figures),
        ));

        // An empty result says nothing about why, and on a production log level
        // the lookups' own notes are dropped, so the reason is asked for here.
        if ($fetched === []) {
            $this->warn('National Bank of Poland: '.$this->probe(str_replace(
                ':code',
                (string) (config('poland.rates.codes')[0] ?? 'eur'),
                (string) config('poland.rates.endpoint'),
            )));
        }

        if ($figures === []) {
            $variable = (array) (array_values((array) config('poland.statistics.variables'))[0] ?? []);

            $this->warn('Statistics Poland: '.$this->probe(str_replace(
                [':unit', ':variable'],
                [(string) config('poland.statistics.unit'), (string) ($variable['id'] ?? '')],
                (string) config('poland.statistics.endpoint'),
            )));
        }

        // An outside service that is down is not a failed deployment: the page
        // simply prints what it has and says when it was checked.
        return self::SUCCESS;
    }

    /** What happens when this server asks the address directly. */
    private function probe(string $url): string
    {
        try {
            $response = Http::timeout(10)->acceptJson()->get($url);
        } catch (\Throwable $exception) {
            return 'no answer - '.$exception->getMessage();
        }

        return sprintf('HTTP %d from %s', $response->status(), $url);
    }
}
