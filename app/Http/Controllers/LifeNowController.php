<?php

namespace App\Http\Controllers;

use App\Support\ExchangeRates;
use App\Support\Statistics;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page about living in Poland now.
 *
 * It is the one page on the site with numbers that move, so it is the one page
 * that needs a controller. Figures with an open source are fetched and marked
 * live, the rest come out of config with the date each was checked, and the
 * exchange rates come from the National Bank of Poland.
 */
class LifeNowController extends Controller
{
    public function __invoke(ExchangeRates $rates, Statistics $statistics): Response
    {
        return Inertia::render('LifeNow', [
            'figures' => $this->figures($statistics),
            'prices' => config('poland.prices', []),
            'rates' => $rates->all(),
            // Some hosts only let a server reach a short list of addresses, and
            // the bank is not on it. The API answers any browser, so when the
            // server came back empty the page asks for the rates itself.
            'rateSource' => [
                'endpoint' => config('poland.rates.endpoint'),
                'codes' => config('poland.rates.codes'),
            ],
        ]);
    }

    /**
     * Config values, with the fetched ones written over the top.
     *
     * A figure that came back from the statistics office carries the year of
     * the reading rather than the day somebody looked it up, because that is
     * the honest label for it.
     *
     * @return array<int, array{key: string, value: string, checked: string, live?: bool, year?: string}>
     */
    private function figures(Statistics $statistics): array
    {
        $live = $statistics->all();

        return array_map(function (array $figure) use ($live): array {
            $fetched = $live[$figure['key']] ?? null;

            if ($fetched === null) {
                return $figure;
            }

            return [...$figure, 'value' => $fetched['value'], 'year' => $fetched['year'], 'live' => true];
        }, (array) config('poland.figures', []));
    }
}
