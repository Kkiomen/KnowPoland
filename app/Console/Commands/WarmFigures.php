<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ExchangeRates;
use App\Support\Statistics;
use Illuminate\Console\Command;

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
        $fetched = $rates->all();
        $figures = $statistics->all();

        $this->info(sprintf(
            '%d exchange rates and %d statistics cached.',
            count($fetched),
            count($figures),
        ));

        // An outside service that is down is not a failed deployment: the page
        // simply prints what it has and says when it was checked.
        return self::SUCCESS;
    }
}
