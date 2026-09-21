<?php

namespace App\Console\Commands;

use App\Support\ExchangeRates;
use App\Support\Statistics;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Check how old the figures on the everyday life page are.
 *
 * The exchange rates look after themselves, so this drops their cache and
 * pulls them again. Everything else on that page is a number somebody has to
 * look up, so the command does the one thing software can honestly do here:
 * say which entries are stale and where to change them.
 */
class RefreshPolandFigures extends Command
{
    protected $signature = 'poland:figures {--days=90 : Treat a figure as stale after this many days}';

    protected $description = 'Refresh the live exchange rates and report which figures need checking by hand';

    public function handle(ExchangeRates $rates, Statistics $statistics): int
    {
        Cache::forget('poland.rates');
        Cache::forget('poland.statistics');

        foreach ($statistics->all() as $key => $point) {
            $this->line(sprintf('  %s  %s  (reading for %s, fetched live)', $key, $point['value'], $point['year']));
        }

        $fetched = $rates->all();

        if ($fetched === []) {
            $this->warn('The National Bank of Poland could not be reached, so no rates were refreshed.');
        } else {
            foreach ($fetched as $rate) {
                $this->line(sprintf('  %s  %s zl  (table of %s)', $rate['code'], $rate['rate'], $rate['date']));
            }
        }

        $limit = (int) $this->option('days');
        $stale = [];

        $live = array_keys((array) config('poland.statistics.variables', []));

        foreach ((array) config('poland.figures', []) as $figure) {
            if (in_array($figure['key'], $live, true)) {
                continue;
            }

            $checked = Carbon::parse($figure['checked']);

            if ($checked->diffInDays(now()) > $limit) {
                $stale[] = sprintf('  %s: %s, last checked %s (%d days ago)',
                    $figure['key'], $figure['value'], $figure['checked'], (int) $checked->diffInDays(now()));
            }
        }

        if ($stale === []) {
            $this->info(sprintf('Every figure was checked within the last %d days.', $limit));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('These need looking up and updating in config/poland.php:');

        foreach ($stale as $line) {
            $this->line($line);
        }

        return self::SUCCESS;
    }
}
