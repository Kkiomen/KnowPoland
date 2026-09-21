<?php

declare(strict_types=1);

/**
 * Addresses the site points at but does not own.
 *
 * They live here rather than in a component or in the copy, because they are
 * not text a translator should ever touch and because a broken one has to be
 * fixable in a single place.
 */
return [

    /*
     * Where the "buy me a coffee" button goes. The site takes no advertising
     * and sells nothing, so this is the only way a reader can give anything
     * back, and it is deliberately optional.
     */
    'support_url' => env('SUPPORT_URL', 'https://buymeacoffee.com/owsianka'),

    /*
     * Counting readers, if and when the site ever does.
     *
     * Nothing is loaded unless both values are set, so out of the box the site
     * ships no third party script, sets no cookie and needs no consent banner.
     * The pair suits a counter that works that way, Plausible or Fathom among
     * them: the script address and the domain it counts under.
     */
    'analytics' => [
        'script' => env('ANALYTICS_SCRIPT'),
        'domain' => env('ANALYTICS_DOMAIN'),
    ],

];
