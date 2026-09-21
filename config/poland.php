<?php

/**
 * Figures about present-day Poland that the page on everyday life prints.
 *
 * They are kept here rather than in the copy because they age at different
 * speeds and a reader is owed the date each one was last looked at. Anything
 * that can be fetched from a public source at request time is not here: the
 * exchange rate comes straight from the National Bank of Poland.
 *
 * Refresh them with `php artisan poland:figures`, which prints what it could
 * check and what still needs a human.
 */
return [

    /*
     * Each entry renders as one card. 'key' names the translation strings,
     * 'value' and 'unit' are printed large, and 'checked' is the date the
     * number was verified, in ISO form.
     */
    'figures' => [
        [
            'key' => 'gdp',
            'value' => '3,6 - 3,7%',
            'checked' => '2026-09-20',
        ],
        [
            'key' => 'inflation',
            'value' => '2,6 - 3,0%',
            'checked' => '2026-09-20',
        ],
        [
            'key' => 'unemployment',
            'value' => '5,8%',
            'checked' => '2026-09-20',
        ],
        [
            'key' => 'petrol',
            'value' => '~8 zł/l',
            'checked' => '2026-09-20',
        ],
        [
            'key' => 'minimum',
            'value' => '4 806 zł',
            'checked' => '2026-09-20',
        ],
    ],

    /*
     * What a day actually costs, which is the question a visitor asks before
     * any of the figures above. Ranges rather than single numbers, because the
     * gap between a small town and the middle of Warsaw is the real answer,
     * and every one carries the date it was checked.
     */
    'prices' => [
        ['key' => 'coffee', 'value' => '16 - 22 zł', 'checked' => '2026-09-20'],
        ['key' => 'beer', 'value' => '12 - 20 zł', 'checked' => '2026-09-20'],
        ['key' => 'lunch', 'value' => '25 - 70 zł', 'checked' => '2026-09-20'],
        ['key' => 'transit', 'value' => '4 - 6 zł', 'checked' => '2026-09-20'],
        ['key' => 'taxi', 'value' => '25 - 40 zł', 'checked' => '2026-09-20'],
        ['key' => 'train', 'value' => '70 - 150 zł', 'checked' => '2026-09-20'],
        ['key' => 'hotel', 'value' => '250 - 450 zł', 'checked' => '2026-09-20'],
        ['key' => 'rent', 'value' => '2 500 - 4 000 zł', 'checked' => '2026-09-20'],
    ],

    /*
     * The half of the figures above that has an open source.
     *
     * Bank Danych Lokalnych, run by Glowny Urzad Statystyczny, answers without
     * a key. Each entry names the series and how to print it, and the key
     * matches an entry in 'figures', whose typed value it replaces when the
     * fetch succeeds. Everything not listed here has no public endpoint that
     * returns a clean number, so it stays a dated value somebody looked up.
     */
    'statistics' => [
        'endpoint' => 'https://bdl.stat.gov.pl/api/v1/data/by-unit/:unit?var-id=:variable&format=json',
        'unit' => '000000000000',
        'fresh_hours' => 12,
        'stale_days' => 7,
        'variables' => [
            'unemployment' => ['id' => 60270, 'format' => '%s%%'],
        ],
    ],

    /*
     * The bank publishes one table a day on working days, so a cache of a few
     * hours is plenty and keeps the page from calling out on every request.
     */
    'rates' => [
        'endpoint' => 'https://api.nbp.pl/api/exchangerates/rates/a/:code/?format=json',
        'codes' => ['eur', 'usd', 'gbp'],
        'cache_minutes' => 240,
    ],
];
