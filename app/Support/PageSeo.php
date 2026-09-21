<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

/**
 * Everything a search engine or a link scraper needs to know about a page.
 *
 * The application renders without server-side rendering, so a crawler only
 * ever sees what the blade template printed. This registry is where a page
 * declares what that head should say: which translation group holds its copy,
 * which picture represents it, what kind of thing it is and where it hangs in
 * the site. One entry per page, and the test suite fails on a page without one.
 *
 * @phpstan-type Entry array{group: string, route: string, image: string, kind: string, parent: ?string, geo?: array{float, float}, robots?: string}
 */
final class PageSeo
{
    /** Social cards are generated at the size every network crops to. */
    public const CARD_WIDTH = 1200;

    public const CARD_HEIGHT = 630;

    /** The query parameter that carries the language, as SetLocale reads it. */
    public const LANGUAGE_KEY = 'lang';

    /** Where the generated cards live, relative to the public directory. */
    public const CARD_DIRECTORY = '/images/cards';

    /**
     * Every page of the site, keyed by its Inertia component.
     *
     * - group: the branch of lang/<locale>/site.php holding its copy
     * - route: the named route, used for canonical addresses and breadcrumbs
     * - image: the source picture the social card is cut from
     * - kind: home, hub, article, place or page, which decides the structured data
     * - parent: the page above it in the breadcrumb, by route name
     * - geo: latitude and longitude, for a place
     *
     * @var array<string, Entry>
     */
    private const PAGES = [
        'Welcome' => ['group' => 'home', 'route' => 'home', 'image' => '/images/tlo-history-1400.jpg', 'kind' => 'home', 'parent' => null],
        'History' => ['group' => 'history', 'route' => 'history', 'image' => '/images/tlo-history-1400.jpg', 'kind' => 'hub', 'parent' => 'home'],
        'HistoryOrigins' => ['group' => 'origins', 'route' => 'history.origins', 'image' => '/images/origins-biskupin-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryEagle' => ['group' => 'eagle', 'route' => 'history.eagle', 'image' => '/images/eagle-evolution-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryBaptism' => ['group' => 'baptism', 'route' => 'history.baptism', 'image' => '/images/baptism-annal-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryGniezno' => ['group' => 'gniezno', 'route' => 'history.gniezno', 'image' => '/images/gniezno-seats-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryCasimir' => ['group' => 'casimir', 'route' => 'history.casimir', 'image' => '/images/casimir-today-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryWhere' => ['group' => 'where', 'route' => 'history.where', 'image' => '/images/where-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryGrunwald' => ['group' => 'grunwald', 'route' => 'history.grunwald', 'image' => '/images/grunwald-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryGoldenAge' => ['group' => 'golden', 'route' => 'history.golden-age', 'image' => '/images/golden-courtyard-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryFaiths' => ['group' => 'faiths', 'route' => 'history.faiths', 'image' => '/images/faiths-act-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryVienna' => ['group' => 'vienna', 'route' => 'history.vienna', 'image' => '/images/vienna-plan-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistorySerfdom' => ['group' => 'serfdom', 'route' => 'history.serfdom', 'image' => '/images/serfdom-cross-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryVeto' => ['group' => 'veto', 'route' => 'history.veto', 'image' => '/images/veto-bellotto-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryConstitution' => ['group' => 'constitution', 'route' => 'history.constitution', 'image' => '/images/constitution-wojniakowski-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryFirstPartition' => ['group' => 'partition1', 'route' => 'history.first-partition', 'image' => '/images/partition1-rejtan-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistorySecondPartition' => ['group' => 'partition2', 'route' => 'history.second-partition', 'image' => '/images/partition2-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryKosciuszko' => ['group' => 'kosciuszko', 'route' => 'history.kosciuszko', 'image' => '/images/kosciuszko-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryThirdPartition' => ['group' => 'partition3', 'route' => 'history.third-partition', 'image' => '/images/partition3-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryUprisings' => ['group' => 'uprisings', 'route' => 'history.uprisings', 'image' => '/images/uprisings-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryKeeping' => ['group' => 'keeping', 'route' => 'history.keeping', 'image' => '/images/keeping-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryThreeEmpires' => ['group' => 'empires', 'route' => 'history.three-empires', 'image' => '/images/empires-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistorySecondRepublic' => ['group' => 'republic', 'route' => 'history.second-republic', 'image' => '/images/republic-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryWarsaw1920' => ['group' => 'war1920', 'route' => 'history.warsaw-1920', 'image' => '/images/war1920-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistorySeptember1939' => ['group' => 'sept1939', 'route' => 'history.september-1939', 'image' => '/images/sept1939-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryDanzig' => ['group' => 'danzig', 'route' => 'history.danzig', 'image' => '/images/danzig-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryWesterplatte' => ['group' => 'westerplatte', 'route' => 'history.westerplatte', 'image' => '/images/westerplatte-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryPact' => ['group' => 'pact', 'route' => 'history.pact', 'image' => '/images/pact-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryExile' => ['group' => 'exile', 'route' => 'history.exile', 'image' => '/images/exile-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryGeneralGovernment' => ['group' => 'gg', 'route' => 'history.general-government', 'image' => '/images/gg-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryKatyn' => ['group' => 'katyn', 'route' => 'history.katyn', 'image' => '/images/katyn-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryZamosc' => ['group' => 'zamosc', 'route' => 'history.zamosc', 'image' => '/images/zamosc-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryVolhynia' => ['group' => 'volhynia', 'route' => 'history.volhynia', 'image' => '/images/volhynia-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryGhettos' => ['group' => 'ghettos', 'route' => 'history.ghettos', 'image' => '/images/ghettos-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryCamps' => ['group' => 'camps', 'route' => 'history.camps', 'image' => '/images/camps-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryGhettoUprising' => ['group' => 'ghettouprising', 'route' => 'history.ghetto-uprising', 'image' => '/images/ghettouprising-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryUnderground' => ['group' => 'underground', 'route' => 'history.underground', 'image' => '/images/underground-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryPilecki' => ['group' => 'pilecki', 'route' => 'history.pilecki', 'image' => '/images/pilecki-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryKarski' => ['group' => 'karski', 'route' => 'history.karski', 'image' => '/images/karski-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryEnigma' => ['group' => 'enigma', 'route' => 'history.enigma', 'image' => '/images/enigma-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryForces' => ['group' => 'forces', 'route' => 'history.forces', 'image' => '/images/forces-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryRising' => ['group' => 'rising', 'route' => 'history.rising', 'image' => '/images/rising-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history.war'],
        'HistoryOccupation' => ['group' => 'occupation', 'route' => 'history.occupation', 'image' => '/images/occupation-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryWar' => ['group' => 'war', 'route' => 'history.war', 'image' => '/images/tlo-memory-1400.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryBorders' => ['group' => 'borders', 'route' => 'history.borders', 'image' => '/images/borders-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryRebuilding' => ['group' => 'rebuilding', 'route' => 'history.rebuilding', 'image' => '/images/rebuilding-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistorySolidarity' => ['group' => 'solidarity', 'route' => 'history.solidarity', 'image' => '/images/solidarity-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryJune1989' => ['group' => 'june1989', 'route' => 'history.june-1989', 'image' => '/images/june1989-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryTransition' => ['group' => 'transition', 'route' => 'history.transition', 'image' => '/images/transition-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryEurope' => ['group' => 'euro', 'route' => 'history.europe', 'image' => '/images/euro-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'HistoryToday' => ['group' => 'today', 'route' => 'history.today', 'image' => '/images/today-hero-lg.jpg', 'kind' => 'article', 'parent' => 'history'],
        'Food' => ['group' => 'food', 'route' => 'food', 'image' => '/images/food-hero-lg.jpg', 'kind' => 'page', 'parent' => 'home'],
        'Everyday' => ['group' => 'everyday', 'route' => 'everyday', 'image' => '/images/tlo-life-1400.jpg', 'kind' => 'page', 'parent' => 'home'],
        'Method' => ['group' => 'method', 'route' => 'method', 'image' => '/images/tlo-history-1400.jpg', 'kind' => 'page', 'parent' => 'home'],
        'Start' => ['group' => 'start', 'route' => 'start', 'image' => '/images/tlo-history-1400.jpg', 'kind' => 'page', 'parent' => 'home'],
        'Roots' => ['group' => 'roots', 'route' => 'roots', 'image' => '/images/roots-hero-lg.jpg', 'kind' => 'page', 'parent' => 'home'],
        'Places' => ['group' => 'places', 'route' => 'places', 'image' => '/images/tlo-places-1400.jpg', 'kind' => 'hub', 'parent' => 'home'],
        'PlaceGdansk' => ['group' => 'gdansk', 'route' => 'places.gdansk', 'image' => '/images/gdansk-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [54.352, 18.6466]],
        'PlaceWarszawa' => ['group' => 'warszawa', 'route' => 'places.warszawa', 'image' => '/images/warszawa-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [52.2297, 21.0122]],
        'PlaceKrakow' => ['group' => 'krakow', 'route' => 'places.krakow', 'image' => '/images/krakow-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [50.0647, 19.945]],
        'PlaceWroclaw' => ['group' => 'wroclaw', 'route' => 'places.wroclaw', 'image' => '/images/wroclaw-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [51.1079, 17.0385]],
        'PlaceLodz' => ['group' => 'lodz', 'route' => 'places.lodz', 'image' => '/images/lodz-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [51.7592, 19.456]],
        'PlacePoznan' => ['group' => 'poznan', 'route' => 'places.poznan', 'image' => '/images/poznan-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [52.4064, 16.9252]],
        'PlaceSzczecin' => ['group' => 'szczecin', 'route' => 'places.szczecin', 'image' => '/images/szczecin-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [53.4285, 14.5528]],
        'PlaceBydgoszcz' => ['group' => 'bydgoszcz', 'route' => 'places.bydgoszcz', 'image' => '/images/bydgoszcz-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [53.1235, 18.0084]],
        'PlaceLublin' => ['group' => 'lublin', 'route' => 'places.lublin', 'image' => '/images/lublin-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [51.2465, 22.5684]],
        'PlaceKatowice' => ['group' => 'katowice', 'route' => 'places.katowice', 'image' => '/images/katowice-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [50.2649, 19.0238]],
        'PlaceZakopane' => ['group' => 'zakopane', 'route' => 'places.zakopane', 'image' => '/images/zakopane-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [49.2992, 19.9497]],
        'PlaceTorun' => ['group' => 'torun', 'route' => 'places.torun', 'image' => '/images/torun-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [53.0138, 18.5984]],
        'PlaceMalbork' => ['group' => 'malbork', 'route' => 'places.malbork', 'image' => '/images/malbork-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [54.0397, 19.0279]],
        'PlaceWieliczka' => ['group' => 'wieliczka', 'route' => 'places.wieliczka', 'image' => '/images/wieliczka-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [49.9847, 20.0546]],
        'PlaceBialowieza' => ['group' => 'bialowieza', 'route' => 'places.bialowieza', 'image' => '/images/bialowieza-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [52.7003, 23.8419]],
        'PlaceMazury' => ['group' => 'mazury', 'route' => 'places.mazury', 'image' => '/images/mazury-panorama-lg.jpg', 'kind' => 'place', 'parent' => 'places', 'geo' => [53.8022, 21.5675]],
        'LifeNow' => ['group' => 'life_now', 'route' => 'life-now', 'image' => '/images/tlo-now-1400.jpg', 'kind' => 'page', 'parent' => 'home'],
    ];

    /**
     * The component the error handler renders, which has no address of its own.
     *
     * It is deliberately outside PAGES: a reader lands on it by mistake, it
     * answers on whatever address was asked for, and it belongs in no sitemap
     * and no index. Everything else about a page it still needs.
     *
     * @var Entry
     */
    public const ERROR_COMPONENT = 'NotFound';

    private const ERROR_ENTRY = [
        'group' => 'notfound',
        'route' => 'home',
        'image' => '/images/tlo-history-1400.jpg',
        'kind' => 'page',
        'parent' => null,
        'robots' => 'noindex, follow',
    ];

    /**
     * Every registered page, keyed by component.
     *
     * @return array<string, Entry>
     */
    public static function pages(): array
    {
        return self::PAGES;
    }

    /**
     * The registry entry for a component, falling back to the home page so a
     * page that has not been registered yet still renders a usable head.
     *
     * @return Entry
     */
    public static function entry(string $component): array
    {
        if ($component === self::ERROR_COMPONENT) {
            return self::ERROR_ENTRY;
        }

        return self::PAGES[$component] ?? self::PAGES['Welcome'];
    }

    /**
     * The registry entry belonging to a named route.
     *
     * @return ?Entry
     */
    public static function entryForRoute(string $route): ?array
    {
        foreach (self::PAGES as $entry) {
            if ($entry['route'] === $route) {
                return $entry;
            }
        }

        return null;
    }

    /** The social card cut for a page, as a path under the public directory. */
    public static function cardPath(string $group): string
    {
        return self::CARD_DIRECTORY.'/'.$group.'.jpg';
    }

    /**
     * The whole head of one page: what the blade template prints and what the
     * tests read back.
     *
     * @return array{
     *     group: string,
     *     kind: string,
     *     locale: string,
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     alternates: array<string, string>,
     *     defaultAddress: string,
     *     image: string,
     *     imageWidth: int,
     *     imageHeight: int,
     *     ogType: string,
     *     robots: string,
     *     breadcrumbs: array<int, array{name: string, url: string}>,
     *     geo: ?array{float, float},
     * }
     */
    public static function head(string $component, Request $request): array
    {
        $entry = self::entry($component);
        $group = $entry['group'];

        $locale = app()->getLocale();
        $default = self::defaultLocale();
        $path = self::pathOf($request);

        // A visitor who once chose Polish keeps it in the session, so the
        // English address can serve Polish copy. The canonical still has to
        // describe the address that was asked for, or one page would claim to
        // be another. A crawler has no session and always gets the default.
        $addressed = self::addressedLocale($request);

        $alternates = [];
        foreach (SetLocale::availableLocales() as $language) {
            $alternates[$language] = self::address($path, $language);
        }

        [$width, $height] = self::cardSize($group, $entry['image']);

        return [
            'group' => $group,
            'kind' => $entry['kind'],
            'locale' => $locale,
            'title' => self::copy($group, 'title'),
            'description' => self::copy($group, 'description'),
            'canonical' => self::address($path, $addressed),
            'alternates' => $alternates,
            'defaultAddress' => self::address($path, $default),
            'image' => url(self::cardFor($group, $entry['image'])),
            'imageWidth' => $width,
            'imageHeight' => $height,
            'ogType' => $entry['kind'] === 'article' ? 'article' : 'website',
            'robots' => $entry['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1',
            'breadcrumbs' => self::breadcrumbs($entry),
            'geo' => $entry['geo'] ?? null,
        ];
    }

    /**
     * The trail from the home page down to this one, as names and addresses.
     *
     * @param  Entry  $entry
     * @return array<int, array{name: string, url: string}>
     */
    public static function breadcrumbs(array $entry): array
    {
        $locale = app()->getLocale();
        $trail = [];
        $current = $entry;

        while (true) {
            array_unshift($trail, [
                'name' => $current['kind'] === 'home'
                    ? (string) trans('site.brand')
                    : self::copy($current['group'], 'title'),
                'url' => self::address(self::pathOfRoute($current['route']), $locale),
            ]);

            if ($current['parent'] === null) {
                break;
            }

            $parent = self::entryForRoute($current['parent']);

            if ($parent === null) {
                break;
            }

            $current = $parent;
        }

        return $trail;
    }

    /** A page title or description, from the translation file of the active locale. */
    public static function copy(string $group, string $key): string
    {
        $line = trans("site.{$group}.meta.{$key}");

        return is_string($line) ? $line : '';
    }

    /** The locale the address itself names, which is the default unless ?lang= says otherwise. */
    public static function addressedLocale(Request $request): string
    {
        $requested = $request->query(self::LANGUAGE_KEY);

        return is_string($requested) && in_array($requested, SetLocale::availableLocales(), true)
            ? $requested
            : self::defaultLocale();
    }

    /** The locale the site falls back to, and the one with no ?lang= in its address. */
    public static function defaultLocale(): string
    {
        $locale = config('app.fallback_locale', 'en');

        return is_string($locale) ? $locale : 'en';
    }

    /**
     * The address of a path in one language.
     *
     * Only the language survives from the query string, so a link carrying
     * campaign parameters cannot turn one page into two in an index.
     */
    public static function address(string $path, string $locale): string
    {
        return url($path).($locale === self::defaultLocale() ? '' : '?lang='.$locale);
    }

    /** The path of the current request, leading slash, empty for the home page. */
    private static function pathOf(Request $request): string
    {
        $path = trim($request->path(), '/');

        return $path === '' ? '' : '/'.$path;
    }

    /** The path of a named route, in the same shape. */
    private static function pathOfRoute(string $name): string
    {
        $uri = trim((string) str_replace(url('/'), '', route($name)), '/');

        return $uri === '' ? '' : '/'.$uri;
    }

    /** The generated card when it exists, the source picture until it does. */
    private static function cardFor(string $group, string $source): string
    {
        $card = self::cardPath($group);

        return is_file(public_path(ltrim($card, '/'))) ? $card : $source;
    }

    /**
     * The real pixel size of the picture a scraper is about to fetch.
     *
     * @return array{int, int}
     */
    private static function cardSize(string $group, string $source): array
    {
        $path = public_path(ltrim(self::cardFor($group, $source), '/'));

        if (! is_file($path)) {
            return [self::CARD_WIDTH, self::CARD_HEIGHT];
        }

        $size = @getimagesize($path);

        return $size === false ? [self::CARD_WIDTH, self::CARD_HEIGHT] : [$size[0], $size[1]];
    }
}
