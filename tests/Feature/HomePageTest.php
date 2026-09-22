<?php

declare(strict_types=1);

use App\Support\PageSeo;
use App\Support\Statistics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;

it('renders the home page with shared translations', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Welcome')
            ->where('locale', 'en')
            ->where('translations.home.masthead.wordmark', 'knowpoland')
            ->where('translations.home.masthead.wordmark_suffix', '.com')
            ->has('translations.home.sections', 6)
            ->has('availableLocales')
        );
});

it('serves English copy by default', function (): void {
    $this->get('/')->assertSee('Get to Know Poland', escape: false);
});

it('ignores a language that has no translation file', function (): void {
    $this->get('/?lang=de')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'en'));
});

it('switches to Polish when asked', function (): void {
    $this->get('/?lang=pl')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'pl')
            ->where('translations.home.meta.title', 'Poznaj Polskę')
            // the wordmark is the address, so a screenshot in any language leads back here
            ->where('translations.home.masthead.wordmark', 'knowpoland')
        );
});

it('offers every locale that has a translation directory', function (): void {
    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('availableLocales', fn ($locales) => collect($locales)->contains('en')
                && collect($locales)->contains('pl'))
        );
});

it('keeps every translation file on the same set of keys', function (): void {
    $flatten = function (array $lines, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($lines as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $keys = is_array($value)
                ? array_merge($keys, $flatten($value, $path))
                : array_merge($keys, [$path]);
        }

        return $keys;
    };

    $english = $flatten(require lang_path('en/site.php'));

    foreach (File::directories(lang_path()) as $directory) {
        $locale = basename($directory);

        if ($locale === 'en') {
            continue;
        }

        $translated = $flatten(require lang_path("{$locale}/site.php"));

        expect(array_diff($english, $translated))->toBe([], "{$locale} is missing keys")
            ->and(array_diff($translated, $english))->toBe([], "{$locale} has keys English does not");
    }
});

it('keeps every home page string in the translation file', function (): void {
    $source = file_get_contents(resource_path('js/pages/Welcome.vue'));

    expect($source)->not->toContain('Get to know Poland')
        ->and($source)->not->toContain('A thousand years, in the order');
});

it('gives every home page section a photograph that exists', function (): void {
    $sections = trans('site.home.sections');

    expect($sections)->toHaveCount(6);

    foreach ($sections as $key => $section) {
        expect($section)->toHaveKeys(['photo_caption', 'photo_credit', 'title', 'lede', 'items', 'link'])
            ->and($section['items'])->toHaveCount(3);

        foreach ([1000, 1400] as $width) {
            expect(public_path("images/tlo-{$key}-{$width}.jpg"))->toBeReadableFile();
        }
    }
});

it('keeps the opening photograph and the coat of arms in place', function (): void {
    expect(public_path('images/tlo-tatry-1000.jpg'))->toBeReadableFile()
        ->and(public_path('images/tlo-tatry-1400.jpg'))->toBeReadableFile()
        ->and(public_path('images/godlo-polski.svg'))->toBeReadableFile();
});

it('renders the history page with six eras', function (): void {
    $this->get('/history')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('History')
            ->has('translations.history.eras', 6)
            ->where('translations.history.hero.title', 'History')
        );
});

it('gives every history era a set of topics', function (): void {
    $eras = trans('site.history.eras');

    foreach ($eras as $key => $era) {
        expect($era)->toHaveKeys(['index', 'years', 'label', 'title', 'lede', 'topics'])
            ->and(count($era['topics']))->toBeGreaterThanOrEqual(3, "{$key} needs topics");

        foreach ($era['topics'] as $topic) {
            expect($topic)->toHaveKeys(['title', 'note']);
        }
    }
});

it('serves the history page in Polish too', function (): void {
    $this->get('/history?lang=pl')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('locale', 'pl')
            ->where('translations.history.eras.commonwealth.label', 'Szlachta')
        );
});

it('renders the second world war page', function (): void {
    $this->get('/history/second-world-war')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryWar')
            ->has('translations.war.chapters', 6)
            ->has('translations.war.timeline.items', 9)
        );
});

it('keeps the war page honest about wording and sources', function (): void {
    $war = trans('site.war');

    expect($war['language']['title'])->toContain('occupied Poland')
        ->and($war['hero']['note'])->toContain('estimates')
        ->and($war['visiting']['etiquette'])->toHaveCount(4);

    foreach ($war['chapters'] as $key => $chapter) {
        expect($chapter)->toHaveKeys(['index', 'label', 'title', 'lede', 'topics'])
            ->and(count($chapter['topics']))->toBeGreaterThanOrEqual(4, "{$key} needs topics");
    }
});

it('splits the partitions into three separate topics', function (): void {
    $topics = collect(trans('site.history.eras.partitions.topics'))->pluck('title');

    expect($topics)->toContain('The First Partition, 1772')
        ->toContain('The Second Partition, 1793')
        ->toContain('The Third Partition, 1795');
});

it('introduces Kosciuszko between the second and third partitions', function (): void {
    $topics = collect(trans('site.history.eras.partitions.topics'))->pluck('title')->values();

    expect($topics->search('Kosciuszko, 1794'))
        ->toBe($topics->search('The Second Partition, 1793') + 1)
        ->toBe($topics->search('The Third Partition, 1795') - 1);
});

it('covers what came before the baptism', function (): void {
    $topics = collect(trans('site.history.eras.beginnings.topics'))->pluck('title');

    expect($topics->first())->toContain('How Poland came to be')
        ->and($topics->implode(' '))->toContain('Lech');
});

it('renders the first article as a filmstrip of frames', function (): void {
    $this->get('/history/how-poland-began')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryOrigins')
            ->has('translations.origins.frames', 8)
        );
});

it('credits every frame of the article and ships its photographs', function (): void {
    foreach (trans('site.origins.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/origins-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }
});

it('bridges the gap between the tribes and Mieszko I', function (): void {
    $frames = collect(trans('site.origins.frames'))->keyBy('key');

    expect($frames)->toHaveKey('rise')
        ->and($frames['rise']['body'])->toContain('Siemomysl')
        ->and($frames['rise']['body'])->toContain('Ibrahim ibn Yaqub')
        ->and(array_search('rise', array_keys($frames->all()), true))
        ->toBeLessThan(array_search('mieszko', array_keys($frames->all()), true));
});

it('names the people it mentions instead of leaving the reader guessing', function (): void {
    $frames = collect(trans('site.origins.frames'))->keyBy('key');

    expect($frames['adalbert']['body'])->toContain('Otto III')
        ->and($frames['adalbert']['body'])->toContain('Gallus Anonymus')
        ->and($frames['legend']['body'])->toContain('Popiel')
        ->and(trans('site.war.chapters.invasion.topics.1.note'))->toContain('Schleswig-Holstein');
});

it('introduces every ruler before it needs him', function (): void {
    $frames = collect(trans('site.origins.frames'));
    $keys = $frames->pluck('key')->all();
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // Mieszko dies on the page, so his successor does not arrive out of nowhere
    expect($frames[array_search('mieszko', $keys, true)]['body'])->toContain('992')
        ->and($firstMention('Boleslaw'))->toBeLessThanOrEqual(array_search('adalbert', $keys, true));

    // the crown says why him and why 1025, not just that it happened
    $crown = $frames[array_search('crown', $keys, true)]['body'];

    expect($crown)->toContain('Henry II')
        ->and($crown)->toContain('1018')
        ->and($crown)->toContain('1024')
        // the chain has to hold: Otto dies, a successor reverses the policy,
        // someone actually performs the coronation
        ->and($crown)->toContain('Otto III died')
        ->and($crown)->toContain('Conrad II')
        ->and($crown)->toContain('archbishop of Gniezno crowned him');
});

it('says plainly which parts of the origin story are legend', function (): void {
    $frames = collect(trans('site.origins.frames'))->keyBy('key');

    expect($frames['legend']['body'])->toContain('written down')
        ->and(trans('site.origins.sources.body'))->toContain('Gallus Anonymus')
        ->and(trans('site.origins.sources.body'))->toContain('Bavarian Geographer')
        ->and(trans('site.origins.map.caption'))->toContain('1918');
});

it('publishes a real map of the tribes rather than a drawn one', function (): void {
    // a map a cartographer made, captioned like any other source, in both languages
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.origins.map.caption'))->toContain('Bazewicz')
            ->and(trans('site.origins.map.credit'))->toContain('1918')
            ->and(trans('site.origins.map.alt'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/origins-tribesmap-{$size}.jpg"))->toBeReadableFile();
    }

    expect(file_get_contents(resource_path('js/pages/HistoryOrigins.vue')))->not->toContain('TribalMap');
});

it('publishes a real map of the Commonwealth on the faiths page', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.faiths.commonwealth.caption'))->toContain('1750')
            ->and(trans('site.faiths.commonwealth.credit'))->toContain('Homann');
    }

    app()->setLocale('en');

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/faiths-commonwealth-{$size}.jpg"))->toBeReadableFile();
    }
});

it('lets the reader open the period maps on the partition pages', function (): void {
    foreach (['HistoryFirstPartition', 'HistorySecondPartition', 'HistoryThirdPartition'] as $page) {
        expect(file_get_contents(resource_path("js/pages/{$page}.vue")))->toContain('as-link');
    }
});

it('loads the fonts with the subset that carries Polish diacritics', function (): void {
    $config = file_get_contents(base_path('vite.config.ts'));

    expect(substr_count($config, "'latin-ext'"))->toBe(2, 'every font family needs latin-ext');

    $stylesheet = glob(public_path('build/assets/fonts-*.css'));

    if ($stylesheet !== []) {
        expect(file_get_contents($stylesheet[0]))->toContain('U+0100-02BA');
    }
});

it('writes Polish copy with Polish letters only', function (): void {
    $polish = 'ąćęłńóśźżĄĆĘŁŃÓŚŹŻ';
    $allowed = $polish.'‘’“”„·';

    $walk = function (array $node, string $path) use (&$walk, $allowed): void {
        foreach ($node as $key => $value) {
            $here = $path === '' ? (string) $key : "{$path}.{$key}";

            if (is_array($value)) {
                $walk($value, $here);

                continue;
            }

            // a credit names the photographer the way they spell it, so a foreign
            // diacritic there is correct rather than a slip in the Polish
            if (str_ends_with($here, '.credit') || str_ends_with($here, '.photo_credit')) {
                continue;
            }

            // the roots page prints the Cyrillic a Russian-era record actually
            // carries, in every language of the site, so that one column is
            // evidence rather than Polish copy
            if (str_starts_with($here, 'roots.words.items.') && str_ends_with($here, '.ru')) {
                continue;
            }

            foreach (preg_split('//u', (string) $value, -1, PREG_SPLIT_NO_EMPTY) as $char) {
                if (mb_ord($char) > 127 && ! str_contains($allowed, $char)) {
                    throw new Exception("Non-Polish character {$char} in {$here}");
                }
            }
        }
    };

    $walk(require lang_path('pl/site.php'), '');

    expect(true)->toBeTrue();
});

it('keeps the copy free of generated-travel-prose vocabulary in every language', function (): void {
    $markers = [
        // English
        'delve', 'tapestry', 'testament to', 'stands as', 'rich history', 'nestled', 'vibrant',
        'bustling', 'hidden gem', 'boasts', 'in the heart of', 'whether you', 'moreover',
        'furthermore', 'it is worth noting', 'more than just', 'dive into', 'unravel',
        'journey through', 'timeless', 'breathtaking', 'iconic', 'must-see',
        // Polish
        'skarbnica', 'tętniąc', 'zapiera dech', 'nie sposób nie', 'warto podkreślić',
        'co więcej', 'podsumowując', 'prawdziwa uczta', 'magia tego miejsca',
    ];

    $found = [];

    foreach (glob(lang_path('*/site.php')) as $file) {
        $locale = basename(dirname($file));

        $walk = function (array $node, string $path) use (&$walk, $markers, $locale, &$found): void {
            foreach ($node as $key => $value) {
                $here = $path === '' ? (string) $key : "{$path}.{$key}";

                if (is_array($value)) {
                    $walk($value, $here);

                    continue;
                }

                foreach ($markers as $marker) {
                    if (mb_stripos((string) $value, $marker) !== false) {
                        $found[] = "{$locale}: {$here} contains {$marker}";
                    }
                }
            }
        };

        $walk(require $file, '');
    }

    expect($found)->toBe([]);
});

it('writes a real title tag into the served html of every page', function (): void {
    // the application has no SSR, so a title left inside the Inertia head
    // component never reaches a crawler: it has to be rendered by the layout
    $routes = collect(Route::getRoutes())
        ->filter(fn ($route): bool => in_array('GET', $route->methods(), true))
        ->map(fn ($route): string => $route->uri())
        ->reject(fn (string $uri): bool => str_contains($uri, '{') || str_contains($uri, 'sitemap'))
        ->unique();

    expect($routes)->not->toBeEmpty();

    foreach ($routes as $uri) {
        foreach (['en', 'pl'] as $locale) {
            $html = $this->get('/'.ltrim($uri, '/').'?lang='.$locale)->assertOk()->getContent();

            expect($html)->toMatch('#<title>.{5,}</title>#');
        }
    }
});

it('renders the article on the legend and the eagle', function (): void {
    $this->get('/history/lech-and-the-white-eagle')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryEagle')
            ->has('translations.eagle.frames', 7)
        );
});

it('credits every frame of the eagle article and ships its photographs', function (): void {
    foreach (trans('site.eagle.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/eagle-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }
});

it('separates the legend from the heraldry', function (): void {
    $frames = collect(trans('site.eagle.frames'))->keyBy('key');

    // the point of the article: the chronicle is late and the eagle is not from it
    expect($frames['chronicle']['body'])->toContain('1113')
        ->and($frames['legend']['body'])->toContain('1222')
        ->and($frames['seal']['body'])->toContain('1295')
        ->and($frames['seal']['body'])->toContain('no crown')
        // the crown is dated, in both directions
        ->and($frames['crown']['body'])->toContain('13 December 1927')
        ->and($frames['crown']['body'])->toContain('9 February 1990')
        ->and(trans('site.eagle.sources.body'))->toContain('Greater Poland Chronicle');
});

it('keeps every history article in the reading order', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    preg_match_all("#'(/history/[a-z0-9-]+)'#", file_get_contents(base_path('routes/web.php')), $routes);

    expect($routes[1])->not->toBeEmpty();

    foreach ($routes[1] as $route) {
        expect($order)->toContain($route);
    }

    // and the label the forward link uses exists in every language
    foreach (glob(lang_path('*/site.php')) as $file) {
        $copy = require $file;

        expect($copy['history']['next_topic'] ?? '')->not->toBeEmpty();
    }
});

it('renders the article on the baptism of 966', function (): void {
    $this->get('/history/baptism-of-966')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryBaptism')
            ->has('translations.baptism.frames', 9)
        );
});

it('credits every frame of the baptism article and ships its photographs', function (): void {
    foreach (trans('site.baptism.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/baptism-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }
});

it('says how little the sources record about the baptism', function (): void {
    $frames = collect(trans('site.baptism.frames'))->keyBy('key');

    // one annal line, the older annal a year off, no place and no day
    expect($frames['baptism']['body'])->toContain('Mesco dux Poloniae baptizatur')
        ->and($frames['baptism']['body'])->toContain('a year late')
        ->and($frames['baptism']['body'])->toContain('No source says where')
        ->and($frames['baptism']['body'])->toContain('modern guess')
        ->and(trans('site.baptism.sources.body'))->toContain('967')
        // the motive is argued, not asserted, here and on the first article
        ->and($frames['why']['body'])->toContain('No source gives')
        ->and($frames['why']['body'])->toContain('older reading')
        ->and(collect(trans('site.origins.frames'))->keyBy('key')['mieszko']['body'])->not->toContain('pretext')
        // the basins are argued over, and the graves are only probable
        ->and($frames['poznan']['body'])->toContain('mixing mortar')
        ->and($frames['poznan']['body'])->toContain('probably');
});

it('introduces everyone in the baptism article before it needs them', function (): void {
    $frames = collect(trans('site.baptism.frames'));
    $keys = $frames->pluck('key')->all();
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($firstMention('Veleti'))->toBeLessThan(array_search('why', $keys, true))
        ->and($firstMention('Wichmann'))->toBeLessThan(array_search('why', $keys, true))
        ->and($firstMention('Thietmar'))->toBeLessThan(array_search('dobrawa', $keys, true))
        ->and($firstMention('Dobrawa'))->toBeLessThan(array_search('baptism', $keys, true))
        ->and($firstMention('Boleslaw'))->toBeLessThan(array_search('slowly', $keys, true));
});

it('dates the Millennium and the holiday correctly in both languages', function (): void {
    $frames = collect(trans('site.baptism.frames'))->keyBy('key');

    expect($frames['millennium']['body'])->toContain('Stefan Wyszynski')
        ->and($frames['millennium']['body'])->toContain('Paul VI')
        ->and($frames['today']['body'])->toContain('2019')
        ->and($frames['today']['body'])->toContain('not a day off');

    app()->setLocale('pl');
    $polish = collect(trans('site.baptism.frames'))->keyBy('key');

    // the letter of 1965 is quoted in its own words, not the popular paraphrase
    expect($polish['millennium']['body'])->toContain('udzielamy wybaczenia i prosimy o nie')
        ->and($polish['today']['body'])->toContain('Święto Chrztu Polski')
        ->and(trans('site.baptism.map.labels.bohemia'))->toBe('Czechy');
});

it('keeps the verified baptism facts in both languages', function (): void {
    $frames = collect(trans('site.baptism.frames'))->keyBy('key');

    // there was no bishopric in Prague until the 970s, so the baptism came through Bohemia, not from Prague
    expect($frames['why']['body'])->toContain('through Bohemia')
        ->and($frames['why']['body'])->not->toContain('Prague')
        ->and(trans('site.baptism.map.labels.latin'))->not->toContain('Prague')
        // the Poznan chapel is about 2.2 m wide
        ->and($frames['poznan']['body'])->toContain('just over two metres')
        // the collapse of the 1030s has its actors and Casimir comes back from exile
        ->and($frames['slowly']['body'])->toContain('Bretislav')
        ->and($frames['slowly']['body'])->toContain('1039')
        // the rebel's king and the emperor are the same Otto I
        ->and($frames['squeeze']['body'])->toContain('his own king, Otto I')
        ->and($frames['pagan']['body'])->toContain('Piasts')
        ->and(collect(trans('site.origins.frames'))->keyBy('key')['mieszko']['body'])->toContain('usual reading');

    app()->setLocale('pl');
    $polish = collect(trans('site.baptism.frames'))->keyBy('key');

    expect($polish['why']['body'])->toContain('za pośrednictwem Czech')
        ->and(trans('site.baptism.map.labels.latin'))->toContain('przez Czechy')
        ->and($polish['poznan']['body'])->toContain('niewiele ponad dwa metry')
        ->and($polish['slowly']['body'])->toContain('Brzetysław')
        ->and($polish['squeeze']['body'])->toContain('Otton I');
});

it('renders the article on Gniezno as a filmstrip', function (): void {
    $this->get('/history/gniezno-the-first-capital')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryGniezno')
            ->has('translations.gniezno.frames', 9)
        );
});

it('credits every frame of the Gniezno article and ships its photographs', function (): void {
    foreach (trans('site.gniezno.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/gniezno-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }
});

it('does not promise Gniezno was a capital in the modern sense', function (): void {
    $frames = collect(trans('site.gniezno.frames'))->keyBy('key');

    // several seats, and Poznan's claim is on the page
    expect($frames['seats']['body'])->toContain('several principal seats')
        ->and($frames['seats']['body'])->toContain('Poznan')
        ->and(trans('site.gniezno.sources.body'))->toContain('several seats');
});

it('keeps the hard-won Gniezno facts dated the way the sources date them', function (): void {
    $frames = collect(trans('site.gniezno.frames'))->keyBy('key');

    expect($frames['name']['body'])->toContain('civitas Schinesghe')
        ->and($frames['name']['caption'])->toContain('GNEZDVN CIVITAS')
        // Polish and Czech chronicles disagree on the year of the raid
        ->and($frames['raid']['year'])->toBe('1038 or 1039')
        // the first coronation certainly held in Gniezno is Mieszko II's, not Boleslaw's
        ->and($frames['grave']['body'])->toContain('Mieszko II')
        ->and($frames['krakow']['body'])->toContain('1300')
        ->and($frames['krakow']['body'])->toContain('1320')
        ->and($frames['primate']['body'])->toContain('May 1573')
        // the cathedral was set alight after the Germans had gone, not in a battle
        ->and($frames['fire']['body'])->toContain('23 January 1945')
        ->and($frames['fire']['body'])->toContain('already left');
});

it('bridges every ruler and relic the Gniezno article relies on', function (): void {
    $frames = collect(trans('site.gniezno.frames'))->keyBy('key');

    // Mieszko dies on the page before his son strikes the coin
    expect($frames['name']['body'])->toContain('Mieszko died in 992')
        // the move to Krakow did not take the coronation right from Gniezno
        ->and($frames['krakow']['body'])->toContain('archbishop of Gniezno')
        ->and($frames['krakow']['body'])->toContain('1138')
        // the head relic is Gniezno's claim, not an established fact
        ->and($frames['fire']['body'])->toContain('1127')
        ->and($frames['fire']['body'])->toContain('said')
        ->and($frames['primate']['body'])->toContain('last male');
});

it('renders the search and sharing tags server side', function (): void {
    $response = $this->get('/history/lech-and-the-white-eagle?lang=pl');

    $response->assertOk()
        // a link scraper does not run JavaScript, so these must be in the HTML
        ->assertSee('<link rel="canonical" href="'.url('/history/lech-and-the-white-eagle?lang=pl').'"', false)
        ->assertSee('hreflang="en"', false)
        ->assertSee('hreflang="pl"', false)
        ->assertSee('hreflang="x-default"', false)
        ->assertSee('property="og:title" content="'.trans('site.eagle.meta.title').'"', false)
        ->assertSee('property="og:site_name" content="Know Poland"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false);
});

it('lists every page in the sitemap, in both languages', function (): void {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $body = $response->getContent();

    foreach (['/', '/history', '/history/how-poland-began', '/history/second-world-war'] as $path) {
        expect($body)->toContain('<loc>'.e(url($path)).'</loc>')
            ->and($body)->toContain('<loc>'.e(url($path).'?lang=pl').'</loc>');
    }

    // internal and parameterised routes stay out of it
    expect($body)->not->toContain('_inertia')
        ->and($body)->not->toContain('{')
        ->and($body)->not->toContain('sitemap.xml</loc>');
});

it('names the application after the brand, not the framework', function (): void {
    expect(config('app.name'))->toBe('Know Poland')
        ->and(trans('site.brand'))->toBe('Know Poland');

    expect(file_get_contents(base_path('.env.example')))->toContain('APP_URL=https://knowpoland.com');
    expect(file_get_contents(public_path('robots.txt')))->toContain('Sitemap: https://knowpoland.com/sitemap.xml');
});

it('renders the article on Grunwald as a filmstrip', function (): void {
    $this->get('/history/grunwald-1410')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryGrunwald')
            ->has('translations.grunwald.frames', 9)
        );
});

it('credits every frame of the Grunwald article and ships its photographs', function (): void {
    foreach (trans('site.grunwald.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/grunwald-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/grunwald-hero-{$size}.jpg"))->toBeReadableFile();
    }
});

it('places Grunwald in the Commonwealth era and in the reading order', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/grunwald-1410'))
        ->toBeGreaterThan(strpos($order, '/history/gniezno-the-first-capital'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'));

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker names the era the way the era band does
        expect(trans('site.grunwald.hero.kicker'))->toContain(trans('site.history.eras.commonwealth.label'))
            ->and(trans('site.grunwald.hero.kicker'))->toContain('01');
    }

    app()->setLocale('en');
});

it('bridges the Grunwald article from the Piast kingdom to the battle', function (): void {
    $frames = collect(trans('site.grunwald.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // who the knights were and who let them in
    expect($byKey['order']['body'])->toContain('duke of Masovia')
        ->and($byKey['order']['body'])->toContain('1226')
        ->and($byKey['order']['body'])->toContain('Casimir the Great')
        // Casimir dies on the page before his crown passes on
        ->and($byKey['krewo']['body'])->toContain('died in 1370')
        ->and($byKey['krewo']['body'])->toContain('Jadwiga')
        ->and($byKey['krewo']['body'])->toContain('Krewo')
        // the pagan grand duke becomes the king the rest of the page calls Jagiello
        ->and($byKey['vytautas']['body'])->toContain('Wladyslaw Jagiello')
        ->and($byKey['vytautas']['body'])->toContain('1392')
        // the cousin's change of side and the war on Poland both get a reason
        ->and($byKey['vytautas']['body'])->toContain('to free his hands')
        ->and($byKey['vytautas']['body'])->toContain('stand by Lithuania')
        // Casimir's kingdom was reunited by someone, and Dobrzyn is placed before it is fought over
        ->and($byKey['order']['body'])->toContain('his father had reunited')
        ->and($byKey['order']['body'])->toContain('Dobrzyn')
        // Plauen becomes Grand Master on the page before the chapel he builds
        ->and($byKey['peace']['body'])->toContain('made Plauen its Grand Master');

    // everyone is on stage before the frame that needs them
    expect($firstMention('Vytautas'))->toBeLessThan(array_search('march', $keys, true))
        ->and($firstMention('Ulrich von Jungingen'))->toBeLessThan(array_search('battle', $keys, true))
        ->and($firstMention('Samogitia'))->toBeLessThan(array_search('peace', $keys, true))
        ->and($firstMention('Stebark'))->toBeLessThan(array_search('memory', $keys, true))
        ->and($firstMention('Heinrich von Plauen'))->toBe(array_search('peace', $keys, true));
});

it('keeps the hard-won Grunwald facts hedged the way the sources hedge them', function (): void {
    $frames = collect(trans('site.grunwald.frames'))->keyBy('key');

    // nobody counted the armies, and the Lithuanian retreat is still argued
    expect($frames['march']['body'])->toContain('estimates')
        // in 1409 Wenceslaus was king of Bohemia only; Rupert held the German crown
        ->and($frames['march']['body'])->toContain('Wenceslaus IV, king of Bohemia,')
        ->and($frames['march']['body'])->not->toContain('of Germany')
        ->and($frames['battle']['body'])->toContain('historians still argue')
        ->and($frames['battle']['body'])->toContain('whose father fought')
        // what the first peace gave, and what it did not
        ->and($frames['peace']['body'])->toContain('1 February 1411')
        ->and($frames['peace']['body'])->toContain('lifetimes')
        ->and($frames['peace']['body'])->toContain('100,000 kopas')
        ->and($frames['peace']['body'])->toContain('kept its state')
        // what it actually settled came later
        ->and($frames['thirteen']['body'])->toContain('1454')
        ->and($frames['thirteen']['body'])->toContain('1466')
        ->and($frames['thirteen']['body'])->toContain('1525')
        // the painting is a depiction, and the 1914 name was a deliberate answer
        ->and($frames['memory']['caption'])->toContain('imagined')
        ->and($frames['memory']['body'])->toContain('Tannenberg')
        ->and($frames['memory']['body'])->toContain('revenge')
        ->and(trans('site.grunwald.map.caption'))->toContain('Schematic')
        ->and(trans('site.grunwald.sources.body'))->toContain('Cronica conflictus');
});

it('writes the Grunwald names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.grunwald.frames'))->keyBy('key');

    expect($frames['vytautas']['body'])->toContain('Witold')
        ->and($frames['vytautas']['body'])->toContain('Żmudź')
        ->and($frames['battle']['body'])->toContain('Stębark')
        ->and($frames['peace']['body'])->toContain('kop groszy praskich')
        ->and(trans('site.grunwald.map.labels.samogitia'))->toBe('Żmudź');

    app()->setLocale('en');
});

it('renders the article on Casimir the Great as a filmstrip', function (): void {
    $this->get('/history/casimir-the-great')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryCasimir')
            ->has('translations.casimir.frames', 9)
        );
});

it('credits every frame of the Casimir article and ships its photographs', function (): void {
    foreach (trans('site.casimir.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/casimir-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }

    expect(public_path('images/casimir-hero-sm.jpg'))->toBeReadableFile();
});

it('keeps the hard-won Casimir facts the way the sources give them', function (): void {
    $frames = collect(trans('site.casimir.frames'))->keyBy('key');

    // his father is on the page before him, with the first Wawel coronation
    expect($frames['father']['body'])->toContain('Lokietek')
        ->and($frames['father']['body'])->toContain('20 January 1320')
        // born 30 April 1310, so 22 at the coronation, not 23
        ->and($frames['peace']['body'])->toContain('aged 22')
        ->and($frames['peace']['body'])->toContain('20,000')
        // the saying is Dlugosz's, a century later, and the castle count is a range
        ->and($frames['brick']['body'])->toContain('Dlugosz')
        ->and($frames['brick']['body'])->toContain('thirty to sixty')
        // the statutes are not given a single year
        ->and($frames['law']['body'])->toContain('1356 and 1362')
        // the plague question stays open
        ->and($frames['law']['body'])->toContain('appears to have escaped')
        ->and($frames['university']['body'])->toContain('12 May 1364')
        ->and($frames['university']['body'])->toContain('may be a story')
        // Esterka is a legend and the charter predates her
        ->and($frames['jews']['body'])->toContain('No document of the time mentions her')
        // Louis was his nephew, not his brother-in-law
        ->and($frames['end']['body'])->toContain('his nephew, Louis')
        ->and($frames['end']['body'])->toContain('5 November');
});

it('labels the Casimir map in both languages', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        expect($copy['casimir']['map']['labels'])->toHaveKeys(['kingdom', 'gained', 'silesia', 'order', 'krakow', 'lviv'])
            ->and($copy['casimir']['map']['caption'])->not->toBeEmpty();
    }
});

it('gives every page its own title and description in the server-rendered head', function (): void {
    // the head is built from the registry rather than from the template, so a
    // page that is missing from it would fall back to the home page's copy
    $registry = PageSeo::pages();

    preg_match_all("#Route::inertia\('[^']+', '([A-Za-z]+)'\)#", file_get_contents(base_path('routes/web.php')), $components);

    expect($components[1])->not->toBeEmpty();

    foreach ($components[1] as $component) {
        expect($registry)->toHaveKey($component);
    }
});

it('renders the article on the golden age as a filmstrip', function (): void {
    $this->get('/history/the-golden-age')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryGoldenAge')
            ->has('translations.golden.frames', 9)
        );
});

it('credits every frame of the golden age article and ships its photographs', function (): void {
    foreach (trans('site.golden.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/golden-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/golden-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title photograph is CC BY-SA and has no caption of its own
    expect(trans('site.golden.sources.body'))->toContain('Zygmunt Put');
});

it('places the golden age right after Grunwald in the Commonwealth era', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/the-golden-age'))
        ->toBeGreaterThan(strpos($order, '/history/grunwald-1410'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'));

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.golden.hero.kicker'))->toContain(trans('site.history.eras.commonwealth.label'))
            ->and(trans('site.golden.hero.kicker'))->toContain('02');
    }

    app()->setLocale('en');
});

it('keeps every golden age frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['golden']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the golden age from Grunwald to the union with Lithuania', function (): void {
    $frames = collect(trans('site.golden.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the page opens where Grunwald closed, and Sigismund is brought on stage
    expect($byKey['nobles']['body'])->toContain('1466')
        ->and($byKey['nobles']['body'])->toContain('Casimir IV')
        // Sigismund was born before his brother Frederick, so not the youngest son
        ->and($byKey['nobles']['body'])->toContain('younger brother, Sigismund')
        ->and($byKey['nobles']['body'])->not->toContain('youngest')
        ->and($byKey['nobles']['body'])->toContain('Nihil novi')
        // Torun is placed under the crown before Copernicus is born there
        ->and($byKey['copernicus']['body'])->toContain('come under the Polish crown')
        // the wealth has a cost, and the page says where that story continues
        ->and($byKey['serfs']['body'])->toContain('serfdom')
        // the last frame of history hands over to the next topic
        ->and($byKey['augustus']['body'])->toContain('1569')
        ->and($byKey['augustus']['body'])->toContain('next topic')
        ->and($byKey['augustus']['body'])->toContain('1572');

    // everyone is on stage before the frame that needs them
    expect($firstMention('Berrecci'))->toBe(array_search('wawel', $keys, true))
        ->and($firstMention('Bona'))->toBeLessThan(array_search('augustus', $keys, true))
        ->and($firstMention('Sigismund Augustus'))->toBeLessThan(array_search('print', $keys, true))
        ->and($firstMention('Sejm'))->toBe(array_search('nobles', $keys, true));
});

it('keeps the hard-won golden age facts the way the sources give them', function (): void {
    $frames = collect(trans('site.golden.frames'))->keyBy('key');

    // the soup-vegetable story is labelled as a story
    expect($frames['wawel']['body'])->toContain('story people tell')
        // the cathedral gives the bell's weight without the yoke
        ->and($frames['chapel']['body'])->toContain('9,650')
        ->and($frames['copernicus']['body'])->toContain('1543')
        ->and($frames['copernicus']['body'])->toContain('Olsztyn')
        // grain: the customs count, the record year, and the myth cut down to size
        ->and($frames['grain']['body'])->toContain('1,752')
        ->and($frames['grain']['body'])->toContain('1618')
        ->and($frames['grain']['body'])->toContain('estimate')
        ->and($frames['grain']['body'])->toContain('one per cent')
        // the two laws that tied the peasants down
        ->and($frames['serfs']['body'])->toContain('1496')
        ->and($frames['serfs']['body'])->toContain('one day a week')
        // the tapestries went to the sisters first
        ->and($frames['augustus']['body'])->toContain('his sisters')
        // Gdansk's merchants held the monopoly, the Dutch bought from them
        ->and($frames['grain']['body'])->toContain('Only Gdansk merchants')
        ->and($frames['grain']['body'])->toContain('spring and autumn')
        // his sister Anna reigned after him, so he ends only the male line
        ->and($frames['augustus']['body'])->toContain('last male Jagiellon')
        // the first Polish book survives only as fragments
        ->and($frames['print']['body'])->toContain('only in fragments')
        ->and($frames['chapel']['body'])->toContain('his workshop')
        ->and($frames['today']['body'])->toContain('1961')
        ->and(trans('site.golden.map.caption'))->toContain('Schematic')
        // the era band no longer claims Polish grain fed half of Europe
        ->and(trans('site.history.eras.commonwealth.topics.1.note'))->not->toContain('half of Europe');
});

it('writes the golden age names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.golden.frames'))->keyBy('key');

    expect($frames['chapel']['body'])->toContain('Zygmunt August')
        ->and($frames['serfs']['body'])->toContain('pańszczyzn')
        ->and($frames['print']['body'])->toContain('Polacy nie gęsi')
        ->and($frames['grain']['body'])->toContain('łaszt')
        ->and(trans('site.golden.map.labels.gdansk'))->toBe('Gdańsk')
        ->and(trans('site.history.eras.commonwealth.topics.1.note'))->not->toContain('pół Europy');

    app()->setLocale('en');
});

it('does not claim Lviv was Polish without a break until 1939', function (): void {
    $frames = collect(trans('site.casimir.frames'))->keyBy('key');

    // Lviv was Austrian from 1772 to 1918
    expect($frames['east']['body'])->not->toContain('Polish until 1939')
        ->and($frames['east']['body'])->toContain('between the world wars')
        ->and($frames['jews']['body'])->toContain('more than twenty years');
});

it('renders the page on where to see the Middle Ages as a filmstrip', function (): void {
    $this->get('/history/where-to-stand-in-it')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryWhere')
            ->has('translations.where.frames', 9)
        );
});

it('credits every frame of the where-to-stand page and ships its photographs', function (): void {
    foreach (trans('site.where.frames') as $frame) {
        expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
            ->and($frame['credit'])->not->toBeEmpty();

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/where-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }
});

it('tells the visitor what is original and what was rebuilt', function (): void {
    $frames = collect(trans('site.where.frames'))->keyBy('key');

    // Poznan cathedral is a post-war rebuild and the graves are only probable
    expect($frames['poznan']['body'])->toContain('1946-1956')
        ->and($frames['poznan']['body'])->toContain('most probably')
        // the Lednica bridges are dated by tree rings and the island is seasonal
        ->and($frames['lednica']['body'])->toContain('963-964')
        ->and($frames['lednica']['body'])->toContain('only in season')
        // Popiel is a legend, the tower is Casimir's
        ->and($frames['kruszwica']['body'])->toContain('legend')
        ->and($frames['kruszwica']['body'])->toContain('1350')
        // the Wieliczka tourist route is not medieval
        ->and($frames['salt']['body'])->toContain('17th century')
        // Bedzin and Pieskowa Skala are not what Casimir left
        ->and($frames['castles']['body'])->toContain('1952-1956')
        ->and($frames['castles']['body'])->toContain('1542-1580')
        // the Plock door is a copy and the remains are traditional
        ->and($frames['further']['body'])->toContain('copy')
        ->and($frames['further']['body'])->toContain('by tradition')
        // and Bedzin is no longer sold as an original on the Casimir page
        ->and(collect(trans('site.casimir.frames'))->keyBy('key')['brick']['body'])->toContain('reconstruction');
});

it('renders the article on one state and many faiths as a filmstrip', function (): void {
    $this->get('/history/one-state-many-faiths')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryFaiths')
            ->has('translations.faiths.frames', 9)
        );
});

it('credits every frame of the faiths article and ships its photographs', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['faiths']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/faiths-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }
        }
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/faiths-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.faiths.sources.body'))->toContain('Grodecki')
        ->and(trans('site.faiths.sources.body'))->toContain('1570');
});

it('places the faiths article right after the golden age in the Commonwealth era', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/one-state-many-faiths'))
        ->toBeGreaterThan(strpos($order, '/history/the-golden-age'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/one-state-many-faiths',\s+titleKey: 'faiths.meta.title',\s+era: 'commonwealth',\s+index: 2,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.faiths.hero.kicker'))->toContain(trans('site.history.eras.commonwealth.label'))
            ->and(trans('site.faiths.hero.kicker'))->toContain('03')
            ->and(trans('site.faiths.meta.title'))->toContain(trans('site.history.eras.commonwealth.topics.2.title'));
    }

    app()->setLocale('en');
});

it('keeps every faiths frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['faiths']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the faiths article from the golden age to the elected kings', function (): void {
    $frames = collect(trans('site.faiths.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the page opens where the golden age closed: a union of two states and a king with no son
    expect($byKey['lublin']['body'])->toContain('Krewo')
        ->and($byKey['lublin']['body'])->toContain('no son')
        ->and($byKey['lublin']['body'])->toContain('Polotsk')
        // the death is on the page before the interregnum it causes
        ->and($byKey['confederation']['body'])->toContain('7 July 1572')
        // the kings after Henry are on stage before the frames that need them
        ->and($byKey['henry']['body'])->toContain('Stefan Batory')
        ->and($byKey['henry']['body'])->toContain('Sigismund III');

    expect($firstMention('Sigismund III'))->toBe(array_search('henry', $keys, true))
        ->and($firstMention('Ostrogski'))->toBe(array_search('brest', $keys, true))
        ->and($firstMention('Isserles'))->toBeLessThan(array_search('today', $keys, true))
        ->and($firstMention('Zamoyski'))->toBeLessThan(array_search('today', $keys, true))
        ->and($firstMention('Cossack'))->toBe(array_search('jews', $keys, true))
        ->and($firstMention('Vytautas'))->toBe(array_search('neighbours', $keys, true));
});

it('keeps the hard-won faiths facts the way the sources give them', function (): void {
    $frames = collect(trans('site.faiths.frames'))->keyBy('key');

    // the union: dates of the walkout and the oath, and what moved to the Crown
    expect($frames['lublin']['body'])->toContain('1 March')
        ->and($frames['lublin']['body'])->toContain('Volhynia')
        ->and($frames['lublin']['body'])->toContain('over a third of Lithuania')
        ->and($frames['union']['body'])->toContain('1 July 1569')
        ->and($frames['union']['body'])->toContain('Warsaw')
        ->and($frames['union']['body'])->toContain('own offices, treasury, army and law')
        // the confederation protected nobles, not their subjects' choices
        ->and($frames['confederation']['body'])->toContain('28 January 1573')
        ->and($frames['confederation']['body'])->toContain('dissidentes de religione')
        ->and($frames['confederation']['body'])->toContain('power over his subjects')
        ->and($frames['confederation']['body'])->toContain('Krasinski')
        // the Henrician Articles and the flight
        ->and($frames['henry']['body'])->toContain('refuse to obey')
        ->and($frames['henry']['body'])->toContain('February 1574')
        // no paradise myth without the caveat the scholarship attaches
        ->and($frames['jews']['body'])->toContain('hostile lampoon of 1606')
        ->and($frames['jews']['body'])->toContain('It was not')
        ->and($frames['jews']['body'])->toContain('1648')
        ->and($frames['jews']['body'])->toContain('royal privileges to keep Jews out')
        ->and($frames['jews']['body'])->toContain('Council of Four Lands')
        ->and($frames['jews']['body'])->toContain('Volhynia')
        ->and($frames['jews']['body'])->toContain('1764')
        // the interrex is explained, not just named
        ->and($frames['confederation']['body'])->toContain('stood in as interrex')
        // Brest founded the Greek Catholic Church and the Orthodox waited until 1632
        ->and($frames['brest']['body'])->toContain('Greek Catholic')
        ->and($frames['brest']['body'])->toContain('now in Belarus')
        ->and($frames['brest']['body'])->toContain('1620')
        ->and($frames['brest']['body'])->toContain('1632')
        ->and($frames['brethren']['body'])->toContain('1658')
        // Karaite arrival is their tradition, and the mosque is later than the era
        ->and($frames['neighbours']['body'])->toContain('say he also brought them')
        ->and($frames['neighbours']['body'])->toContain('Lviv is in Ukraine today')
        ->and($frames['neighbours']['caption'])->toContain('probably')
        ->and($frames['lublin']['caption'])->toContain('1869')
        ->and(trans('site.faiths.map.caption'))->toContain('Schematic');
});

it('writes the faiths names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.faiths.frames'))->keyBy('key');

    expect($frames['lublin']['body'])->toContain('Zygmunt August')
        ->and($frames['lublin']['body'])->toContain('Połock')
        ->and($frames['lublin']['body'])->toContain('Kijowszczyznę')
        ->and($frames['confederation']['body'])->toContain('Uchański')
        ->and($frames['henry']['body'])->toContain('Henryka Walezego')
        ->and($frames['henry']['body'])->toContain('artykuły henrykowskie')
        ->and($frames['jews']['body'])->toContain('Sejm Czterech Ziem')
        ->and($frames['brest']['body'])->toContain('greckokatolick')
        ->and($frames['neighbours']['body'])->toContain('Troki')
        ->and($frames['neighbours']['body'])->toContain('wierzą, że to on')
        ->and($frames['jews']['body'])->toContain('Nie była nim')
        ->and(trans('site.faiths.map.labels.vilnius'))->toBe('Wilno')
        ->and(trans('site.faiths.map.labels.kyiv'))->toBe('Kijów');

    app()->setLocale('en');
});

it('labels the faiths map in both languages', function (): void {
    $map = file_get_contents(resource_path('js/components/FaithsMap.vue'));

    preg_match_all("#key: '([a-z]+)'#", $map, $keys);

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach (['sea', 'crown', 'lithuania', 'moved', 'krakow', 'warsaw', 'lublin', 'gdansk', 'vilnius', 'kyiv', 'polotsk'] as $label) {
            expect($copy['faiths']['map']['labels'][$label] ?? '')->not->toBeEmpty();
        }

        expect($copy['faiths']['map']['alt'])->not->toBeEmpty();
    }

    expect($keys[1])->toContain('podlasie')->toContain('volhynia')->toContain('kyiv')->toContain('braclaw');
});

it('renders the article on Vienna 1683 as a filmstrip', function (): void {
    $this->get('/history/vienna-1683')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryVienna')
            ->has('translations.vienna.frames', 9)
        );
});

it('credits every frame of the Vienna article and ships its photographs', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['vienna']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/vienna-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }
        }
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/vienna-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.vienna.sources.body'))->toContain('Alten-Allen')
        ->and(trans('site.vienna.sources.body'))->toContain('1686');
});

it('places the Vienna article right after the faiths article in the Commonwealth era', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/vienna-1683'))
        ->toBeGreaterThan(strpos($order, '/history/one-state-many-faiths'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/vienna-1683',\s+titleKey: 'vienna.meta.title',\s+era: 'commonwealth',\s+index: 3,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.vienna.hero.kicker'))->toContain(trans('site.history.eras.commonwealth.label'))
            ->and(trans('site.vienna.hero.kicker'))->toContain('04')
            ->and(trans('site.vienna.meta.title'))->toContain(trans('site.history.eras.commonwealth.topics.3.title'));
    }

    app()->setLocale('en');
});

it('keeps every Vienna frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['vienna']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Vienna article from the faiths article to the relief', function (): void {
    $frames = collect(trans('site.vienna.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the page opens where the faiths article left off: the Cossacks of 1648 and the Swedes of 1655
    expect($byKey['deluge']['body'])->toContain('1648')
        ->and($byKey['deluge']['body'])->toContain('1655')
        // the king of the Deluge is tied to the Sigismund III the reader already met
        ->and($byKey['deluge']['body'])->toContain('son of Sigismund III')
        // the king before Sobieski dies on the page, and Sobieski is elected on it
        ->and($byKey['khotyn']['body'])->toContain('died the day before')
        ->and($byKey['khotyn']['body'])->toContain('May 1674')
        // the Ottoman Empire is explained before it besieges anything
        ->and($byKey['khotyn']['body'])->toContain('Turkish state')
        // a bridge to the Sejm and its veto, which the next topics cover
        ->and($byKey['after']['body'])->toContain('one deputy could stop a session');

    expect($firstMention('Sobieski'))->toBe(array_search('deluge', $keys, true))
        ->and($firstMention('Kamianets'))->toBe(array_search('khotyn', $keys, true))
        ->and($firstMention('Leopold'))->toBe(array_search('vizier', $keys, true))
        ->and($firstMention('Kara Mustafa'))->toBe(array_search('vizier', $keys, true))
        ->and($firstMention('Marysienka'))->toBe(array_search('march', $keys, true))
        ->and($firstMention('Charles of Lorraine'))->toBe(array_search('march', $keys, true))
        ->and($firstMention('Kahlenberg'))->toBe(array_search('march', $keys, true));
});

it('keeps the hard-won Vienna facts the way the sources give them', function (): void {
    $frames = collect(trans('site.vienna.frames'))->keyBy('key');

    // Sobieski himself went over to the Swedes before he came back
    expect($frames['deluge']['body'])->toContain('swore loyalty to him, a young officer named Jan Sobieski')
        ->and($frames['khotyn']['body'])->toContain('11 November 1673')
        ->and($frames['khotyn']['body'])->toContain('grand hetman, commander')
        // the treaty: its date, why it was dated early, and the mutual clause
        ->and($frames['vizier']['body'])->toContain('31 March 1683')
        ->and($frames['vizier']['body'])->toContain('reportedly dated a day early')
        ->and($frames['vizier']['body'])->toContain('Krakow or Vienna')
        // the siege: dates from the City of Vienna, sizes given as estimates
        ->and($frames['siege']['body'])->toContain('14 July 1683')
        ->and($frames['siege']['body'])->toContain('Estimates start at about 90,000')
        ->and($frames['siege']['body'])->toContain('Starhemberg')
        ->and($frames['march']['body'])->toContain('15 August 1683')
        ->and($frames['march']['body'])->toContain('Tarnowskie Gory')
        ->and($frames['march']['body'])->toContain('65,000 to 70,000')
        ->and($frames['march']['body'])->toContain('Tulln')
        // the Poles were not alone on the field, and the left fought first
        ->and($frames['charge']['body'])->toContain('fought all morning')
        ->and($frames['charge']['body'])->toContain('Stanislaw Potocki')
        // the letters, quoted from the letter itself
        ->and($frames['letter']['body'])->toContain('barely on one horse and in one robe')
        ->and($frames['letter']['body'])->toContain('Venimus, vidimus, Deus vicit')
        ->and($frames['letter']['body'])->toContain('Talenti')
        ->and($frames['letter']['body'])->toContain('25 December')
        // what it brought, and the rescue of Christendom as a celebration, not a finding
        ->and($frames['after']['body'])->toContain('hailed the victory as the rescue of Christendom')
        ->and($frames['after']['body'])->toContain('only what it had lost in 1672')
        ->and($frames['deluge']['body'])->toContain('Andrusovo')
        ->and($frames['march']['body'])->toContain('just across the border in Silesia')
        ->and($frames['today']['body'])->toContain('any European museum')
        ->and($frames['after']['body'])->toContain('stayed a great power')
        ->and($frames['after']['body'])->toContain('17 June 1696')
        // the name trap on the hill, and the tents are tied to Vienna by tradition
        ->and($frames['today']['body'])->toContain('Leopoldsberg')
        ->and($frames['today']['body'])->toContain('1906')
        ->and($frames['today']['body'])->toContain('Tradition ties some of them')
        ->and($frames['vizier']['caption'])->toContain('after his death')
        ->and(trans('site.vienna.map.caption'))->toContain('Schematic');

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['vienna']['frames'] as $frame) {
            expect(mb_strtolower($frame['body']))->not->toContain('saved europe')
                ->not->toContain('saved christian')
                ->not->toContain('uratował europę')
                ->not->toContain('ocalił europę');
        }
    }
});

it('writes the Vienna names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.vienna.frames'))->keyBy('key');

    expect($frames['deluge']['body'])->toContain('Chmielnickiego')
        ->and($frames['deluge']['body'])->toContain('Jasnej Górze')
        ->and($frames['khotyn']['body'])->toContain('Michała Korybuta Wiśniowieckiego')
        ->and($frames['khotyn']['body'])->toContain('Kamieniec Podolski')
        ->and($frames['khotyn']['body'])->toContain('Chocimiem')
        ->and($frames['march']['body'])->toContain('Tarnowskich Górach')
        ->and($frames['march']['body'])->toContain('Karola Lotaryńskiego')
        ->and($frames['letter']['body'])->toContain('namiotów')
        ->and($frames['letter']['body'])->toContain('ledwo na jednym koniu i w jednej sukni')
        ->and($frames['after']['body'])->toContain('Karłowicach')
        ->and($frames['today']['body'])->toContain('Leopoldsbergiem')
        ->and(trans('site.vienna.map.labels.vienna'))->toBe('Wiedeń')
        ->and(trans('site.vienna.map.labels.tarnowskie'))->toBe('Tarnowskie Góry');

    app()->setLocale('en');
});

it('labels the Vienna map in both languages', function (): void {
    $map = file_get_contents(resource_path('js/components/ViennaMap.vue'));

    preg_match_all("#key: '([a-z]+)'#", $map, $keys);

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach (['route', ...$keys[1]] as $label) {
            expect($copy['vienna']['map']['labels'][$label] ?? '')->not->toBeEmpty();
        }

        expect($copy['vienna']['map']['alt'])->not->toBeEmpty();
    }

    expect($keys[1])->toContain('krakow')->toContain('tarnowskie')->toContain('tulln')->toContain('kahlenberg')->toContain('vienna');
});

it('renders the article on serfdom as a filmstrip', function (): void {
    $this->get('/history/serfdom')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistorySerfdom')
            ->has('translations.serfdom.frames', 9)
        );
});

it('credits every frame of the serfdom article and ships its photographs', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['serfdom']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/serfdom-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }
        }
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/serfdom-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.serfdom.sources.body'))->toContain('Korczyna')
        ->and(trans('site.serfdom.sources.body'))->toContain('Lucekbb');
});

it('places the serfdom article right after the Vienna article in the Commonwealth era', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/serfdom'))
        ->toBeGreaterThan(strpos($order, '/history/vienna-1683'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/serfdom',\s+titleKey: 'serfdom.meta.title',\s+era: 'commonwealth',\s+index: 4,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.serfdom.hero.kicker'))->toContain(trans('site.history.eras.commonwealth.label'))
            ->and(trans('site.serfdom.hero.kicker'))->toContain('05')
            ->and(trans('site.serfdom.meta.title'))->toContain(trans('site.history.eras.commonwealth.topics.4.title'));
    }

    app()->setLocale('en');
});

it('keeps every serfdom frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['serfdom']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the serfdom article from the golden age to the partitions', function (): void {
    $frames = collect(trans('site.serfdom.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the page opens on the grain trade and the laws the golden age page already told
    expect($byKey['folwark']['body'])->toContain('Gdansk')
        ->and($byKey['folwark']['body'])->toContain('1496')
        ->and($byKey['folwark']['body'])->toContain('1520')
        // the Cossacks of the Vienna page are tied to the peasants who fled to them
        ->and($byKey['dues']['body'])->toContain('rising of 1648')
        // the constitution of 1791 is a bridge, not a topic here
        ->and($byKey['polaniec']['body'])->toContain('a later topic')
        // the partitions are named before the Duchy of Warsaw needs them
        ->and($byKey['polaniec']['body'])->toContain('1772 and 1793')
        ->and($byKey['duchy']['body'])->toContain('After 1795')
        // every partition is named as a share of the old country before its decree
        ->and($byKey['galicia']['body'])->toContain('Galicia, the Austrian share')
        ->and($byKey['decree']['body'])->toContain('the Kingdom of Poland')
        // and the page hands over to the next era
        ->and($byKey['decree']['body'])->toContain('partitions era');

    expect($firstMention('szlachta'))->toBe(array_search('folwark', $keys, true))
        ->and($firstMention('panszczyzna'))->toBe(array_search('dues', $keys, true))
        ->and($firstMention('Kosciuszko'))->toBe(array_search('polaniec', $keys, true))
        ->and($firstMention('Napoleon'))->toBe(array_search('duchy', $keys, true))
        ->and($firstMention('Alexander II'))->toBe(array_search('decree', $keys, true));
});

it('keeps the hard-won serfdom facts the way the sources give them', function (): void {
    $frames = collect(trans('site.serfdom.frames'))->keyBy('key');

    // numbers historians argue over are given as ranges, never as one figure
    expect($frames['folwark']['body'])->toContain('between about 6 and 10 per cent')
        ->and($frames['folwark']['body'])->toContain('just under 70 per cent')
        ->and($frames['dues']['body'])->toContain('historians give ranges')
        ->and($frames['galicia']['body'])->toContain('from about 1,000 to 3,000')
        // the lord's court and the price of a peasant's life
        ->and($frames['court']['body'])->toContain('In 1518 King Sigismund the Old')
        ->and($frames['court']['body'])->toContain('120 grzywna')
        ->and($frames['court']['body'])->toContain('he paid 10')
        ->and($frames['court']['body'])->toContain('from 1493')
        ->and($frames['court']['body'])->toContain('30 grzywna in 1581')
        ->and($frames['court']['body'])->toContain('Modrzewski')
        ->and($frames['court']['body'])->toContain('1768')
        // the tavern: the lord's monopoly and the smoke hut
        ->and($frames['village']['body'])->toContain('propinacja')
        ->and($frames['village']['body'])->toContain('kurna chata')
        // Polaniec: what it promised, and that it was not carried out
        ->and($frames['polaniec']['body'])->toContain('7 May')
        ->and($frames['polaniec']['body'])->toContain('a third to half less labour while the rising lasted')
        ->and($frames['polaniec']['body'])->toContain('Most nobles resisted it')
        // freedom without land, in the Duchy and in Prussia
        ->and($frames['duchy']['body'])->toContain('Article 4')
        ->and($frames['duchy']['body'])->toContain('21 December')
        ->and($frames['duchy']['body'])->toContain('1823')
        // 1846: the Austrian official is named, and so is the leader
        ->and($frames['galicia']['body'])->toContain('Joseph Breinl')
        ->and($frames['galicia']['body'])->toContain('Jakub Szela')
        ->and($frames['galicia']['body'])->toContain('22 April 1848')
        ->and($frames['galicia']['body'])->toContain('15 May')
        ->and($frames['decree']['body'])->toContain('2 March 1864')
        ->and($frames['decree']['body'])->toContain('1861')
        ->and($frames['today']['body'])->toContain('1906')
        ->and($frames['today']['body'])->toContain('1753')
        ->and(trans('site.serfdom.sources.body'))->toContain('1,200 to 3,000');

    // the 1846 killings are told, not celebrated
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['serfdom']['frames'] as $frame) {
            expect(mb_strtolower($frame['body']))->not->toContain('heroic')
                ->not->toContain('bohatersk');
        }
    }
});

it('writes the serfdom names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.serfdom.frames'))->keyBy('key');

    expect($frames['dues']['body'])->toContain('pańszczyzną')
        ->and($frames['dues']['body'])->toContain('łanu')
        ->and($frames['court']['body'])->toContain('Zygmunt Stary')
        ->and($frames['court']['body'])->toContain('120 grzywien')
        ->and($frames['village']['body'])->toContain('propinacji')
        ->and($frames['village']['body'])->toContain('kurna chata')
        ->and($frames['polaniec']['body'])->toContain('Połańcem')
        ->and($frames['polaniec']['body'])->toContain('Racławicami')
        ->and($frames['duchy']['body'])->toContain('niewola znosi się')
        ->and($frames['duchy']['body'])->toContain('Wielkim Księstwie Poznańskim')
        ->and($frames['galicia']['body'])->toContain('Starosta tarnowski')
        ->and($frames['decree']['body'])->toContain('tabelę likwidacyjną')
        ->and($frames['today']['body'])->toContain('Wdzydzach Kiszewskich');

    app()->setLocale('en');
});

it('renders the article on elected kings and the liberum veto as a filmstrip', function (): void {
    $this->get('/history/liberum-veto')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryVeto')
            ->has('translations.veto.frames', 9)
        );
});

it('credits every frame of the veto article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['veto']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/veto-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }
        }

        // two frames showing the same picture would read as a mistake
        expect(collect($copy['veto']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/veto-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.veto.sources.body'))->toContain('Bellotto')
        ->and(trans('site.veto.sources.body'))->toContain('1778');
});

it('places the veto article right after the serfdom article in the Commonwealth era', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/liberum-veto'))
        ->toBeGreaterThan(strpos($order, '/history/serfdom'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/liberum-veto',\s+titleKey: 'veto.meta.title',\s+era: 'commonwealth',\s+index: 5,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.veto.hero.kicker'))->toContain(trans('site.history.eras.commonwealth.label'))
            ->and(trans('site.veto.hero.kicker'))->toContain('06')
            ->and(trans('site.veto.meta.title'))->toContain(trans('site.history.eras.commonwealth.topics.5.title'));
    }

    app()->setLocale('en');
});

it('keeps every veto frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['veto']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the veto article from the first election to the partitions and 1791', function (): void {
    $frames = collect(trans('site.veto.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the page picks up where the faiths page left the first election
    expect($byKey['field']['body'])->toContain('1573')
        ->and($byKey['field']['body'])->toContain('Kamien')
        ->and($byKey['field']['body'])->toContain('faiths')
        ->and($byKey['field']['body'])->toContain('Henrician Articles')
        // the 1733 election goes back to the same field, and the text says so
        ->and($byKey['double']['body'])->toContain('Kamien, the field of 1573')
        // the Silent Sejm's army figure opens the next frame, not a new topic
        ->and($byKey['double']['body'])->toContain('24,000')
        // the story hands over to the partitions era and to the 1791 page
        ->and($byKey['repnin']['body'])->toContain('First Partition')
        ->and($byKey['repnin']['body'])->toContain('next era')
        ->and($byKey['today']['body'])->toContain('3 May 1791')
        ->and($byKey['today']['body'])->toContain('next topic');

    // everyone is on stage before they act
    expect($firstMention('confederation'))->toBe(array_search('saxony', $keys, true))
        ->and($firstMention('Leszczynski'))->toBe(array_search('saxony', $keys, true))
        ->and($firstMention('Augustus III'))->toBe(array_search('double', $keys, true))
        ->and($firstMention('Catherine II'))->toBe(array_search('poniatowski', $keys, true))
        ->and($firstMention('Repnin'))->toBe(array_search('repnin', $keys, true));
});

it('keeps the hard-won veto facts the way the sources give them', function (): void {
    $frames = collect(trans('site.veto.frames'))->keyBy('key');

    // 1652: a protest against sitting longer, closed by the marshal two days later
    expect($frames['veto']['body'])->toContain('9 March 1652')
        ->and($frames['veto']['body'])->toContain('11 March')
        ->and($frames['veto']['body'])->toContain('Upita')
        ->and($frames['veto']['body'])->toContain('sit longer')
        // Radziwill behind Sicinski is a suspicion, and stays one
        ->and($frames['veto']['body'])->toContain('long suspected')
        ->and($frames['veto']['body'])->toContain('Olizar')
        ->and($frames['veto']['body'])->toContain('1669')
        // the Silent Sejm and the first Russian mediation
        ->and($frames['saxony']['body'])->toContain('1 February 1717')
        ->and($frames['saxony']['body'])->toContain('Tarnogrod')
        ->and($frames['saxony']['body'])->toContain('Dolgorukov')
        // the marshal and the clerks read the laws aloud; it was the envoys who were silenced
        ->and($frames['saxony']['body'])->toContain('No envoy was allowed to speak')
        // 1717 was mediation, not yet a formal guarantee
        ->and($frames['double']['body'])->toContain('acted as the guardian')
        ->and($frames['double']['body'])->toContain('Potsdam')
        ->and($frames['double']['body'])->toContain('12 September')
        ->and($frames['double']['body'])->toContain('5 October')
        // one law-making Sejm in the whole reign of Augustus III
        ->and($frames['saxon']['body'])->toContain('1736')
        ->and($frames['saxon']['body'])->toContain('the only one')
        // the old verdict is told as a verdict, with today's historians beside it
        ->and($frames['saxon']['body'])->toContain('Older histories')
        ->and($frames['saxon']['body'])->toContain('Historians today')
        ->and($frames['poniatowski']['body'])->toContain('7 September')
        // the turnout of 1764 has one published figure only, so the page gives none
        ->and($frames['poniatowski']['body'])->not->toContain('5,500')
        ->and($frames['poniatowski']['body'])->toContain('thin crowd')
        ->and($frames['poniatowski']['body'])->toContain('April 1764')
        // the sources disagree on the night of the arrests, so the page gives the month
        ->and($frames['repnin']['body'])->toContain('October 1767')
        ->and($frames['repnin']['body'])->toContain('Kaluga')
        ->and($frames['repnin']['body'])->toContain('29 February')
        ->and($frames['repnin']['body'])->toContain('guarantee')
        // the castle burned in 1939; in 1944 the Germans blew up what was left
        ->and($frames['today']['body'])->toContain('1939')
        ->and($frames['today']['body'])->toContain('blew up the ruins in 1944')
        ->and($frames['today']['body'])->toContain('1997');

    // the veto is not the one cause of the fall, in any language
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['veto']['frames'] as $frame) {
            expect(mb_strtolower($frame['body']))->not->toContain('ruined')
                ->not->toContain('doomed')
                ->not->toContain('zgubił');
        }
    }
});

it('writes the veto names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.veto.frames'))->keyBy('key');

    expect($frames['field']['body'])->toContain('artykuły henrykowskie')
        ->and($frames['field']['body'])->toContain('pod Wolą')
        ->and($frames['veto']['body'])->toContain('Władysław Siciński, poseł upicki')
        ->and($frames['veto']['body'])->toContain('Janusza Radziwiłła')
        ->and($frames['veto']['body'])->toContain('nie pozwalam')
        ->and($frames['saxony']['body'])->toContain('Tarnogrodzie')
        ->and($frames['saxony']['body'])->toContain('Grzegorz Dołgoruki')
        ->and($frames['saxony']['body'])->toContain('sejm niemy')
        ->and($frames['double']['body'])->toContain('Poczdamie')
        ->and($frames['saxon']['body'])->toContain('za króla Sasa jedz, pij i popuszczaj pasa')
        ->and($frames['poniatowski']['body'])->toContain('pod węzłem konfederacji')
        ->and($frames['repnin']['body'])->toContain('Kaługi')
        ->and($frames['repnin']['body'])->toContain('prawa kardynalne')
        ->and($frames['repnin']['body'])->toContain('konfederację barską');

    app()->setLocale('en');
});

it('renders the article on the Constitution of 3 May 1791 as a filmstrip', function (): void {
    $this->get('/history/constitution-of-3-may-1791')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryConstitution')
            ->has('translations.constitution.frames', 9)
        );
});

it('credits every frame of the constitution article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['constitution']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/constitution-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }
        }

        // two frames showing the same picture would read as a mistake
        expect(collect($copy['constitution']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/constitution-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.constitution.sources.body'))->toContain('Matejko')
        ->and(trans('site.constitution.sources.body'))->toContain('1891');
});

it('places the constitution article right after the veto article in the Commonwealth era', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/constitution-of-3-may-1791'))
        ->toBeGreaterThan(strpos($order, '/history/liberum-veto'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/constitution-of-3-may-1791',\s+titleKey: 'constitution.meta.title',\s+era: 'commonwealth',\s+index: 6,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.constitution.hero.kicker'))->toContain(trans('site.history.eras.commonwealth.label'))
            ->and(trans('site.constitution.hero.kicker'))->toContain('07')
            ->and(trans('site.constitution.meta.title'))->toContain(trans('site.history.eras.commonwealth.topics.6.title'));
    }

    app()->setLocale('en');
});

it('keeps every constitution frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['constitution']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the constitution article from the Bar Confederation to the partitions', function (): void {
    $frames = collect(trans('site.constitution.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the page picks up where the veto page left the Bar Confederation
    expect($byKey['partition']['body'])->toContain('Bar Confederation')
        ->and($byKey['partition']['body'])->toContain('1772')
        // the partitions themselves belong to the next era, and the page says so
        ->and($byKey['partition']['body'])->toContain('partitions era')
        // the peasants are handed over to the serfdom page instead of being dropped
        ->and($byKey['peasants']['body'])->toContain('serfdom page')
        ->and($byKey['peasants']['body'])->toContain('1794')
        // and the story hands over to the next partition
        ->and($byKey['targowica']['body'])->toContain('Second Partition')
        ->and($byKey['today']['body'])->toContain('Grodno');

    // everyone is on stage before they act
    expect($firstMention('Stanislaw August'))->toBe(array_search('partition', $keys, true))
        ->and($firstMention('Commission of National Education'))->toBe(array_search('schools', $keys, true))
        ->and($firstMention('Kollataj'))->toBe(array_search('patriots', $keys, true))
        ->and($firstMention('Potocki'))->toBe(array_search('patriots', $keys, true))
        ->and($firstMention('Patriots'))->toBe(array_search('day', $keys, true))
        ->and($firstMention('Targowica'))->toBe(array_search('targowica', $keys, true))
        ->and($firstMention('Kosciuszko'))->toBe(array_search('targowica', $keys, true));

    // the Patriots are introduced as a party before they move the vote
    expect($byKey['patriots']['body'])->toContain('Patriotic Party');
});

it('keeps the hard-won constitution facts the way the sources give them', function (): void {
    $frames = collect(trans('site.constitution.frames'))->keyBy('key');

    expect($frames['partition']['body'])->toContain('5 August')
        ->and($frames['partition']['body'])->toContain('about a third')
        // the ministry claim is a label people use, not a measured fact
        ->and($frames['schools']['body'])->toContain('14 October 1773')
        ->and($frames['schools']['body'])->toContain('often called')
        ->and($frames['sejm']['body'])->toContain('6 October 1788')
        ->and($frames['sejm']['body'])->toContain('100,000')
        ->and($frames['sejm']['body'])->toContain('18,500')
        ->and($frames['sejm']['body'])->toContain('29 March 1790')
        ->and($frames['patriots']['body'])->toContain('2 December 1789')
        ->and($frames['patriots']['body'])->toContain('141 towns')
        ->and($frames['patriots']['body'])->toContain('18 April 1791')
        ->and($frames['patriots']['body'])->toContain('Piattoli')
        // moved forward from 5 May, with opponents away after Easter
        ->and($frames['day']['body'])->toContain('5 May')
        ->and($frames['day']['body'])->toContain('Easter')
        ->and($frames['day']['body'])->toContain('182')
        ->and($frames['day']['body'])->toContain('110 for and 72 against')
        ->and($frames['day']['body'])->toContain('Suchorzewski')
        // first in Europe, second in the world, and said as the usual label
        ->and($frames['act']['body'])->toContain('usually called')
        ->and($frames['act']['body'])->toContain("world's second")
        ->and($frames['act']['body'])->toContain('1787')
        ->and($frames['act']['body'])->toContain('Frederick Augustus')
        ->and($frames['act']['body'])->toContain('Mutual Guarantee')
        // the peasants got protection, not freedom
        ->and($frames['peasants']['body'])->toContain('protection of the law')
        ->and($frames['peasants']['body'])->toContain('Serfdom stayed')
        ->and($frames['targowica']['body'])->toContain('27 April 1792')
        ->and($frames['targowica']['body'])->toContain('18 May')
        ->and($frames['targowica']['body'])->toContain('nearly 100,000')
        ->and($frames['targowica']['body'])->toContain('Zielence')
        ->and($frames['targowica']['body'])->toContain('Dubienka')
        ->and($frames['targowica']['body'])->toContain('24 July')
        // 1946 banned the marches; the holiday itself went in 1951
        ->and($frames['today']['body'])->toContain('1919')
        ->and($frames['today']['body'])->toContain('banned marches on 3 May 1946')
        ->and($frames['today']['body'])->toContain('1951')
        ->and($frames['today']['body'])->toContain('1990')
        ->and($frames['today']['body'])->toContain('1891')
        ->and($frames['today']['body'])->toContain('2016')
        // bridges and details the fact gate added: why Russia was away, where the
        // name comes from, which two nations, the order founded for Zielence,
        // who took the Second Partition, and why Matejko is not a record
        ->and($frames['sejm']['body'])->toContain('busy in the south')
        ->and($frames['sejm']['body'])->toContain('Four-Year Sejm')
        ->and($frames['act']['body'])->toContain('Poland and Lithuania')
        ->and($frames['targowica']['body'])->toContain('Virtuti Militari')
        ->and($frames['targowica']['body'])->toContain('Russia and Prussia')
        ->and($frames['today']['body'])->toContain('Dekert, who died in 1790')
        ->and($frames['today']['body'])->toContain('list of holidays');

    // the era page carries the same careful superlative, in every language
    expect(trans('site.history.eras.commonwealth.topics.6.note'))->toContain('usually called')
        ->and(trans('site.history.eras.commonwealth.topics.6.note'))->toContain('second');

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['constitution']['frames'] as $frame) {
            expect(mb_strtolower($frame['body']))->not->toContain('first constitution in the world')
                ->not->toContain('abolished serfdom')
                ->not->toContain('zniosła pańszczyznę');
        }
    }
});

it('writes the constitution names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.constitution.frames'))->keyBy('key');

    expect($frames['partition']['body'])->toContain('Konfederacja barska')
        ->and($frames['schools']['body'])->toContain('Komisji Edukacji Narodowej')
        ->and($frames['sejm']['body'])->toContain('Radę Nieustającą')
        ->and($frames['patriots']['body'])->toContain('czarną procesją')
        ->and($frames['sejm']['body'])->toContain('Sejm Czteroletni')
        ->and($frames['targowica']['body'])->toContain('Virtuti Militari')
        ->and($frames['patriots']['body'])->toContain('Janem Dekertem')
        ->and($frames['patriots']['body'])->toContain('Hugo Kołłątaj')
        ->and($frames['patriots']['body'])->toContain('Scypion Piattoli')
        ->and($frames['day']['body'])->toContain('poseł kaliski')
        ->and($frames['day']['body'])->toContain('Sali Senatorskiej')
        ->and($frames['act']['body'])->toContain('Strażą Praw')
        ->and($frames['act']['body'])->toContain('Zaręczenie Wzajemne Obojga Narodów')
        ->and($frames['peasants']['body'])->toContain('pod opiekę prawa i rządu krajowego')
        ->and($frames['targowica']['body'])->toContain('Zieleńcami')
        ->and($frames['targowica']['body'])->toContain('Dubienką')
        ->and($frames['today']['body'])->toContain('Świątynię Opatrzności Bożej');

    app()->setLocale('en');
});

it('tells each early story once and points to it from the other articles', function (): void {
    $frames = fn (string $group) => collect(trans("site.{$group}.frames"))->keyBy('key');

    // the baptism is told on its own page; the origins frame moves on to what Mieszko did next
    expect($frames('origins')['mieszko']['body'])->toContain('article on the baptism')
        ->and($frames('origins')['mieszko']['body'])->toContain('Cedynia')
        ->and($frames('origins')['mieszko']['body'])->not->toContain('Latin script')
        // the legend is told on the eagle page
        ->and($frames('origins')['legend']['body'])->toContain('article on Lech and the eagle')
        ->and($frames('origins')['legend']['body'])->not->toContain('Czech and Rus')
        // only the where-to-stand page is a guide to the sites
        ->and($frames('origins')['today']['body'])->toContain('Where to stand in it')
        ->and($frames('baptism')['today']['body'])->toContain('Where to stand in it')
        ->and($frames('eagle')['today']['body'])->not->toContain('Museum of the Origins')
        // the collapse of the 1030s belongs to the Gniezno page
        ->and($frames('baptism')['slowly']['body'])->toContain('article on Gniezno')
        ->and($frames('baptism')['slowly']['body'])->not->toContain('Restorer')
        // Adalbert's death is told on the origins page, Gniezno keeps what is its own
        ->and($frames('gniezno')['grave']['body'])->toContain('barefoot')
        ->and($frames('gniezno')['grave']['body'])->not->toContain('bishop of Prague');
});

it('renders the article on the First Partition of 1772 as a filmstrip', function (): void {
    $this->get('/history/first-partition-1772')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryFirstPartition')
            ->has('translations.partition1.frames', 9)
        );
});

it('credits every frame of the first partition article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['partition1']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/partition1-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/partition1-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);
        }

        // two frames showing the same picture would read as a mistake
        expect(collect($copy['partition1']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/partition1-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.partition1.sources.body'))->toContain('Le Mire')
        ->and(trans('site.partition1.sources.body'))->toContain('1773');
});

it('places the first partition article right after the constitution, opening the partitions era', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/first-partition-1772'))
        ->toBeGreaterThan(strpos($order, '/history/constitution-of-3-may-1791'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/first-partition-1772',\s+titleKey: 'partition1.meta.title',\s+era: 'partitions',\s+index: 0,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.partition1.hero.kicker'))->toContain(trans('site.history.eras.partitions.label'))
            ->and(trans('site.partition1.hero.kicker'))->toContain('01')
            ->and(trans('site.partition1.meta.title'))->toContain(trans('site.history.eras.partitions.topics.0.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/first-partition-1772')
        ->assertSee(e(trans('site.partition1.meta.title')), escape: false)
        ->assertSee('/images/cards/partition1.jpg', escape: false);
});

it('keeps every first partition frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['partition1']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the first partition article from the Bar Confederation to the Second Partition', function (): void {
    $frames = collect(trans('site.partition1.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the era picks up where the veto page left the Bar Confederation
    expect($byKey['war']['body'])->toContain('Bar Confederation')
        ->and($byKey['war']['body'])->toContain('veto page')
        // the reforms are handed over to the constitution page instead of retold
        ->and($byKey['council']['body'])->toContain('constitution page')
        // and the story hands over to the next partition
        ->and($byKey['vistula']['body'])->toContain('Second Partition')
        ->and($byKey['today']['body'])->toContain('Second Partition');

    // everyone is on stage before they act, and introduced where they first appear
    expect($firstMention('Stanislaw August'))->toBe(array_search('war', $keys, true))
        ->and($firstMention('Catherine II'))->toBe(array_search('war', $keys, true))
        ->and($firstMention('Maria Theresa'))->toBe(array_search('zips', $keys, true))
        ->and($firstMention('Kaunitz'))->toBe(array_search('zips', $keys, true))
        ->and($firstMention('Frederick II'))->toBe(array_search('henry', $keys, true))
        ->and($firstMention('Prince Henry'))->toBe(array_search('henry', $keys, true))
        ->and($firstMention('Joseph II'))->toBe(array_search('treaty', $keys, true))
        ->and($firstMention('Poninski'))->toBe(array_search('sejm', $keys, true))
        ->and($firstMention('Rejtan'))->toBe(array_search('sejm', $keys, true))
        ->and($firstMention('Stackelberg'))->toBe(array_search('council', $keys, true));

    // Spisz is set up before Catherine points at it
    expect(array_search('zips', $keys, true))->toBeLessThan(array_search('henry', $keys, true));
});

it('keeps the hard-won first partition facts the way the sources give them', function (): void {
    $frames = collect(trans('site.partition1.frames'))->keyBy('key');

    expect($frames['war']['body'])->toContain('Balta')
        ->and($frames['war']['body'])->toContain('October 1768')
        ->and($frames['war']['body'])->toContain('Moldavia and Wallachia')
        ->and($frames['zips']['body'])->toContain('1412')
        ->and($frames['zips']['body'])->toContain('1769')
        ->and($frames['zips']['body'])->toContain('Nowy Targ')
        ->and($frames['henry']['body'])->toContain('1752 and 1768')
        ->and($frames['henry']['body'])->toContain('January 1771')
        ->and($frames['henry']['body'])->toContain('half in jest')
        ->and($frames['treaty']['body'])->toContain('5 August 1772')
        ->and($frames['treaty']['body'])->toContain('Most Holy Trinity')
        ->and($frames['treaty']['body'])->toContain('Galicia and Lodomeria')
        // the shares are estimates that differ, and the page says so
        ->and($frames['shares']['body'])->toContain('figures differ')
        ->and($frames['shares']['body'])->toContain('84,000 to 92,000')
        ->and($frames['shares']['body'])->toContain('not Krakow')
        ->and($frames['shares']['body'])->toContain('without Gdansk and Torun')
        ->and($frames['vistula']['body'])->toContain('Robert H. Lord')
        ->and($frames['vistula']['body'])->toContain('1793')
        ->and($frames['sejm']['body'])->toContain('16 April 1773')
        ->and($frames['sejm']['body'])->toContain('21 April')
        ->and($frames['sejm']['body'])->toContain('Nowogrodek')
        ->and($frames['sejm']['body'])->toContain('Korsak')
        ->and($frames['sejm']['body'])->toContain('Bohuszewicz')
        // the painting is a verdict made 93 years later, and the caption says so
        ->and($frames['sejm']['caption'])->toContain('1866')
        ->and($frames['sejm']['caption'])->toContain('not a record')
        ->and($frames['council']['body'])->toContain('18 September 1773')
        ->and($frames['council']['body'])->toContain('36 members')
        ->and($frames['council']['body'])->toContain('guaranteed by Russia')
        ->and($frames['today']['body'])->toContain('1918')
        ->and($frames['today']['body'])->toContain('Lviv');
});

it('writes the first partition names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.partition1.frames'))->keyBy('key');

    expect($frames['war']['body'])->toContain('konfederacji barskiej')
        ->and($frames['war']['body'])->toContain('Katarzyna II')
        ->and($frames['zips']['body'])->toContain('Zygmunt Luksemburski')
        ->and($frames['zips']['body'])->toContain('Władysławowi Jagielle')
        ->and($frames['zips']['body'])->toContain('Maria Teresa')
        ->and($frames['henry']['body'])->toContain('Fryderyk II')
        ->and($frames['henry']['body'])->toContain('książę Henryk')
        ->and($frames['henry']['body'])->toContain('Królewcem')
        ->and($frames['treaty']['body'])->toContain('Józef II')
        ->and($frames['treaty']['body'])->toContain('Królestwem Galicji i Lodomerii')
        ->and($frames['shares']['body'])->toContain('Inflanty Polskie')
        ->and($frames['sejm']['body'])->toContain('poseł nowogródzki')
        ->and($frames['sejm']['body'])->toContain('Adam Poniński')
        ->and($frames['council']['body'])->toContain('Radę Nieustającą')
        ->and($frames['council']['body'])->toContain('Wolter')
        ->and($frames['today']['body'])->toContain('żupy krakowskie');

    app()->setLocale('en');
});

it('labels the partition map from the page translations, in every language', function (): void {
    $component = file_get_contents(resource_path('js/components/PartitionMap.vue'));

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['partition1']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            ->and($map['labels'])->toHaveKeys([
                'sea', 'commonwealth', 'east_prussia', 'russia', 'prussia', 'austria',
                'warsaw', 'krakow', 'gdansk', 'torun', 'lwow', 'vilnius', 'polotsk', 'konigsberg',
            ]);
    }

    // the caption owns up to the map being a schematic
    expect(trans('site.partition1.map.caption'))->toContain('Schematic');

    // every label the component reads exists, and no label is typed into it
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);
    expect(array_diff($keys[1], array_keys(trans('site.partition1.map.labels'))))->toBe([])
        ->and($component)->toContain('map.labels.')
        ->and($component)->not->toContain('>Warsaw<');
});

it('renders the article on the Second Partition of 1793 as a filmstrip', function (): void {
    $this->get('/history/second-partition-1793')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistorySecondPartition')
            ->has('translations.partition2.frames', 9)
        );
});

it('credits every frame of the second partition article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['partition2']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/partition2-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/partition2-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/partition2-{$frame['photo']}-lg.jpg"))[0])->toBe(1280);
        }

        // two frames showing the same picture would read as a mistake
        expect(collect($copy['partition2']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/partition2-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.partition2.sources.body'))->toContain('A Dance round the Poles')
        ->and(trans('site.partition2.sources.body'))->toContain('1794');
});

it('places the second partition article right after the first, as the second partitions topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/second-partition-1793'))
        ->toBeGreaterThan(strpos($order, '/history/first-partition-1772'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/second-partition-1793',\s+titleKey: 'partition2.meta.title',\s+era: 'partitions',\s+index: 1,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.partition2.hero.kicker'))->toContain(trans('site.history.eras.partitions.label'))
            ->and(trans('site.partition2.hero.kicker'))->toContain('02')
            ->and(trans('site.partition2.meta.title'))->toContain(trans('site.history.eras.partitions.topics.1.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/second-partition-1793')
        ->assertSee(e(trans('site.partition2.meta.title')), escape: false)
        ->assertSee('/images/cards/partition2.jpg', escape: false);
});

it('keeps every second partition frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['partition2']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the second partition article from the constitution to the rising of 1794', function (): void {
    $frames = collect(trans('site.partition2.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // the reforms are handed back to the constitution page instead of retold
    expect($byKey['ally']['body'])->toContain('constitution page')
        ->and($byKey['ally']['body'])->toContain('First Partition')
        // and the story hands over to Kosciuszko without telling the rising
        ->and($byKey['army']['body'])->toContain('Kosciuszko')
        ->and($byKey['army']['body'])->toContain('next topic')
        ->and($byKey['army']['body'])->not->toContain('Raclawice');

    // everyone is on stage before they act, and introduced where they first appear
    expect($firstMention('Frederick William II'))->toBe(array_search('ally', $keys, true))
        ->and($firstMention('Ignacy Potocki'))->toBe(array_search('ally', $keys, true))
        ->and($firstMention('Catherine II'))->toBe(array_search('deal', $keys, true))
        ->and($firstMention('Raumer'))->toBe(array_search('gdansk', $keys, true))
        ->and($firstMention('Jakob Sievers'))->toBe(array_search('targowica', $keys, true))
        ->and($firstMention('Sievers'))->toBe(array_search('targowica', $keys, true))
        ->and($firstMention('Stanislaw August'))->toBe(array_search('sievers', $keys, true))
        ->and($firstMention('Bielinski'))->toBe(array_search('silent', $keys, true))
        ->and($firstMention('Madalinski'))->toBe(array_search('army', $keys, true));

    // the ambassador is introduced before the frame that is about him
    expect(array_search('targowica', $keys, true))->toBeLessThan(array_search('sievers', $keys, true));
});

it('keeps the hard-won second partition facts the way the sources give them', function (): void {
    $frames = collect(trans('site.partition2.frames'))->keyBy('key');

    expect($frames['ally']['body'])->toContain('March 1790')
        ->and($frames['ally']['body'])->toContain('came after the treaty')
        ->and($frames['deal']['body'])->toContain('23 January 1793')
        ->and($frames['deal']['body'])->toContain('leaving Austria out')
        // the shares are estimates that differ, and the page says so
        ->and($frames['shares']['body'])->toContain('rough')
        ->and($frames['shares']['body'])->toContain('230,000 to 250,000')
        ->and($frames['shares']['body'])->toContain('Druja to Pinsk')
        ->and($frames['shares']['body'])->toContain('South Prussia')
        ->and($frames['gdansk']['body'])->toContain('24 January 1793')
        ->and($frames['gdansk']['body'])->toContain('8 March')
        ->and($frames['gdansk']['body'])->toContain('28 March')
        ->and($frames['gdansk']['body'])->toContain('4 April')
        ->and($frames['gdansk']['body'])->toContain('7 May')
        ->and($frames['silent']['body'])->toContain('17 June 1793')
        ->and($frames['silent']['body'])->toContain('23 September')
        ->and($frames['silent']['body'])->toContain('silence meant yes')
        ->and($frames['silent']['caption'])->toContain('25 September 1793')
        ->and($frames['army']['body'])->toContain('14 October')
        ->and($frames['army']['body'])->toContain('23 November')
        // the size of the cut army is counted differently, and the page says so
        ->and($frames['army']['body'])->toContain('15,000 to 18,000')
        ->and($frames['army']['body'])->toContain('12 March 1794')
        ->and($frames['army']['body'])->toContain('Ostroleka')
        // Grodno is not in Poland today, and the page says where it is
        ->and($frames['today']['body'])->toContain('Belarus')
        ->and($frames['today']['body'])->toContain('Hrodna')
        ->and($frames['today']['body'])->toContain('1918');
});

it('writes the second partition names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.partition2.frames'))->keyBy('key');

    expect($frames['ally']['body'])->toContain('Fryderyk Wilhelm II')
        ->and($frames['deal']['body'])->toContain('Katarzyna II')
        ->and($frames['shares']['body'])->toContain('Prusy Południowe')
        ->and($frames['gdansk']['body'])->toContain('Moellendorfa')
        ->and($frames['targowica']['body'])->toContain('konfederacja targowicka')
        ->and($frames['targowica']['body'])->toContain('Stanisław Szczęsny Potocki')
        ->and($frames['silent']['body'])->toContain('Stanisław Bieliński')
        ->and($frames['silent']['label'])->toContain('Niema sesja')
        ->and($frames['army']['body'])->toContain('Madaliński')
        ->and($frames['army']['body'])->toContain('Ostrołęki')
        ->and($frames['today']['body'])->toContain('Białorusi');

    app()->setLocale('en');
});

it('labels the 1793 partition map from the page translations, in every language', function (): void {
    $component = file_get_contents(resource_path('js/components/PartitionMap.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['partition2']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            // every label the component can read exists for this page too
            ->and(array_diff($keys[1], array_keys($map['labels'])))->toBe([])
            ->and($map['labels'])->toHaveKeys(['sea', 'commonwealth', 'east_prussia', 'russia', 'prussia', 'austria', 'grodno', 'poznan']);
    }

    // the caption owns up to the map being a schematic
    expect(trans('site.partition2.map.caption'))->toContain('Schematic');

    // the page publishes the period map instead of the drawn one
    expect(file_get_contents(resource_path('js/pages/HistorySecondPartition.vue')))->toContain('partition2-laurie')
        ->and($component)->toContain('partition: 2,');
});

it('keeps the second partition fixes from the fact check in both languages', function (): void {
    $frames = fn (string $locale) => collect((require lang_path("{$locale}/site.php"))['partition2']['frames'])->keyBy('key');

    // the convention was signed by Russian ministers and one Prussian envoy
    expect($frames('en')['deal']['body'])->toContain('Russian ministers and the Prussian envoy')
        ->and($frames('pl')['deal']['body'])->toContain('rosyjscy ministrowie i poseł pruski')
        // the soldiers round the castle were Russian, and the page says whose
        ->and($frames('en')['silent']['body'])->toContain('Russian grenadiers')
        ->and($frames('pl')['silent']['body'])->toContain('rosyjscy grenadierzy')
        // the treaty let Russia send troops when it judged it necessary, not on a whim
        ->and($frames('en')['army']['body'])->toContain('judged it necessary')
        ->and($frames('pl')['army']['body'])->toContain('uzna to za konieczne')
        // the Grodno Sejm is not named before the frame that introduces it
        ->and($frames('en')['shares']['body'])->not->toContain('Grodno')
        ->and($frames('pl')['shares']['body'])->not->toContain('grodzie');
});

it('renders the article on Kosciuszko and the rising of 1794 as a filmstrip', function (): void {
    $this->get('/history/kosciuszko-1794')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryKosciuszko')
            ->has('translations.kosciuszko.frames', 9)
        );
});

it('credits every frame of the Kosciuszko article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['kosciuszko']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/kosciuszko-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/kosciuszko-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/kosciuszko-{$frame['photo']}-lg.jpg"))[0])->toBe(1280);
        }

        // two frames showing the same picture would read as a mistake
        expect(collect($copy['kosciuszko']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/kosciuszko-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.kosciuszko.sources.body'))->toContain('Benjamin West')
        ->and(trans('site.kosciuszko.sources.body'))->toContain('1797');
});

it('uses pictures on the Kosciuszko article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/kosciuszko-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'kosciuszko-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();

    // the Grassi drawing of Kosciuszko belongs to the second partition page
    expect(collect(trans('site.kosciuszko.frames'))->pluck('credit')->implode(' '))->not->toContain('Grassi');
});

it('places the Kosciuszko article right after the second partition, as the third partitions topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/kosciuszko-1794'))
        ->toBeGreaterThan(strpos($order, '/history/second-partition-1793'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/kosciuszko-1794',\s+titleKey: 'kosciuszko.meta.title',\s+era: 'partitions',\s+index: 2,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.kosciuszko.hero.kicker'))->toContain(trans('site.history.eras.partitions.label'))
            ->and(trans('site.kosciuszko.hero.kicker'))->toContain('03')
            ->and(trans('site.kosciuszko.meta.title'))->toContain(trans('site.history.eras.partitions.topics.2.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/kosciuszko-1794')
        ->assertSee(e(trans('site.kosciuszko.meta.title')), escape: false)
        ->assertSee('/images/cards/kosciuszko.jpg', escape: false);
});

it('keeps every Kosciuszko frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['kosciuszko']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Kosciuszko article from Madalinski\'s march to the Third Partition', function (): void {
    $frames = collect(trans('site.kosciuszko.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // it picks up where the second partition page stops, with Madalinski leaving Ostroleka
    expect($byKey['engineer']['body'])->toContain('Madalinski')
        ->and($byKey['engineer']['body'])->toContain('Ostroleka')
        // the Polaniec proclamation is handed to the serfdom page instead of retold
        ->and($byKey['raclawice']['body'])->toContain('Polaniec')
        ->and($byKey['raclawice']['body'])->toContain('serfdom page')
        // and the story hands over to the Third Partition without telling it
        ->and($byKey['memory']['body'])->toContain('next topic')
        ->and($byKey['memory']['body'])->not->toContain('1795');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Stanislaw August'))->toBe(array_search('engineer', $keys, true))
        ->and($firstMention('Targowica Confederation'))->toBe(array_search('dubienka', $keys, true))
        ->and($firstMention('Kollataj'))->toBe(array_search('dubienka', $keys, true))
        ->and($firstMention('Tormasov'))->toBe(array_search('raclawice', $keys, true))
        ->and($firstMention('Igelstrom'))->toBe(array_search('warsaw', $keys, true))
        ->and($firstMention('Kilinski'))->toBe(array_search('warsaw', $keys, true))
        ->and($firstMention('Jasinski'))->toBe(array_search('warsaw', $keys, true))
        ->and($firstMention('Szczekociny'))->toBe(array_search('gallows', $keys, true))
        ->and($firstMention('Frederick William II'))->toBe(array_search('siege', $keys, true))
        ->and($firstMention('Suvorov'))->toBe(array_search('siege', $keys, true))
        ->and($firstMention('Fersen'))->toBe(array_search('siege', $keys, true))
        ->and($firstMention('Catherine II'))->toBe(array_search('memory', $keys, true))
        ->and($firstMention('Thomas Jefferson'))->toBe(array_search('memory', $keys, true));
});

it('keeps the hard-won Kosciuszko facts the way the sources give them', function (): void {
    $frames = collect(trans('site.kosciuszko.frames'))->keyBy('key');

    expect($frames['engineer']['body'])->toContain('1746')
        ->and($frames['engineer']['body'])->toContain('October 1777')
        ->and($frames['dubienka']['body'])->toContain('18 July')
        ->and($frames['dubienka']['body'])->toContain('25,000')
        // the plan in the picture misdates the battle, and the caption says so
        ->and($frames['dubienka']['caption'])->toContain('wrong year')
        ->and($frames['oath']['body'])->toContain('24 March 1794')
        ->and($frames['oath']['body'])->toContain('Naczelnik')
        // Raclawice was not a win against the odds: the Russians were fewer
        ->and($frames['raclawice']['body'])->toContain('smaller Russian force')
        ->and($frames['raclawice']['body'])->toContain('4 April')
        // the cap on the cannon is a story people tell, and the page says so
        ->and($frames['raclawice']['body'])->toContain('The story goes')
        ->and($frames['warsaw']['body'])->toContain('17 April')
        ->and($frames['warsaw']['body'])->toContain('22 April')
        ->and($frames['gallows']['body'])->toContain('25 April')
        ->and($frames['gallows']['body'])->toContain('9 May')
        ->and($frames['gallows']['body'])->toContain('28 June')
        ->and($frames['gallows']['body'])->toContain('without trial')
        ->and($frames['siege']['body'])->toContain('13 July')
        ->and($frames['siege']['body'])->toContain('6 September')
        ->and($frames['siege']['body'])->toContain('10 October')
        // the Praga death toll is an estimate, attributed, never a single number
        ->and($frames['praga']['body'])->toContain('4 November')
        ->and($frames['praga']['body'])->toContain('13,000 to 20,000')
        ->and($frames['praga']['body'])->toContain('Museum of Polish History')
        ->and($frames['praga']['body'])->toContain('civilians')
        ->and($frames['praga']['body'])->toContain('Berek Joselewicz')
        ->and($frames['praga']['body'])->toContain('16 November')
        // the will freed nobody, and the page does not pretend otherwise
        ->and($frames['memory']['body'])->toContain('1798')
        ->and($frames['memory']['body'])->toContain('no one was freed')
        ->and($frames['memory']['body'])->toContain('swore loyalty to the tsar')
        ->and($frames['memory']['body'])->toContain('1820-1823')
        ->and($frames['memory']['body'])->toContain('1828')
        ->and($frames['memory']['body'])->toContain('1840');

    // a quote Kosciuszko denied is not put in his mouth, in any language
    foreach (['en', 'pl'] as $locale) {
        expect(json_encode((require lang_path("{$locale}/site.php"))['kosciuszko']))->not->toContain('Finis Poloniae');
    }
});

it('writes the Kosciuszko names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.kosciuszko.frames'))->keyBy('key');

    expect($frames['engineer']['body'])->toContain('Tadeusz Kościuszko')
        ->and($frames['engineer']['body'])->toContain('Antoni Madaliński')
        ->and($frames['dubienka']['body'])->toContain('Hugonem Kołłątajem')
        ->and($frames['dubienka']['body'])->toContain('konfederacji targowickiej')
        ->and($frames['oath']['body'])->toContain('Naczelnik')
        ->and($frames['raclawice']['body'])->toContain('Racławicami')
        ->and($frames['raclawice']['body'])->toContain('Połańcem')
        ->and($frames['warsaw']['body'])->toContain('Jana Kilińskiego')
        ->and($frames['warsaw']['body'])->toContain('Jakuba Jasińskiego')
        ->and($frames['gallows']['body'])->toContain('Szczekocinami')
        ->and($frames['siege']['body'])->toContain('Fryderyk Wilhelm II')
        ->and($frames['siege']['body'])->toContain('Suworowa')
        ->and($frames['praga']['body'])->toContain('13 do 20 tysięcy')
        ->and($frames['memory']['body'])->toContain('Wawelu');

    app()->setLocale('en');
});

it('marks the battlefields of 1794 on the partition map from the page translations', function (): void {
    $component = file_get_contents(resource_path('js/components/PartitionMap.vue'));
    $page = file_get_contents(resource_path('js/pages/HistoryKosciuszko.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $towns);
    preg_match_all("#id: '([a-z_]+)'#", $component, $sites);

    expect($sites[1])->toContain('raclawice')->toContain('szczekociny')->toContain('maciejowice');

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['kosciuszko']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            // every town and every battlefield the component can draw has a label here
            ->and(array_diff($towns[1], array_keys($map['labels'])))->toBe([])
            ->and(array_diff($sites[1], array_keys($map['labels'])))->toBe([]);
    }

    expect(trans('site.kosciuszko.map.caption'))->toContain('Schematic')
        // the page shows the rump state of 1793 and asks for its three battlefields
        ->and($page)->toContain(':highlight="[2]"')
        ->and($page)->toContain("'raclawice'")
        ->and($page)->toContain("'maciejowice'")
        // the other partition pages ask for no sites, so their maps stay as they were
        ->and(file_get_contents(resource_path('js/pages/HistorySecondPartition.vue')))->not->toContain(':sites');
});

it('keeps the Kosciuszko fixes from the fact and language checks in both languages', function (): void {
    $frames = fn (string $locale) => collect((require lang_path("{$locale}/site.php"))['kosciuszko']['frames'])->keyBy('key');

    // the peak is the highest of mainland Australia, not of Australia, and its namer is named
    expect($frames('en')['memory']['body'])->toContain('mainland Australia')
        ->and($frames('en')['memory']['body'])->toContain('Pawel Strzelecki')
        ->and($frames('pl')['memory']['body'])->toContain('lądu australijskiego')
        ->and($frames('pl')['memory']['body'])->toContain('Paweł Strzelecki')
        // the body that commissioned him in America is named
        ->and($frames('en')['engineer']['body'])->toContain('Continental Congress')
        // the court that sentenced the Targowica leaders is the rising's own
        ->and($frames('en')['gallows']['body'])->toContain('insurgent court')
        ->and($frames('pl')['gallows']['body'])->toContain('sąd powstańczy')
        // the lynching is dated to the news of the defeats, not given a motive the sources do not state
        ->and($frames('en')['gallows']['body'])->toContain('news of the defeats')
        ->and($frames('pl')['gallows']['body'])->toContain('wieści o klęskach')
        // the constitution the Russians came to overturn is dated
        ->and($frames('en')['dubienka']['body'])->toContain('3 May 1791')
        ->and($frames('pl')['dubienka']['body'])->toContain('3 maja 1791')
        // Suvorov is not ranked as the best general by the page itself
        ->and($frames('en')['siege']['body'])->not->toContain('best general');
});

it('renders the article on the Third Partition of 1795 as a filmstrip', function (): void {
    $this->get('/history/third-partition-1795')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryThirdPartition')
            ->has('translations.partition3.frames', 9)
        );
});

it('credits every frame of the third partition article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['partition3']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/partition3-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/partition3-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/partition3-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/partition3-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['partition3']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/partition3-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.partition3.sources.body'))->toContain('William Faden')
        ->and(trans('site.partition3.sources.body'))->toContain('1799');
});

it('uses pictures on the third partition article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/partition3-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'partition3-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the third partition article right after Kosciuszko, as the fourth partitions topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/third-partition-1795'))
        ->toBeGreaterThan(strpos($order, '/history/kosciuszko-1794'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/third-partition-1795',\s+titleKey: 'partition3.meta.title',\s+era: 'partitions',\s+index: 3,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.partition3.hero.kicker'))->toContain(trans('site.history.eras.partitions.label'))
            ->and(trans('site.partition3.hero.kicker'))->toContain('04')
            ->and(trans('site.partition3.meta.title'))->toContain(trans('site.history.eras.partitions.topics.3.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/third-partition-1795')
        ->assertSee(e(trans('site.partition3.meta.title')), escape: false)
        ->assertSee('/images/cards/partition3.jpg', escape: false);
});

it('keeps every third partition frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['partition3']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the third partition article from Praga to the years without a state', function (): void {
    $frames = collect(trans('site.partition3.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // it picks up where the Kosciuszko page stops, at Praga, and sends the king to Grodno
    expect($keys[0])->toBe('occupied')
        ->and($byKey['occupied']['body'])->toContain('Praga')
        ->and($byKey['occupied']['body'])->toContain('Grodno')
        ->and($byKey['occupied']['body'])->toContain('7 January 1795')
        // the Duchy of Warsaw is only pointed to, and handed on to the next topics
        ->and($byKey['years']['body'])->toContain('Duchy of Warsaw')
        ->and($byKey['years']['body'])->toContain('next topics');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Suvorov'))->toBe(array_search('occupied', $keys, true))
        ->and($firstMention('Stanislaw August'))->toBe(array_search('occupied', $keys, true))
        ->and($firstMention('Catherine II'))->toBe(array_search('deal', $keys, true))
        ->and($firstMention('Francis II'))->toBe(array_search('deal', $keys, true))
        ->and($firstMention('Frederick William II'))->toBe(array_search('deal', $keys, true))
        ->and($firstMention('Repnin'))->toBe(array_search('abdication', $keys, true))
        ->and($firstMention('Paul I'))->toBe(array_search('petersburg', $keys, true))
        ->and($firstMention('Dabrowski'))->toBe(array_search('legions', $keys, true))
        ->and($firstMention('Wybicki'))->toBe(array_search('legions', $keys, true))
        ->and($firstMention('Napoleon Bonaparte'))->toBe(array_search('legions', $keys, true))
        ->and($firstMention('Napoleon'))->toBeLessThan(array_search('years', $keys, true));
});

it('keeps the hard-won third partition facts the way the sources give them', function (): void {
    $frames = collect(trans('site.partition3.frames'))->keyBy('key');

    expect($frames['deal']['body'])->toContain('3 January 1795')
        ->and($frames['shares']['body'])->toContain('24 October 1795')
        ->and($frames['shares']['body'])->toContain('New Galicia')
        ->and($frames['shares']['body'])->toContain('5 January 1796')
        ->and($frames['shares']['body'])->toContain('9 January')
        ->and($frames['abdication']['body'])->toContain('25 November 1795')
        // the date is the coronation anniversary on St Catherine's day, not the Russian name day
        ->and($frames['abdication']['body'])->toContain('anniversary of his coronation')
        ->and($frames['abdication']['body'])->not->toContain('name day')
        ->and($frames['petersburg']['body'])->toContain('12 February 1798')
        ->and($frames['petersburg']['body'])->toContain('1938')
        ->and($frames['petersburg']['body'])->toContain('Wolczyn')
        ->and($frames['petersburg']['body'])->toContain('14 February 1995')
        ->and($frames['name']['body'])->toContain('26 January 1797')
        ->and($frames['name']['body'])->toContain('Kingdom of Poland')
        ->and($frames['legions']['body'])->toContain('9 January 1797')
        ->and($frames['legions']['body'])->toContain('Reggio Emilia')
        // the song first said "has not yet died"; "perished" came later
        ->and($frames['legions']['body'])->toContain('not yet died')
        ->and($frames['years']['body'])->toContain('October 1795 to November 1918')
        ->and($frames['today']['body'])->toContain('26 February 1927')
        ->and($frames['today']['body'])->toContain('1978')
        // fixes from the fact check: the tsar of 1815 is named, the rising is dated, Praga is placed
        ->and($frames['years']['body'])->toContain('Alexander I')
        ->and($frames['years']['body'])->toContain('Belarusians and Ukrainians')
        ->and($frames['occupied']['body'])->toContain('rising of 1794')
        ->and($frames['occupied']['body'])->toContain('suburb across the Vistula')
        ->and(trans('site.partition3.frames.7.body', [], 'pl'))->toContain('Aleksander I');

    // the shares are described, not measured, because the estimates differ
    foreach (['en', 'pl'] as $locale) {
        expect(json_encode((require lang_path("{$locale}/site.php"))['partition3']['frames']))->not->toMatch('/km|kilomet/');
    }
});

it('writes the third partition names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.partition3.frames'))->keyBy('key');

    expect($frames['occupied']['body'])->toContain('Suworowa')
        ->and($frames['occupied']['body'])->toContain('Jana Kilińskiego')
        ->and($frames['occupied']['body'])->toContain('Grodna')
        ->and($frames['deal']['body'])->toContain('Katarzyna II')
        ->and($frames['deal']['body'])->toContain('Fryderyk Wilhelm II')
        ->and($frames['shares']['body'])->toContain('Nową Galicją')
        ->and($frames['abdication']['body'])->toContain('Nikołaj Repnin')
        ->and($frames['petersburg']['body'])->toContain('Wołczyna')
        ->and($frames['name']['body'])->toContain('Królestwo Polskie')
        ->and($frames['legions']['body'])->toContain('Jan Henryk Dąbrowski')
        ->and($frames['legions']['body'])->toContain('Józef Wybicki')
        ->and($frames['legions']['body'])->toContain('Jeszcze Polska nie umarła')
        ->and($frames['years']['body'])->toContain('Księstwo Warszawskie')
        ->and($frames['today']['body'])->toContain('Łazienkach')
        ->and($frames['today']['body'])->toContain('Będominie');

    app()->setLocale('en');
});

it('draws all three partitions on the map of 1795 from the page translations', function (): void {
    $component = file_get_contents(resource_path('js/components/PartitionMap.vue'));
    $page = file_get_contents(resource_path('js/pages/HistoryThirdPartition.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['partition3']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            ->and(array_diff($keys[1], array_keys($map['labels'])))->toBe([]);
    }

    expect(trans('site.partition3.map.caption'))->toContain('Schematic')
        // the page publishes Faden's 1799 sheet, which dates every partition line
        ->and($page)->toContain('partition3-faden')
        ->and(substr_count($component, 'partition: 3,'))->toBe(3)
        // the drawn map still serves the pages that have no period map of their own
        ->and(file_get_contents(resource_path('js/pages/HistoryKosciuszko.vue')))->toContain(':highlight="[2]"');
});

it('renders the article on the uprisings of 1830 and 1863 as a filmstrip', function (): void {
    $this->get('/history/uprisings-1830-1863')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryUprisings')
            ->has('translations.uprisings.frames', 9)
        );
});

it('credits every frame of the uprisings article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['uprisings']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/uprisings-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/uprisings-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/uprisings-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/uprisings-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['uprisings']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/uprisings-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.uprisings.sources.body'))->toContain('Matejko')
        ->and(trans('site.uprisings.sources.body'))->toContain('Polonia 1863');
});

it('uses pictures on the uprisings article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/uprisings-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'uprisings-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the uprisings article right after the Third Partition, as the fifth partitions topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/uprisings-1830-1863'))
        ->toBeGreaterThan(strpos($order, '/history/third-partition-1795'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/uprisings-1830-1863',\s+titleKey: 'uprisings.meta.title',\s+era: 'partitions',\s+index: 4,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.uprisings.hero.kicker'))->toContain(trans('site.history.eras.partitions.label'))
            ->and(trans('site.uprisings.hero.kicker'))->toContain('05')
            ->and(trans('site.uprisings.meta.title'))->toContain(trans('site.history.eras.partitions.topics.4.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/uprisings-1830-1863')
        ->assertSee(e(trans('site.uprisings.meta.title')), escape: false)
        ->assertSee('/images/cards/uprisings.jpg', escape: false);
});

it('keeps every uprisings frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['uprisings']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the uprisings article from the legions to the Vistula Land', function (): void {
    $frames = collect(trans('site.uprisings.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // it picks up the two bridges the third partition page hands on: 1807 and 1815
    expect($keys)->toBe(['duchy', 'kingdom', 'night', 'war', 'exile', 'citadel', 'mourning', 'forest', 'aftermath'])
        ->and($byKey['duchy']['body'])->toContain('legions')
        ->and($byKey['duchy']['body'])->toContain('Duchy of Warsaw')
        ->and($byKey['kingdom']['body'])->toContain('Congress of Vienna')
        ->and($byKey['kingdom']['body'])->toContain('Grand Duchy of Posen')
        ->and($byKey['kingdom']['body'])->toContain('free city')
        // Alexander I dies on the page before his brother rules
        ->and($byKey['night']['body'])->toContain('Alexander I died in 1825')
        // Nicholas I dies before his son loosens the grip
        ->and($byKey['mourning']['body'])->toContain('Nicholas I died in 1855')
        // what another topic tells is pointed to, not repeated
        ->and($byKey['aftermath']['body'])->toContain('serfdom topic');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Napoleon'))->toBe(array_search('duchy', $keys, true))
        ->and($firstMention('Frederick Augustus'))->toBe(array_search('duchy', $keys, true))
        ->and($firstMention('Poniatowski'))->toBe(array_search('duchy', $keys, true))
        ->and($firstMention('Alexander I'))->toBe(array_search('kingdom', $keys, true))
        ->and($firstMention('Grand Duke Constantine'))->toBe(array_search('kingdom', $keys, true))
        ->and($firstMention('Nicholas I'))->toBe(array_search('night', $keys, true))
        ->and($firstMention('Piotr Wysocki'))->toBe(array_search('night', $keys, true))
        ->and($firstMention('Field Marshal Ivan Diebitsch'))->toBe(array_search('war', $keys, true))
        ->and($firstMention('Field Marshal Ivan Paskevich'))->toBe(array_search('war', $keys, true))
        ->and($firstMention('Czartoryski'))->toBe(array_search('exile', $keys, true))
        ->and($firstMention('Alexander II'))->toBe(array_search('mourning', $keys, true))
        ->and($firstMention('Wielopolski'))->toBe(array_search('mourning', $keys, true))
        ->and($firstMention('Kalinowski'))->toBe(array_search('forest', $keys, true))
        ->and($firstMention('Romuald Traugutt'))->toBe(array_search('aftermath', $keys, true));
});

it('keeps the hard-won uprisings facts the way the sources give them', function (): void {
    $frames = collect(trans('site.uprisings.frames'))->keyBy('key');

    expect($frames['duchy']['body'])->toContain('22 July')
        ->and($frames['duchy']['body'])->toContain('close to 100,000')
        ->and($frames['kingdom']['body'])->toContain('27 November 1815')
        // the Sejm was not suspended in 1825, its debates were closed to the public
        ->and($frames['kingdom']['body'])->toContain('debate in public')
        ->and($frames['night']['body'])->toContain('six Polish generals')
        ->and($frames['war']['body'])->toContain('25 January 1831')
        ->and($frames['war']['body'])->toContain('115,000')
        ->and($frames['war']['body'])->toContain('6 September')
        ->and($frames['war']['body'])->toContain('5 October')
        // the emigration is an estimate, and it is given as a range
        ->and($frames['exile']['body'])->toContain('9,000 to 10,000')
        // the Hotel Lambert was Czartoryski's only from 1843
        ->and($frames['exile']['body'])->toContain('from 1843')
        ->and($frames['citadel']['body'])->toContain('26 February 1832')
        // the Citadel was charged to the city and the kingdom, not the city alone
        ->and($frames['citadel']['body'])->toContain('the city and the kingdom')
        ->and($frames['mourning']['body'])->toContain('between about 100 and 200')
        ->and($frames['forest']['body'])->toContain('14 January 1863')
        ->and($frames['forest']['body'])->toContain('22 January')
        ->and($frames['forest']['body'])->toContain('over 1,200')
        ->and($frames['forest']['body'])->toContain('22 March 1864')
        ->and($frames['aftermath']['body'])->toContain('5 August')
        ->and($frames['aftermath']['body'])->toContain('2 March 1864')
        ->and($frames['aftermath']['body'])->toContain('38,000')
        ->and($frames['aftermath']['body'])->toContain('Vistula Land')
        // the debate is on the page: the critics are dated, and so is the answer
        ->and($frames['aftermath']['body'])->toContain('1869')
        ->and($frames['aftermath']['body'])->toContain('Later historians')
        // fixes from the fact check: Raszyn was no repulse, Warsaw fell to Austria for a while
        ->and($frames['duchy']['body'])->toContain('struck back into Galicia')
        ->and($frames['duchy']['body'])->not->toContain('fought off')
        // one of the six generals, Nowicki, was killed by mistake, not for refusing
        ->and($frames['night']['body'])->toContain('most for refusing');

    // no poetic deaths: Ordon survived his redoubt, Sowinski did not die inside the church
    foreach (['en', 'pl'] as $locale) {
        $json = json_encode((require lang_path("{$locale}/site.php"))['uprisings']['frames'], JSON_UNESCAPED_UNICODE);

        expect($json)->not->toContain('Ordon')
            ->and($json)->not->toContain('Sowi');
    }
});

it('writes the uprisings names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.uprisings.frames'))->keyBy('key');

    expect($frames['duchy']['body'])->toContain('Księstwo Warszawskie')
        ->and($frames['duchy']['body'])->toContain('Tylży')
        ->and($frames['duchy']['body'])->toContain('Józef Poniatowski')
        ->and($frames['duchy']['body'])->toContain('Fryderyk August')
        ->and($frames['kingdom']['body'])->toContain('Wielkie Księstwo Poznańskie')
        ->and($frames['kingdom']['body'])->toContain('Królestwo Polskie')
        ->and($frames['kingdom']['body'])->toContain('wielki książę Konstanty')
        ->and($frames['night']['body'])->toContain('Mikołaj I')
        ->and($frames['night']['body'])->toContain('Szkoły Podchorążych Piechoty w Łazienkach')
        ->and($frames['night']['body'])->toContain('Belwederu')
        ->and($frames['war']['body'])->toContain('Iwan Dybicz')
        ->and($frames['war']['body'])->toContain('Olszynką Grochowską')
        ->and($frames['war']['body'])->toContain('Ostrołęką')
        ->and($frames['war']['body'])->toContain('Iwan Paskiewicz')
        ->and($frames['exile']['body'])->toContain('Wielka Emigracja')
        ->and($frames['exile']['body'])->toContain('Fryderyk Chopin')
        ->and($frames['citadel']['body'])->toContain('Statut Organiczny')
        ->and($frames['citadel']['body'])->toContain('X Pawilon')
        ->and($frames['mourning']['body'])->toContain('placu Zamkowym')
        ->and($frames['forest']['body'])->toContain('Tymczasowy Rząd Narodowy')
        ->and($frames['forest']['body'])->toContain('Konstanty Kalinowski')
        ->and($frames['forest']['body'])->toContain('Wilnie')
        ->and($frames['aftermath']['body'])->toContain('Krajem Przywiślańskim');

    app()->setLocale('en');
});

it('draws the map of 1815 for the uprisings article from the page translations', function (): void {
    $component = file_get_contents(resource_path('js/components/PartitionMap.vue'));
    $page = file_get_contents(resource_path('js/pages/HistoryUprisings.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);
    preg_match_all("#region: '([a-z_]+)'#", $component, $regions);

    expect($regions[1])->toContain('empire', 'kingdom', 'posen', 'galicia', 'free_city');

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['uprisings']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            // every town, every region and the Free City's own name exist for this page
            ->and(array_diff($keys[1], array_keys($map['labels'])))->toBe([])
            ->and(array_diff($regions[1], array_keys($map['labels'])))->toBe([])
            ->and($map['labels'])->toHaveKey('krakow_city');
    }

    expect(trans('site.uprisings.map.caption'))->toContain('Schematic')
        ->and(trans('site.uprisings.map.labels.kingdom'))->toBe('Kingdom of Poland')
        ->and(trans('site.uprisings.map.labels.kingdom', [], 'pl'))->toBe('Królestwo Polskie')
        // the page asks for the map of 1815, and the partition pages still do not
        ->and($page)->toContain('settlement')
        ->and(file_get_contents(resource_path('js/pages/HistoryThirdPartition.vue')))->not->toContain('settlement')
        ->and(file_get_contents(resource_path('js/pages/HistoryKosciuszko.vue')))->not->toContain('settlement');
});

it('renders the article on keeping a country without a country as a filmstrip', function (): void {
    $this->get('/history/keeping-a-country')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryKeeping')
            ->has('translations.keeping.frames', 9)
        );
});

it('credits every frame of the keeping article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['keeping']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/keeping-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/keeping-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/keeping-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/keeping-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['keeping']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/keeping-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.keeping.sources.body'))->toContain('Drzymala')
        ->and(trans('site.keeping.sources.body', [], 'pl'))->toContain('Drzymał');
});

it('uses pictures on the keeping article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/keeping-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'keeping-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the keeping article right after the uprisings, as the sixth partitions topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/keeping-a-country'))
        ->toBeGreaterThan(strpos($order, '/history/uprisings-1830-1863'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/keeping-a-country',\s+titleKey: 'keeping.meta.title',\s+era: 'partitions',\s+index: 5,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.keeping.hero.kicker'))->toContain(trans('site.history.eras.partitions.label'))
            ->and(trans('site.keeping.hero.kicker'))->toContain('06')
            ->and(trans('site.keeping.meta.title'))->toContain(trans('site.history.eras.partitions.topics.5.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/keeping-a-country')
        ->assertSee(e(trans('site.keeping.meta.title')), escape: false)
        ->assertSee('/images/cards/keeping.jpg', escape: false);
});

it('keeps every keeping frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['keeping']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the keeping article from the two exiles to the fall of the empires', function (): void {
    $frames = collect(trans('site.keeping.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // it picks up the two exiles the uprisings page names, and does not retell the risings
    expect($keys)->toBe(['chopin', 'bards', 'faiths', 'work', 'positivists', 'schools', 'galicia', 'prussia', 'nobel'])
        ->and($byKey['chopin']['body'])->toContain('uprisings topic named two exiles')
        ->and($byKey['bards']['body'])->toContain('The second exile')
        ->and($byKey['positivists']['body'])->toContain('After the defeat of 1864')
        // each partition is named before its policy is described
        ->and($byKey['work']['body'])->toContain('Prussian partition')
        ->and($byKey['schools']['body'])->toContain('Kingdom of Poland')
        ->and($byKey['galicia']['body'])->toContain('its share of Poland, Galicia')
        // the page ends on 1914 and 1918 and hands over to the next topic without telling it
        ->and($byKey['nobel']['body'])->toContain('1914')
        ->and($byKey['nobel']['body'])->toContain('1918')
        ->and($byKey['nobel']['body'])->toContain('next topic');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Fryderyk Chopin'))->toBe(array_search('chopin', $keys, true))
        ->and($firstMention('Jozef Elsner'))->toBe(array_search('chopin', $keys, true))
        ->and($firstMention('Robert Schumann'))->toBe(array_search('chopin', $keys, true))
        ->and($firstMention('Adam Mickiewicz'))->toBe(array_search('bards', $keys, true))
        ->and($firstMention('Philomaths'))->toBe(array_search('bards', $keys, true))
        ->and($firstMention('Otto von Bismarck'))->toBe(array_search('faiths', $keys, true))
        ->and($firstMention('Mieczyslaw Ledochowski'))->toBe(array_search('faiths', $keys, true))
        ->and($firstMention('Dov Ber Meisels'))->toBe(array_search('faiths', $keys, true))
        ->and($firstMention('Karol Marcinkowski'))->toBe(array_search('work', $keys, true))
        ->and($firstMention('Hipolit Cegielski'))->toBe(array_search('work', $keys, true))
        ->and($firstMention('Piotr Wawrzyniak'))->toBe(array_search('work', $keys, true))
        ->and($firstMention('Boleslaw Prus'))->toBe(array_search('positivists', $keys, true))
        ->and($firstMention('Eliza Orzeszkowa'))->toBe(array_search('positivists', $keys, true))
        ->and($firstMention('Alexander Apukhtin'))->toBe(array_search('schools', $keys, true))
        ->and($firstMention('Jadwiga Szczawinska-Dawidowa'))->toBe(array_search('schools', $keys, true))
        ->and($firstMention('Maria Sklodowska'))->toBe(array_search('schools', $keys, true))
        ->and($firstMention('Jan Matejko'))->toBe(array_search('galicia', $keys, true))
        ->and($firstMention('Michal Drzymala'))->toBe(array_search('prussia', $keys, true))
        ->and($firstMention('Henryk Sienkiewicz'))->toBe(array_search('nobel', $keys, true));
});

it('keeps the hard-won keeping facts the way the sources give them', function (): void {
    $frames = collect(trans('site.keeping.frames'))->keyBy('key');

    expect($frames['chopin']['body'])->toContain('2 November 1830')
        // the heart went into the pillar in 1880, not 1879, and was hidden in 1944-1945
        ->and($frames['chopin']['body'])->toContain('since 1880')
        ->and($frames['chopin']['body'])->toContain('1944-1945')
        ->and($frames['chopin']['body'])->toContain('cannons buried in flowers')
        ->and($frames['bards']['body'])->toContain('1834')
        ->and($frames['bards']['body'])->toContain('Lithuania, my fatherland')
        // "Christ of nations" is a later label, not Mickiewicz's words, so it is not quoted
        ->and($frames['bards']['body'])->not->toContain('Christ of nations')
        ->and($frames['faiths']['body'])->toContain('1875')
        ->and($frames['faiths']['body'])->toContain('1874')
        ->and($frames['faiths']['body'])->toContain('never only Catholic')
        // the Bazar was founded in 1838 and opened in stages, so no single opening year
        ->and($frames['work']['body'])->toContain('early 1840s')
        ->and($frames['work']['body'])->toContain('1846')
        ->and($frames['work']['body'])->toContain('1891')
        ->and($frames['positivists']['body'])->toContain('1866')
        // under Apukhtin only religion stayed Polish; the claim that Polish was taught in Russian had no source
        ->and($frames['schools']['body'])->toContain('only religion')
        ->and($frames['schools']['body'])->not->toContain('taught in Russian')
        ->and($frames['schools']['body'])->toContain('An estimated 5,000')
        ->and($frames['galicia']['body'])->toContain('1870')
        ->and($frames['galicia']['body'])->toContain('Ruthenians')
        // German became the language of Prussian schools in 1873, not 1872
        ->and($frames['prussia']['body'])->toContain('From 1873')
        ->and($frames['prussia']['body'])->toContain('from 1887')
        ->and($frames['prussia']['body'])->toContain('14 were flogged')
        // the 1906-1907 strike figures run from 70,000 to 93,000, so the page says it is an estimate
        ->and($frames['prussia']['body'])->toContain('by the most common estimate')
        ->and($frames['prussia']['body'])->toContain('every day')
        ->and($frames['nobel']['body'])->toContain('1884-1888')
        ->and($frames['nobel']['body'])->toContain('1905')
        ->and($frames['nobel']['body'])->toContain('after the country of origin of one of us');

    // Chopin is in one of two known photographs, not the only one
    expect(trans('site.keeping.frames.0.caption'))->toContain('one of only two');

    app()->setLocale('pl');
    $polish = collect(trans('site.keeping.frames'))->keyBy('key');

    // Sienkiewicz wrote "dla pokrzepienia serc", not the popular "ku pokrzepieniu serc"
    expect($polish['nobel']['body'])->toContain('dla pokrzepienia serc')
        ->and($polish['nobel']['body'])->not->toContain('ku pokrzepieniu');

    app()->setLocale('en');
});

it('writes the keeping names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.keeping.frames'))->keyBy('key');

    expect($frames['chopin']['body'])->toContain('Fryderyk Chopin')
        ->and($frames['chopin']['body'])->toContain('Józef Elsner')
        ->and($frames['chopin']['body'])->toContain('kościoła Świętego Krzyża')
        ->and($frames['bards']['body'])->toContain('Nowogródka')
        ->and($frames['bards']['body'])->toContain('filomatów')
        ->and($frames['bards']['body'])->toContain('Litwo! Ojczyzno moja!')
        ->and($frames['bards']['body'])->toContain('wieszczami')
        ->and($frames['faiths']['body'])->toContain('Mieczysława Ledóchowskiego')
        ->and($frames['faiths']['body'])->toContain('Dow Bera Meiselsa')
        ->and($frames['work']['body'])->toContain('pracą organiczną')
        ->and($frames['positivists']['body'])->toContain('pracę u podstaw')
        ->and($frames['positivists']['body'])->toContain('Lalce')
        ->and($frames['schools']['body'])->toContain('Uniwersytet Latający')
        ->and($frames['schools']['body'])->toContain('Maria Skłodowska')
        ->and($frames['galicia']['body'])->toContain('Akademię Umiejętności')
        ->and($frames['prussia']['body'])->toContain('Września')
        ->and($frames['prussia']['body'])->toContain('Komisja Kolonizacyjna')
        ->and($frames['prussia']['body'])->toContain('Michałowi Drzymale')
        ->and($frames['nobel']['body'])->toContain('Trylogię');

    app()->setLocale('en');
});

it('renders the article on three empires and three cities as a filmstrip', function (): void {
    $this->get('/history/three-empires-three-cities')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryThreeEmpires')
            ->has('translations.empires.frames', 9)
        );
});

it('credits every frame of the three empires article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['empires']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/empires-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/empires-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/empires-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/empires-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['empires']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/empires-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.empires.sources.body'))->toContain('Title picture')
        ->and(trans('site.empires.sources.body'))->toContain('1903-1910')
        // the one picture under a share-alike licence says so
        ->and(trans('site.empires.frames.8.credit'))->toContain('CC BY-SA 4.0');
});

it('uses pictures on the three empires article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/empires-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'empires-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the three empires article right after the keeping article, as the seventh partitions topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/three-empires-three-cities'))
        ->toBeGreaterThan(strpos($order, '/history/keeping-a-country'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/three-empires-three-cities',\s+titleKey: 'empires.meta.title',\s+era: 'partitions',\s+index: 6,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.empires.hero.kicker'))->toContain(trans('site.history.eras.partitions.label'))
            ->and(trans('site.empires.hero.kicker'))->toContain('07')
            ->and(trans('site.empires.meta.title'))->toContain(trans('site.history.eras.partitions.topics.6.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/three-empires-three-cities')
        ->assertSee(e(trans('site.empires.meta.title')), escape: false)
        ->assertSee('/images/cards/empires.jpg', escape: false);
});

it('keeps every three empires frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['empires']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the three empires article from 1914 back to the partitions and on to 1918', function (): void {
    $frames = collect(trans('site.empires.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    // it picks up where the keeping article stops, in 1914, and goes back to what was built
    expect($keys)->toBe(['corner', 'fortress', 'castle', 'planty', 'wawel', 'galicia', 'cathedral', 'gauge', 'ghost'])
        ->and($byKey['corner']['body'])->toContain('The last topic ended')
        ->and($byKey['corner']['body'])->toContain('1914')
        // each city is placed in its partition before its buildings are described
        ->and($byKey['fortress']['body'])->toContain('came back to Prussia in 1815')
        ->and($byKey['planty']['body'])->toContain('Austrian for good from 1846')
        ->and($byKey['cathedral']['body'])->toContain('Kingdom of Poland')
        // the last frame hands over to 1918 without retelling it
        ->and($byKey['ghost']['body'])->toContain('November 1918')
        // what the keeping article told is not told again
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Drzymala')
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Wrzesnia');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Wilhelm II, German Emperor'))->toBe(array_search('castle', $keys, true))
        ->and($firstMention('the town planner Joseph Stubben'))->toBe(array_search('castle', $keys, true))
        ->and($firstMention('the architect Franz Schwechten'))->toBe(array_search('castle', $keys, true))
        ->and($firstMention('the German chancellor Otto von Bismarck'))->toBe(array_search('corner', $keys, true))
        ->and($firstMention('Settlement Commission, which bought land'))->toBe(array_search('castle', $keys, true))
        ->and($firstMention('Stanislaw Szczepanowski, an oil industrialist'))->toBe(array_search('galicia', $keys, true))
        ->and($firstMention('The historian Wlodzimierz Suleja'))->toBe(array_search('galicia', $keys, true))
        ->and($firstMention('the architect Leonty Benois'))->toBe(array_search('cathedral', $keys, true))
        ->and($firstMention('Marshal Jozef Pilsudski, the first head'))->toBe(array_search('cathedral', $keys, true))
        ->and($firstMention('the engineer Stanislaw Kierbedz'))->toBe(array_search('gauge', $keys, true))
        ->and($firstMention('the economists Irena Grosfeld and Ekaterina Zhuravskaya'))->toBe(array_search('ghost', $keys, true));
});

it('keeps the hard-won three empires facts the way the sources give them', function (): void {
    $frames = collect(trans('site.empires.frames'))->keyBy('key');

    expect($frames['corner']['body'])->toContain('From 1846')
        ->and($frames['corner']['body'])->toContain('Black and the White Przemsza')
        // the calendar gap was 12 days before 1900 and 13 after
        ->and($frames['corner']['body'])->toContain('12 or 13 days')
        ->and($frames['fortress']['body'])->toContain('1828 to 1842')
        ->and($frames['fortress']['body'])->toContain('Between 1876 and 1896')
        ->and($frames['fortress']['body'])->toContain('18 forts')
        ->and($frames['fortress']['body'])->toContain('In 1939 the German occupiers')
        ->and($frames['castle']['body'])->toContain('In 1902')
        ->and($frames['castle']['body'])->toContain('1905-1910')
        ->and($frames['castle']['body'])->toContain('residence for Hitler')
        // the Planty were laid out by the Free City, not by Austria
        ->and($frames['planty']['body'])->toContain('1822-1830 the Free City')
        ->and($frames['planty']['body'])->toContain('Lviv')
        ->and($frames['planty']['body'])->toContain('1901')
        ->and($frames['wawel']['body'])->toContain('1850-1854')
        ->and($frames['wawel']['body'])->toContain('Only in 1905, after Poles had raised the money')
        ->and($frames['wawel']['body'])->toContain('more than a hundred')
        // the figures are Szczepanowski's own count, and the page says so
        ->and($frames['galicia']['body'])->toContain('By his count')
        ->and($frames['galicia']['body'])->toContain('10 kilograms')
        ->and($frames['galicia']['body'])->toContain('27 years')
        // emigration estimates differ between historians, so the count is attributed
        ->and($frames['galicia']['body'])->toContain('Suleja counts some 350,000')
        ->and($frames['cathedral']['body'])->toContain('In 1894')
        ->and($frames['cathedral']['body'])->toContain('1912')
        ->and($frames['cathedral']['body'])->toContain('1924-1926')
        // the sources give 70 and 73 metres, so the page says about 70
        ->and($frames['cathedral']['body'])->toContain('about 70 metres')
        ->and($frames['cathedral']['body'])->toContain('some 2,500')
        ->and($frames['gauge']['body'])->toContain('from 1845')
        ->and($frames['gauge']['body'])->toContain('1862')
        ->and($frames['gauge']['body'])->toContain('1864')
        ->and($frames['gauge']['body'])->toContain('1875')
        ->and($frames['gauge']['body'])->toContain('33 square kilometres')
        // the direct Warsaw-Poznan line is dated 1921 by one source and 1922 by another
        ->and($frames['ghost']['body'])->toContain('early 1920s')
        ->and($frames['ghost']['body'])->toContain('In 2015')
        // the border explains part of the pattern, and the page says so
        ->and($frames['ghost']['body'])->toContain('not all of it')
        ->and($frames['ghost']['body'])->toContain('after 1945')
        // fixes from the fact check: who bought Wawel back, and the 1945 shift explained
        ->and($frames['wawel']['body'])->toContain('Poles had raised the money')
        ->and($frames['ghost']['body'])->toContain('former German lands became Polish')
        // the citadel's start and the tower's year rest on one source each, so they are not given
        ->and($frames['wawel']['body'])->not->toContain('From 1849')
        ->and($frames['corner']['body'])->not->toContain('1907')
        // not every one of the Poznan forts survives, so the page does not say most do
        ->and($frames['fortress']['body'])->toContain('Many of them still stand');
});

it('writes the three empires names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.empires.frames'))->keyBy('key');

    expect($frames['corner']['body'])->toContain('Mysłowicami')
        ->and($frames['corner']['body'])->toContain('Czarna Przemsza')
        ->and($frames['corner']['body'])->toContain('kalendarza juliańskiego')
        ->and($frames['fortress']['body'])->toContain('Wielkiego Księstwa Poznańskiego')
        ->and($frames['fortress']['body'])->toContain('Fort Winiary')
        ->and($frames['castle']['body'])->toContain('Komisji Kolonizacyjnej')
        ->and($frames['castle']['body'])->toContain('Akademia Królewska')
        ->and($frames['planty']['body'])->toContain('Bramę Floriańską')
        ->and($frames['planty']['body'])->toContain('Sejm Krajowy')
        ->and($frames['planty']['body'])->toContain('Pałac Sztuki')
        ->and($frames['wawel']['body'])->toContain('krużganki')
        ->and($frames['wawel']['body'])->toContain('Wawel Odzyskany')
        ->and($frames['galicia']['body'])->toContain('Nędzę Galicji w cyfrach')
        ->and($frames['galicia']['body'])->toContain('Włodzimierz Suleja')
        ->and($frames['cathedral']['body'])->toContain('soboru św. Aleksandra Newskiego')
        ->and($frames['cathedral']['body'])->toContain('placu Saskiego')
        ->and($frames['cathedral']['body'])->toContain('Józefa Piłsudskiego')
        ->and($frames['gauge']['body'])->toContain('Stanisława Kierbedzia')
        ->and($frames['gauge']['body'])->toContain('Cytadelą');

    app()->setLocale('en');
});

it('draws the three partitions around 1900 for the three empires article from the page translations', function (): void {
    $component = file_get_contents(resource_path('js/components/PartitionMap.vue'));
    $page = file_get_contents(resource_path('js/pages/HistoryThreeEmpires.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);
    preg_match_all("#region: '([a-z_]+)'#", $component, $regions);

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['empires']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            // every town, every region and the corner have a label on this page
            ->and(array_diff($keys[1], array_keys($map['labels'])))->toBe([])
            ->and(array_diff($regions[1], array_keys($map['labels'])))->toBe([])
            ->and($map['labels'])->toHaveKey('corner');
    }

    expect(trans('site.empires.map.caption'))->toContain('Schematic')
        ->and(trans('site.empires.map.labels.kingdom'))->toBe('Russian partition')
        ->and(trans('site.empires.map.labels.kingdom', [], 'pl'))->toBe('Zabór rosyjski')
        ->and($component)->toContain('map.labels.corner')
        // the page asks for the map of about 1900, and the older pages still do not
        ->and($page)->toMatch('#<PartitionMap[^>]*\sempires\s#')
        ->and(file_get_contents(resource_path('js/pages/HistoryUprisings.vue')))->not->toMatch('#\sempires\s*/?>#')
        ->and(file_get_contents(resource_path('js/pages/HistoryThirdPartition.vue')))->not->toContain('empires');
});

it('renders the article on 1918 and the Second Republic as a filmstrip', function (): void {
    $this->get('/history/second-republic-1918')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistorySecondRepublic')
            ->has('translations.republic.frames', 9)
        );
});

it('credits every frame of the Second Republic article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['republic']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/republic-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/republic-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/republic-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/republic-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['republic']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/republic-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.republic.sources.body'))->toContain('Title picture')
        ->and(trans('site.republic.sources.body'))->toContain('February 1919')
        // the one picture under a share-alike licence says so
        ->and(collect(trans('site.republic.frames'))->keyBy('key')['marks']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the Second Republic article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/republic-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'republic-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the Second Republic article after the three empires and before the war, as the first wars topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/second-republic-1918'))
        ->toBeGreaterThan(strpos($order, '/history/three-empires-three-cities'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/second-republic-1918',\s+titleKey: 'republic.meta.title',\s+era: 'wars',\s+index: 0,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.republic.hero.kicker'))->toContain(trans('site.history.eras.wars.label'))
            ->and(trans('site.republic.hero.kicker'))->toContain('01')
            ->and(trans('site.republic.meta.title'))->toContain(trans('site.history.eras.wars.topics.0.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/second-republic-1918')
        ->assertSee(e(trans('site.republic.meta.title')), escape: false)
        ->assertSee('/images/cards/republic.jpg', escape: false);
});

it('keeps every Second Republic frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['republic']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Second Republic article from November 1918 back to 1914 and on to 1939', function (): void {
    $frames = collect(trans('site.republic.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['legions', 'paris', 'november', 'lwow', 'silesia', 'marks', 'minorities', 'coup', 'gdynia'])
        // it picks up where the three empires article stops
        ->and($byKey['legions']['body'])->toContain('The last topic ended')
        ->and($byKey['legions']['body'])->toContain('November 1918')
        // the Battle of Warsaw is the next topic, so it is pointed at, not told
        ->and($byKey['lwow']['body'])->toContain('has a topic of its own')
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Miracle on the Vistula')
        // the last frame hands over to the war page without telling it
        ->and($byKey['gdynia']['body'])->toContain('On 1 September Nazi Germany invaded')
        ->and($byKey['gdynia']['body'])->toContain('17 September the Soviet Union')
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Westerplatte');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Jozef Pilsudski, a socialist'))->toBe(array_search('legions', $keys, true))
        ->and($firstMention('Roman Dmowski, leader of the nationalist National Democrats'))->toBe(array_search('paris', $keys, true))
        ->and($firstMention('General Jozef Haller'))->toBe(array_search('paris', $keys, true))
        ->and($firstMention('the US president, Woodrow Wilson'))->toBe(array_search('paris', $keys, true))
        ->and($firstMention('the Regency Council, three Poles'))->toBe(array_search('november', $keys, true))
        ->and($firstMention('the pianist Ignacy Jan Paderewski'))->toBe(array_search('silesia', $keys, true))
        ->and($firstMention('the prime minister, Wladyslaw Grabski'))->toBe(array_search('marks', $keys, true))
        ->and($firstMention('the first president, Gabriel Narutowicz'))->toBe(array_search('minorities', $keys, true))
        ->and($firstMention('a painter, Eligiusz Niewiadomski'))->toBe(array_search('minorities', $keys, true))
        ->and($firstMention('President Stanislaw Wojciechowski'))->toBe(array_search('coup', $keys, true))
        ->and($firstMention('the peasant leader Wincenty Witos'))->toBe(array_search('coup', $keys, true))
        ->and($firstMention('Eugeniusz Kwiatkowski, minister of industry'))->toBe(array_search('gdynia', $keys, true))
        // Pilsudski is locked in Magdeburg before he is freed from it
        ->and($firstMention('Magdeburg'))->toBeLessThan(array_search('november', $keys, true));
});

it('keeps the hard-won Second Republic facts the way the sources give them', function (): void {
    $frames = collect(trans('site.republic.frames'))->keyBy('key');

    // the death toll of the war is an estimate, and the page gives the range
    expect($frames['legions']['body'])->toContain('from about 400,000 to over half a million')
        ->and($frames['legions']['body'])->toContain('6 August')
        // some 40,000 passed through the Legions; 25,000 was never the total
        ->and($frames['legions']['body'])->toContain('Some 40,000')
        // the oath was brotherhood in arms, not an oath to the Kaiser
        ->and($frames['paris']['body'])->toContain('brotherhood in arms')
        ->and($frames['paris']['body'])->not->toContain('Kaiser')
        ->and($frames['paris']['body'])->toContain('nearly 70,000')
        ->and($frames['paris']['body'])->toContain('8 January 1918')
        ->and($frames['paris']['body'])->toContain('13th of his Fourteen Points')
        // independence was declared on 7 October; 11 November is the day of the command
        ->and($frames['november']['body'])->toContain('On 7 October')
        ->and($frames['november']['body'])->toContain('10 November')
        ->and($frames['november']['body'])->toContain('14 November')
        ->and($frames['november']['body'])->toContain('1937')
        ->and($frames['november']['body'])->toContain('1945')
        ->and($frames['november']['body'])->toContain('1989')
        ->and($frames['november']['body'])->toContain('28 November')
        // the pogrom's toll differs between sources, so the page gives the range
        ->and($frames['lwow']['body'])->toContain('at least 50 Jews, probably 70 to 100')
        ->and($frames['lwow']['body'])->toContain('by 22 November')
        ->and($frames['lwow']['body'])->toContain('July 1919')
        ->and($frames['silesia']['body'])->toContain('27 December 1918')
        ->and($frames['silesia']['body'])->toContain('28 June 1919')
        ->and($frames['silesia']['body'])->toContain('20 March 1921')
        ->and($frames['silesia']['body'])->toContain('about 60 per cent')
        // Poland got 29 per cent of the land, so the page speaks of the industry, not a third
        ->and($frames['silesia']['body'])->toContain('most of the industry')
        ->and($frames['marks']['body'])->toContain('17 March 1921')
        ->and($frames['marks']['body'])->toContain('more than nine million marks')
        ->and($frames['marks']['body'])->toContain('1,800,000')
        // 1921 counted nationality, 1931 mother tongue, and the page says which
        ->and($frames['minorities']['body'])->toContain('69 per cent')
        ->and($frames['minorities']['body'])->toContain('mother tongue')
        // Yiddish and Hebrew together were 8.6 per cent, not 9.8
        ->and($frames['minorities']['body'])->toContain('9 per cent Yiddish or Hebrew')
        ->and($frames['minorities']['body'])->toContain('from 1937')
        ->and($frames['coup']['body'])->toContain('12 May 1926')
        ->and($frames['coup']['body'])->toContain('about 380')
        ->and($frames['coup']['body'])->toContain('12 May 1935')
        ->and($frames['coup']['body'])->toContain('Belweder')
        ->and($frames['coup']['body'])->toContain('Wawel')
        ->and($frames['gdynia']['body'])->toContain('1,300')
        ->and($frames['gdynia']['body'])->toContain('about 127,000')
        ->and($frames['gdynia']['body'])->toContain('by cargo')
        ->and($frames['gdynia']['body'])->toContain('2026');
});

it('writes the Second Republic names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.republic.frames'))->keyBy('key');

    expect($frames['legions']['body'])->toContain('Józefa Piłsudskiego')
        ->and($frames['legions']['body'])->toContain('Legiony Polskie')
        ->and($frames['paris']['body'])->toContain('Komitetem Narodowym Polskim')
        ->and($frames['paris']['body'])->toContain('Józefa Hallera')
        ->and($frames['november']['body'])->toContain('Rada Regencyjna')
        ->and($frames['lwow']['body'])->toContain('Lwowie')
        ->and($frames['silesia']['body'])->toContain('Ignacego Jana Paderewskiego')
        ->and($frames['silesia']['body'])->toContain('Wolnym Miastem')
        ->and($frames['marks']['body'])->toContain('Władysław Grabski')
        ->and($frames['minorities']['body'])->toContain('Gabriela Narutowicza')
        ->and($frames['minorities']['body'])->toContain('Eligiusz Niewiadomski')
        ->and($frames['coup']['body'])->toContain('moście Poniatowskiego')
        ->and($frames['coup']['body'])->toContain('sanacją')
        ->and($frames['gdynia']['body'])->toContain('Centralny Okręg Przemysłowy')
        ->and($frames['gdynia']['body'])->toContain('Stalowej Woli');

    app()->setLocale('en');
});

it('draws Poland in 1922 with the old partitions for the Second Republic article from the page translations', function (): void {
    $component = file_get_contents(resource_path('js/components/RepublicMap.vue'));
    $page = file_get_contents(resource_path('js/pages/HistorySecondRepublic.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);
    preg_match_all("#power: '([a-z_]+)'#", $component, $powers);

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['republic']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            // every town, neighbour and partition drawn has a label on this page
            ->and(array_diff($keys[1], array_keys($map['labels'])))->toBe([])
            ->and(array_diff($powers[1], array_keys($map['labels'])))->toBe([]);
    }

    expect(file_get_contents(resource_path('js/pages/HistorySecondRepublic.vue')))->toContain('republic-atlas1920')
        ->and(trans('site.republic.map.caption'))->toContain('Schematic')
        ->and(trans('site.republic.map.labels.russia'))->toBe('Russian partition')
        ->and(trans('site.republic.map.labels.russia', [], 'pl'))->toBe('Zabór rosyjski')
        ->and($page)->toContain('republic-atlas1920')
        // the partition map is left as it was for the pages that use it
        ->and($page)->not->toContain('PartitionMap');
});

it('keeps the fixes from the Second Republic fact and language checks', function (): void {
    $frames = collect(trans('site.republic.frames'))->keyBy('key');

    // the oath bound the Legions to both Central Powers, not to Germany alone
    expect($frames['paris']['body'])->toContain('the German and Austrian armies')
        // the Ukrainian side has a purpose, not only a verb
        ->and($frames['lwow']['body'])->toContain('capital of a new Ukrainian state')
        // the rising is carried by insurgents, not by the city
        ->and($frames['silesia']['body'])->toContain('insurgents held most of the province')
        ->and($frames['silesia']['body'])->toContain('Silesian Parliament')
        // the 1930 pacification names who it answered
        ->and($frames['minorities']['body'])->toContain('underground Ukrainian nationalists')
        // after Witos resigns, the next subject is named again, not left to a pronoun
        ->and($frames['coup']['body'])->toContain('Pilsudski never became president')
        ->and($frames['coup']['body'])->toContain('the peasant leader Wincenty Witos')
        ->and(trans('site.republic.frames.7.body', [], 'pl'))->toContain('Piłsudski prezydentem nie został')
        ->and(trans('site.republic.frames.6.body', [], 'pl'))->toContain('getto ławkowe');
});

it('renders the article on the Battle of Warsaw as a filmstrip', function (): void {
    $this->get('/history/battle-of-warsaw-1920')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryWarsaw1920')
            ->has('translations.war1920.frames', 10)
        );
});

it('credits every frame of the Battle of Warsaw article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['war1920']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/war1920-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/war1920-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/war1920-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/war1920-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['war1920']['frames'])->pluck('photo')->unique())->toHaveCount(10);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/war1920-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.war1920.sources.body'))->toContain('Title picture')
        ->and(trans('site.war1920.sources.body'))->toContain('machine-gun crew')
        // the one picture under a share-alike licence says so
        ->and(collect(trans('site.war1920.frames'))->keyBy('key')['memory']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the Battle of Warsaw article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/war1920-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'war1920-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(12)
        ->and($ours->unique())->toHaveCount(12)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the Battle of Warsaw article after the Second Republic, as the second wars topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/battle-of-warsaw-1920'))
        ->toBeGreaterThan(strpos($order, '/history/second-republic-1918'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/battle-of-warsaw-1920',\s+titleKey: 'war1920.meta.title',\s+era: 'wars',\s+index: 1,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.war1920.hero.kicker'))->toContain(trans('site.history.eras.wars.label'))
            ->and(trans('site.war1920.hero.kicker'))->toContain('02')
            ->and(trans('site.war1920.meta.title'))->toContain(trans('site.history.eras.wars.topics.1.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/battle-of-warsaw-1920')
        ->assertSee(e(trans('site.war1920.meta.title')), escape: false)
        ->assertSee('/images/cards/war1920.jpg', escape: false);
});

it('keeps every Battle of Warsaw frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['war1920']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Battle of Warsaw article from the Second Republic to the peace of 1921', function (): void {
    $frames = collect(trans('site.war1920.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['vacuum', 'ideas', 'kyiv', 'advance', 'defence', 'codes', 'radzymin', 'wieprz', 'riga', 'memory'])
        // it picks up the war the previous topic pointed at but did not tell
        ->and($byKey['vacuum']['body'])->toContain('The last topic')
        ->and($byKey['vacuum']['body'])->toContain('November 1918')
        // the battle has a beginning, a turn and an end, in that order
        ->and($byKey['radzymin']['body'])->toContain('13 August')
        ->and($byKey['wieprz']['body'])->toContain('16 August')
        ->and($byKey['riga']['body'])->toContain('18 March 1921')
        // the miracle is explained where it belongs, not asserted as a fact earlier
        ->and($frames->take(9)->pluck('body')->implode(' '))->not->toContain('miracle')
        ->and($byKey['memory']['body'])->toContain('miracle on the Vistula');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Lenin, the Bolshevik leader in Moscow'))->toBe(array_search('vacuum', $keys, true))
        ->and($firstMention('Jozef Pilsudski, the socialist commander'))->toBe(array_search('ideas', $keys, true))
        ->and($firstMention('Roman Dmowski, leader of the nationalist National Democrats'))->toBe(array_search('ideas', $keys, true))
        ->and($firstMention('Symon Petliura, head of the Ukrainian People'))->toBe(array_search('kyiv', $keys, true))
        ->and($firstMention('Mikhail Tukhachevsky, the 27-year-old commander'))->toBe(array_search('advance', $keys, true))
        ->and($firstMention('Lord Curzon'))->toBe(array_search('advance', $keys, true))
        ->and($firstMention('General Jozef Haller, who had led the Polish army raised in France'))->toBe(array_search('defence', $keys, true))
        ->and($firstMention('Wincenty Witos, a working farmer'))->toBe(array_search('defence', $keys, true))
        ->and($firstMention('Jan Kowalewski'))->toBe(array_search('codes', $keys, true))
        ->and($firstMention('Father Ignacy Skorupka'))->toBe(array_search('radzymin', $keys, true))
        ->and($firstMention('General Wladyslaw Sikorski'))->toBe(array_search('radzymin', $keys, true))
        ->and($firstMention('General Lucjan Zeligowski'))->toBe(array_search('riga', $keys, true))
        ->and($firstMention('General Tadeusz Rozwadowski'))->toBe(array_search('memory', $keys, true));
});

it('keeps the hard-won Battle of Warsaw facts the way the sources give them', function (): void {
    $frames = collect(trans('site.war1920.frames'))->keyBy('key');

    // the war was never declared, and the sources differ on its first day
    expect($frames['vacuum']['body'])->toContain('Nobody declared war')
        ->and($frames['vacuum']['body'])->toContain('usually dated to February 1919')
        ->and($frames['ideas']['body'])->toContain('March 1920')
        // the alliance with Petliura, the offensive and the collapse
        ->and($frames['kyiv']['body'])->toContain('21 April 1920')
        ->and($frames['kyiv']['body'])->toContain('25 April')
        ->and($frames['kyiv']['body'])->toContain('7 May')
        ->and($frames['kyiv']['body'])->toContain('some 20,000 men')
        ->and($frames['kyiv']['body'])->toContain('5 June')
        // the order was read to the troops on 2 July, two days before the offensive
        ->and($frames['advance']['body'])->toContain('2 July 1920')
        ->and($frames['advance']['body'])->toContain('over the corpse of White Poland')
        ->and($frames['advance']['body'])->toContain('14 July')
        // the volunteers were counted, and the count is not rounded up
        ->and($frames['defence']['body'])->toContain('1 July')
        ->and($frames['defence']['body'])->toContain('105,714')
        ->and($frames['defence']['body'])->toContain('24 July')
        ->and($frames['defence']['body'])->toContain('35 million rifle cartridges')
        // the ciphers went first, in September 1919, and the radio station a year later
        ->and($frames['codes']['body'])->toContain('September 1919')
        ->and($frames['codes']['body'])->toContain('13 August')
        ->and($frames['codes']['body'])->toContain('Ciechanow')
        ->and($frames['radzymin']['body'])->toContain('25 kilometres')
        ->and($frames['radzymin']['body'])->toContain('236th Volunteer Regiment')
        ->and($frames['radzymin']['body'])->toContain('14 August')
        // the counter-attack and the losses, given as the estimate they are
        ->and($frames['wieprz']['body'])->toContain('45 kilometres')
        ->and($frames['wieprz']['body'])->toContain('The usual estimate')
        ->and($frames['wieprz']['body'])->toContain('25,000')
        ->and($frames['wieprz']['body'])->toContain('66,000')
        ->and($frames['wieprz']['body'])->toContain('East Prussia')
        ->and($frames['riga']['body'])->toContain('20 to 26 September')
        ->and($frames['riga']['body'])->toContain('18 October')
        ->and($frames['riga']['body'])->toContain('1922')
        // prisoners died on both sides, and the page says so
        ->and($frames['riga']['body'])->toContain('prisoners died in thousands')
        // saving Europe is d'Abernon's claim, not this page's
        ->and($frames['memory']['body'])->toContain("d'Abernon")
        ->and($frames['memory']['body'])->toContain('Historians differ')
        ->and($frames['memory']['body'])->toContain('15 August')
        ->and($frames['memory']['body'])->toContain('Ossow');

    // the page never asserts that the battle saved Europe
    expect(collect(trans('site.war1920.frames'))->pluck('body')->implode(' '))
        ->not->toContain('saved Europe');
});

it('writes the Battle of Warsaw names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.war1920.frames'))->keyBy('key');

    expect($frames['ideas']['body'])->toContain('Józef Piłsudski')
        ->and($frames['ideas']['body'])->toContain('Roman Dmowski')
        ->and($frames['kyiv']['body'])->toContain('Symonem Petlurą')
        ->and($frames['kyiv']['body'])->toContain('Siemiona Budionnego')
        ->and($frames['advance']['body'])->toContain('Michaił Tuchaczewski')
        ->and($frames['advance']['body'])->toContain('trupa białej Polski')
        ->and($frames['defence']['body'])->toContain('Radę Obrony Państwa')
        ->and($frames['defence']['body'])->toContain('Rząd Obrony Narodowej')
        ->and($frames['defence']['body'])->toContain('Wincenty Witos')
        ->and($frames['codes']['body'])->toContain('Jan Kowalewski')
        ->and($frames['codes']['body'])->toContain('Wacława Sierpińskiego')
        ->and($frames['radzymin']['body'])->toContain('Ignacy Skorupka')
        ->and($frames['radzymin']['body'])->toContain('Władysława Sikorskiego')
        ->and($frames['wieprz']['body'])->toContain('znad Wieprza')
        ->and($frames['riga']['body'])->toContain('Lucjan Żeligowski')
        ->and($frames['riga']['body'])->toContain('Rydze')
        ->and($frames['memory']['body'])->toContain('Stanisław Stroński')
        ->and($frames['memory']['body'])->toContain('Święto Wojska Polskiego');

    app()->setLocale('en');
});

it('draws the campaign of 1920 for the Battle of Warsaw article from the page translations', function (): void {
    $component = file_get_contents(resource_path('js/components/War1920Map.vue'));
    $page = file_get_contents(resource_path('js/pages/HistoryWarsaw1920.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['war1920']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            // every town and arrow drawn has a label on this page
            ->and(array_diff($keys[1], array_keys($map['labels'])))->toBe([]);
    }

    expect(file_get_contents(resource_path('js/pages/HistoryWarsaw1920.vue')))->toContain('war1920-sketch')
        ->and(trans('site.war1920.map.caption'))->toContain('Schematic')
        ->and(trans('site.war1920.map.labels.ossow'))->toBe('Ossow')
        ->and(trans('site.war1920.map.labels.ossow', [], 'pl'))->toBe('Ossów')
        ->and(trans('site.war1920.map.labels.kyiv', [], 'pl'))->toBe('Kijów')
        ->and($page)->toContain('war1920-sketch')
        // the map of the Second Republic is left alone for the page that uses it
        ->and($page)->not->toContain('RepublicMap');
});

it('keeps the fixes from the Battle of Warsaw fact check in both languages', function (): void {
    $frames = collect(trans('site.war1920.frames'))->keyBy('key');

    // every actor arrives with a role, including the ones added late
    expect($frames['kyiv']['body'])->toContain('First Cavalry Army of Semyon Budyonny')
        ->and($frames['codes']['body'])->toContain('a lieutenant of 27, Jan Kowalewski')
        // the Bolsheviks had a government ready for Poland, and the page says whose
        ->and($frames['advance']['body'])->toContain('Feliks Dzierzynski')
        ->and($frames['advance']['body'])->toContain('23 July')
        // the Ukrainian ally is followed to the end, not dropped after the spring
        ->and($frames['riga']['body'])->toContain('Petliura');

    expect(trans('site.war1920.frames.2.body', [], 'pl'))->toContain('1 Armia Konna Siemiona Budionnego')
        ->and(trans('site.war1920.frames.3.body', [], 'pl'))->toContain('Feliksem Dzierżyńskim')
        ->and(trans('site.war1920.frames.8.body', [], 'pl'))->toContain('Ukrainę Petlury');
});

it('renders the article on the moved borders as a filmstrip', function (): void {
    $this->get('/history/the-borders-moved')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryBorders')
            ->has('translations.borders.frames', 10)
        );
});

it('credits every frame of the moved borders article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['borders']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/borders-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/borders-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/borders-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/borders-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['borders']['frames'])->pluck('photo')->unique())->toHaveCount(10);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/borders-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.borders.sources.body'))->toContain('Title picture')
        ->and(trans('site.borders.sources.body'))->toContain('border posts on the Oder')
        // the pictures under a share-alike licence say so
        ->and(collect(trans('site.borders.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the moved borders article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/borders-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'borders-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the moved borders article after the war, as the first communism topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/the-borders-moved'))
        ->toBeGreaterThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/the-borders-moved',\s+titleKey: 'borders.meta.title',\s+era: 'communism',\s+index: 0,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.borders.hero.kicker'))->toContain(trans('site.history.eras.communism.label'))
            ->and(trans('site.borders.hero.kicker'))->toContain('01')
            ->and(trans('site.borders.meta.title'))->toContain(trans('site.history.eras.communism.topics.0.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/the-borders-moved')
        ->assertSee(e(trans('site.borders.meta.title')), escape: false)
        ->assertSee('/images/cards/borders.jpg', escape: false);
});

it('keeps every moved borders frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['borders']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the moved borders article from the war to the German treaties', function (): void {
    $frames = collect(trans('site.borders.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['tehran', 'potsdam', 'kresy', 'recovered', 'repatriation', 'expulsion', 'vistula', 'kielce', 'strangers', 'today'])
        // it picks up where the war article stopped
        ->and($byKey['tehran']['body'])->toContain('The last topic')
        ->and($byKey['tehran']['body'])->toContain('Red Army in Warsaw')
        // the decisions come before the consequences, and the treaties close it
        ->and($byKey['tehran']['body'])->toContain('1943')
        ->and($byKey['today']['body'])->toContain('14 November 1990');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Joseph Stalin of the Soviet Union'))->toBe(array_search('tehran', $keys, true))
        ->and($firstMention('Lord Curzon'))->toBe(array_search('tehran', $keys, true))
        ->and($firstMention('Boleslaw Bierut'))->toBe(array_search('potsdam', $keys, true))
        ->and($firstMention('Wladyslaw Gomulka, first secretary'))->toBe(array_search('recovered', $keys, true))
        ->and($firstMention('Edward Osobka-Morawski, who chaired'))->toBe(array_search('repatriation', $keys, true))
        ->and($firstMention('General Karol Swierczewski, deputy defence minister'))->toBe(array_search('vistula', $keys, true))
        ->and($firstMention('Henryk Blaszczyk'))->toBe(array_search('kielce', $keys, true))
        ->and($firstMention('Jozef Maksymilian Ossolinski'))->toBe(array_search('strangers', $keys, true))
        ->and($firstMention('Stanislaw Kulczynski'))->toBe(array_search('strangers', $keys, true));
});

it('keeps the hard-won moved borders facts the way the sources give them', function (): void {
    $frames = collect(trans('site.borders.frames'))->keyBy('key');

    // the conferences, with the Poles absent from the first two and only heard at the third
    expect($frames['tehran']['body'])->toContain('28 November to 1 December 1943')
        ->and($frames['tehran']['body'])->toContain('No Pole was in the room')
        ->and($frames['potsdam']['body'])->toContain('4 to 11 February 1945')
        ->and($frames['potsdam']['body'])->toContain('five to eight kilometres')
        ->and($frames['potsdam']['body'])->toContain('22 July 1944')
        ->and($frames['potsdam']['body'])->toContain('17 July to 2 August 1945')
        ->and($frames['potsdam']['body'])->toContain('He did not sign')
        // the areas are attributed, not asserted
        ->and($frames['kresy']['body'])->toContain('Jan Karski Institute for War Losses')
        ->and($frames['kresy']['body'])->toContain('389,700')
        ->and($frames['kresy']['body'])->toContain('311,700')
        ->and($frames['kresy']['body'])->toContain('mother tongue')
        // Danzig was not German before 1939, and the page says what it was
        ->and($frames['recovered']['body'])->toContain('13 November 1945')
        ->and($frames['recovered']['body'])->toContain('Free City')
        ->and($frames['recovered']['body'])->toContain('1920')
        // repatriation is named as a euphemism, and the count is given as two figures
        ->and($frames['repatriation']['body'])->toContain('9 September 1944')
        ->and($frames['repatriation']['body'])->toContain('propaganda')
        ->and($frames['repatriation']['body'])->toContain('1.2 million')
        ->and($frames['repatriation']['body'])->toContain('two million')
        // the expulsion order is read off the order itself, and the death toll stays a dispute
        ->and($frames['expulsion']['body'])->toContain('14 July')
        ->and($frames['expulsion']['body'])->toContain('twenty kilograms')
        ->and($frames['expulsion']['body'])->toContain('2,189,286')
        ->and($frames['expulsion']['body'])->toContain('disputed')
        ->and($frames['expulsion']['body'])->toContain('2.25 million')
        ->and($frames['expulsion']['body'])->toContain('420,000')
        // Operation Vistula: the context, the trigger, and that the plan predated it
        ->and($frames['vistula']['body'])->toContain('Volhynia, now western Ukraine')
        ->and($frames['vistula']['body'])->toContain('a Carpathian people')
        ->and($frames['vistula']['body'])->toContain('28 March 1947')
        ->and($frames['vistula']['body'])->toContain('the day before')
        ->and($frames['vistula']['body'])->toContain('140,575')
        ->and($frames['vistula']['body'])->toContain('Red Army veterans')
        // Kielce: the impossible story, and the two counts of the dead
        ->and($frames['kielce']['body'])->toContain('4 July 1946')
        ->and($frames['kielce']['body'])->toContain('no cellar')
        ->and($frames['kielce']['body'])->toContain('37 Jews')
        ->and($frames['kielce']['body'])->toContain('42')
        // the Ossolineum figure is the institution's own
        ->and($frames['strangers']['body'])->toContain('thirty per cent')
        ->and($frames['strangers']['body'])->toContain('14 June 1985')
        // the border was recognised three times, and the page gives all three
        ->and($frames['today']['body'])->toContain('6 July 1950')
        ->and($frames['today']['body'])->toContain('7 December 1970')
        ->and($frames['today']['body'])->toContain('14 November 1990');
});

it('writes the moved borders names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.borders.frames'))->keyBy('key');

    expect($frames['tehran']['body'])->toContain('Józef Stalin')
        ->and($frames['tehran']['body'])->toContain('lorda Curzona')
        ->and($frames['potsdam']['body'])->toContain('Bolesławem Bierutem')
        ->and($frames['potsdam']['body'])->toContain('Nysy Łużyckiej')
        ->and($frames['kresy']['body'])->toContain('Kresy')
        ->and($frames['recovered']['body'])->toContain('Władysław Gomułka')
        ->and($frames['recovered']['body'])->toContain('Wolne Miasto')
        ->and($frames['repatriation']['body'])->toContain('Edward Osóbka-Morawski')
        ->and($frames['expulsion']['body'])->toContain('2 Armia Wojska Polskiego')
        ->and($frames['vistula']['body'])->toContain('Łemków')
        ->and($frames['vistula']['body'])->toContain('Karol Świerczewski')
        ->and($frames['vistula']['body'])->toContain('Wołyniu')
        ->and($frames['kielce']['body'])->toContain('Henryk Błaszczyk')
        ->and($frames['strangers']['body'])->toContain('Panorama Racławicka')
        ->and($frames['strangers']['body'])->toContain('Stanisław Kulczyński')
        ->and($frames['today']['body'])->toContain('Instytut Pamięci Narodowej');

    app()->setLocale('en');
});

it('draws the move west for the moved borders article from the page translations', function (): void {
    $component = file_get_contents(resource_path('js/components/BordersMap.vue'));
    $page = file_get_contents(resource_path('js/pages/HistoryBorders.vue'));
    preg_match_all("#key: '([a-z_]+)'#", $component, $keys);

    foreach (['en', 'pl'] as $locale) {
        $map = (require lang_path("{$locale}/site.php"))['borders']['map'];

        expect($map['caption'])->not->toBeEmpty()
            ->and($map['alt'])->not->toBeEmpty()
            // every town and arrow drawn has a label on this page
            ->and(array_diff($keys[1], array_keys($map['labels'])))->toBe([]);
    }

    expect(trans('site.borders.map.caption'))->toContain('Schematic')
        ->and(trans('site.borders.map.caption', [], 'pl'))->toContain('Schemat')
        // the eastern cities carry the name a foreign reader would look up, and the Polish one in Polish
        ->and(trans('site.borders.map.labels.vilnius'))->toBe('Vilnius')
        ->and(trans('site.borders.map.labels.vilnius', [], 'pl'))->toBe('Wilno')
        ->and(trans('site.borders.map.labels.lwow'))->toBe('Lviv')
        ->and(trans('site.borders.map.labels.lwow', [], 'pl'))->toBe('Lwów')
        ->and($page)->toMatch('#<BordersMap[^>]*group="borders"#')
        // the map of the Second Republic is left alone for the page that uses it
        ->and($page)->not->toContain('RepublicMap');
});

it('renders the article on rebuilding Warsaw as a filmstrip', function (): void {
    $this->get('/history/rebuilding-warsaw')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryRebuilding')
            ->has('translations.rebuilding.frames', 10)
        );
});

it('credits every frame of the rebuilding article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['rebuilding']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/rebuilding-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/rebuilding-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/rebuilding-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/rebuilding-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['rebuilding']['frames'])->pluck('photo')->unique())->toHaveCount(10);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/rebuilding-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.rebuilding.sources.body'))->toContain('Title picture')
        ->and(trans('site.rebuilding.sources.body'))->toContain('Sempolinski')
        // the picture under a share-alike licence says so
        ->and(collect(trans('site.rebuilding.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the rebuilding article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/rebuilding-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'rebuilding-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(12)
        ->and($ours->unique())->toHaveCount(12)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the rebuilding article after the moved borders, as the second communism topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/rebuilding-warsaw'))
        ->toBeGreaterThan(strpos($order, '/history/the-borders-moved'))
        ->and($order)->toMatch("#'/history/rebuilding-warsaw',\s+titleKey: 'rebuilding.meta.title',\s+era: 'communism',\s+index: 1,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.rebuilding.hero.kicker'))->toContain(trans('site.history.eras.communism.label'))
            ->and(trans('site.rebuilding.hero.kicker'))->toContain('02')
            ->and(trans('site.rebuilding.meta.title'))->toContain(trans('site.history.eras.communism.topics.1.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/rebuilding-warsaw')
        ->assertSee(e(trans('site.rebuilding.meta.title')), escape: false)
        ->assertSee('/images/cards/rebuilding.jpg', escape: false);
});

it('keeps every rebuilding frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['rebuilding']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the rebuilding article from the moved borders to the shipyard', function (): void {
    $frames = collect(trans('site.rebuilding.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['rubble', 'lodz', 'bos', 'doctrine', 'sources', 'bricks', 'honest', 'castle', 'palace', 'today'])
        // it picks up where the article on the moved borders stopped
        ->and($byKey['rubble']['body'])->toContain('The last topic')
        // and it hands the reader on to Solidarity without telling that story
        ->and($byKey['today']['body'])->toContain('shipyard');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Heinrich Himmler, who commanded the SS'))->toBe(array_search('rubble', $keys, true))
        ->and($firstMention('Boleslaw Bierut, the communist who chaired'))->toBe(array_search('lodz', $keys, true))
        ->and($firstMention('the architect Roman Piotrowski'))->toBe(array_search('bos', $keys, true))
        ->and($firstMention('Jan Zachwatowicz, who ran'))->toBe(array_search('doctrine', $keys, true))
        ->and($firstMention('Bernardo Bellotto'))->toBe(array_search('sources', $keys, true))
        ->and($firstMention('Maria Zachwatowicz'))->toBe(array_search('sources', $keys, true))
        ->and($firstMention('Stanislaw Lorentz, director of the National Museum'))->toBe(array_search('castle', $keys, true))
        ->and($firstMention('the architect Jozef Sigalin'))->toBe(array_search('palace', $keys, true));
});

it('keeps the hard-won rebuilding facts the way the sources give them', function (): void {
    $frames = collect(trans('site.rebuilding.frames'))->keyBy('key');

    // the ruin, with the share of destruction read off a period map rather than asserted
    expect($frames['rubble']['body'])->toContain('1.5 million')
        ->and($frames['rubble']['body'])->toContain('160,000')
        ->and($frames['rubble']['body'])->toContain('84 per cent')
        ->and($frames['rubble']['body'])->toContain('9 October')
        // Lodz was a real option, and Stalin closed it
        ->and($frames['lodz']['body'])->toContain('19 January 1945')
        ->and($frames['lodz']['body'])->toContain('22 January 1945')
        ->and($frames['lodz']['body'])->toContain('Waingertner')
        // the office, its size, the decree that made it possible and its archive
        ->and($frames['bos']['body'])->toContain('14 February 1945')
        ->and($frames['bos']['body'])->toContain('1,500')
        ->and($frames['bos']['body'])->toContain('26 October 1945')
        ->and($frames['bos']['body'])->toContain('Memory of the World')
        ->and($frames['bos']['body'])->toContain('2011')
        // the doctrine is quoted from the charter, not paraphrased into vagueness
        ->and($frames['doctrine']['body'])->toContain('Biuletyn Historii Sztuki i Kultury')
        ->and($frames['doctrine']['body'])->toContain('1946')
        ->and($frames['doctrine']['body'])->toContain('Venice Charter of 1964')
        ->and($frames['doctrine']['body'])->toContain('ruled out a priori')
        // the paintings are one source among several, and they are not exact
        ->and($frames['sources']['body'])->toContain('1767')
        ->and($frames['sources']['body'])->toContain('Twenty-four')
        ->and($frames['sources']['body'])->toContain('tidied up')
        ->and($frames['sources']['body'])->toContain('1922')
        ->and($frames['sources']['body'])->toContain('35,000')
        // the bricks came out of other Polish cities, and the page says what that cost them
        ->and($frames['bricks']['body'])->toContain('Recovered Territories')
        ->and($frames['bricks']['body'])->toContain('Breslau')
        ->and($frames['bricks']['body'])->toContain('fraud trial')
        // the honest frame names what is old, what is new and what copies nothing
        ->and($frames['honest']['body'])->toContain('late eighteenth century')
        ->and($frames['honest']['body'])->toContain('cellars')
        ->and($frames['honest']['body'])->toContain('Barbican')
        // the castle waited, and the dates say how long
        ->and($frames['castle']['body'])->toContain('September 1939')
        ->and($frames['castle']['body'])->toContain('September 1944')
        ->and($frames['castle']['body'])->toContain('Edward Gierek')
        ->and($frames['castle']['body'])->toContain('1971')
        ->and($frames['castle']['body'])->toContain('1974')
        ->and($frames['castle']['body'])->toContain('1984')
        // the socialist city, with the gift named as a gift and Stalin's name dated off it
        ->and($frames['palace']['body'])->toContain('1950 and 1952')
        ->and($frames['palace']['body'])->toContain('Plac Konstytucji')
        ->and($frames['palace']['body'])->toContain('April 1952')
        ->and($frames['palace']['body'])->toContain('21 July 1955')
        ->and($frames['palace']['body'])->toContain('17 November 1956')
        // UNESCO is quoted, and its own caveats are passed on
        ->and($frames['today']['body'])->toContain('1980')
        ->and($frames['today']['body'])->toContain('near-total reconstruction')
        ->and($frames['today']['body'])->toContain('13th to the 20th century')
        ->and($frames['today']['body'])->toContain('left unbuilt on purpose');
});

it('writes the rebuilding names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.rebuilding.frames'))->keyBy('key');

    expect($frames['rubble']['body'])->toContain('Powstanie warszawskie')
        ->and($frames['lodz']['body'])->toContain('Bolesław Bierut')
        ->and($frames['lodz']['body'])->toContain('Łódź')
        ->and($frames['bos']['body'])->toContain('Biuro Odbudowy Stolicy')
        ->and($frames['doctrine']['body'])->toContain('Karcie Weneckiej')
        ->and($frames['doctrine']['body'])->toContain('Jan Zachwatowicz')
        ->and($frames['sources']['body'])->toContain('Canalettem')
        ->and($frames['sources']['body'])->toContain('Stanisława Augusta Poniatowskiego')
        ->and($frames['sources']['body'])->toContain('Maria Zachwatowicz')
        ->and($frames['bricks']['body'])->toContain('Ziemiami Odzyskanymi')
        ->and($frames['honest']['body'])->toContain('Barbakan')
        ->and($frames['castle']['body'])->toContain('Zamek Królewski')
        ->and($frames['castle']['body'])->toContain('Stanisława Lorentza')
        ->and($frames['castle']['body'])->toContain('Edward Gierek')
        ->and($frames['palace']['body'])->toContain('Józefa Sigalina')
        ->and($frames['palace']['body'])->toContain('Pałac Kultury i Nauki')
        ->and($frames['today']['body'])->toContain('stoczni w Gdańsku');

    app()->setLocale('en');
});

it('publishes a real period map of the destruction on the rebuilding article', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/rebuilding-map-{$size}.jpg"))->toBeReadableFile();
    }

    // a map a surveying office made, captioned with its maker, its date and its language
    expect(trans('site.rebuilding.map.caption'))->toContain('1949')
        ->and(trans('site.rebuilding.map.credit'))->toContain('Main Office of Land Surveying')
        ->and(trans('site.rebuilding.map.alt'))->not->toBeEmpty()
        ->and(trans('site.rebuilding.map.caption', [], 'pl'))->toContain('1949')
        ->and(trans('site.rebuilding.map.credit', [], 'pl'))->toContain('Główny Urząd Pomiarów Kraju')
        ->and(trans('site.rebuilding.map.alt', [], 'pl'))->not->toBeEmpty();

    // no hand-drawn schematic stands in for it
    expect(file_get_contents(resource_path('js/pages/HistoryRebuilding.vue')))
        ->not->toContain('Map.vue')
        ->toContain('rebuilding-map-lg.jpg');
});

it('renders the article on Solidarity as a filmstrip', function (): void {
    $this->get('/history/solidarity-1980')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistorySolidarity')
            ->has('translations.solidarity.frames', 11)
        );
});

it('credits every frame of the Solidarity article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['solidarity']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/solidarity-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/solidarity-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/solidarity-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/solidarity-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['solidarity']['frames'])->pluck('photo')->unique())->toHaveCount(11);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/solidarity-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.solidarity.sources.body'))->toContain('Title picture')
        ->and(trans('site.solidarity.sources.body'))->toContain('Adrian Tync')
        // the pictures under a share-alike licence say so
        ->and(collect(trans('site.solidarity.frames'))->keyBy('key')['august']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the Solidarity article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/solidarity-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'solidarity-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(12)
        ->and($ours->unique())->toHaveCount(12)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the Solidarity article after the rebuilding of Warsaw, as the third communism topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/solidarity-1980'))
        ->toBeGreaterThan(strpos($order, '/history/rebuilding-warsaw'))
        ->and($order)->toMatch("#'/history/solidarity-1980',\s+titleKey: 'solidarity.meta.title',\s+era: 'communism',\s+index: 2,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.solidarity.hero.kicker'))->toContain(trans('site.history.eras.communism.label'))
            ->and(trans('site.solidarity.hero.kicker'))->toContain('03')
            ->and(trans('site.solidarity.meta.title'))->toContain(trans('site.history.eras.communism.topics.2.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/solidarity-1980')
        ->assertSee(e(trans('site.solidarity.meta.title')), escape: false)
        ->assertSee('/images/cards/solidarity.jpg', escape: false);
});

it('keeps every Solidarity frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['solidarity']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Solidarity article from the rebuilt capital to 1989', function (): void {
    $frames = collect(trans('site.solidarity.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['queues', 'poznan', 'march', 'coast', 'kor', 'pope', 'august', 'carnival', 'martial', 'underground', 'today'])
        // it picks up where the article on the rebuilding of Warsaw stopped
        ->and($byKey['queues']['body'])->toContain('The last topic');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Edward Gierek, who ran the communist party'))->toBe(array_search('queues', $keys, true))
        ->and($firstMention('Wladyslaw Gomulka'))->toBe(array_search('poznan', $keys, true))
        ->and($firstMention('Adam Mickiewicz'))->toBe(array_search('march', $keys, true))
        ->and($firstMention('Adam Michnik and Henryk Szlajfer'))->toBe(array_search('march', $keys, true))
        ->and($firstMention('Mieczyslaw Moczar, a deputy interior minister'))->toBe(array_search('march', $keys, true))
        ->and($firstMention('Piotr Jaroszewicz'))->toBe(array_search('kor', $keys, true))
        ->and($firstMention('Jacek Kuron'))->toBe(array_search('kor', $keys, true))
        ->and($firstMention('Karol Wojtyla, the archbishop of Krakow'))->toBe(array_search('pope', $keys, true))
        ->and($firstMention('Anna Walentynowicz'))->toBe(array_search('august', $keys, true))
        ->and($firstMention('Lech Walesa, an electrician'))->toBe(array_search('august', $keys, true))
        ->and($firstMention('Mieczyslaw Jagielski'))->toBe(array_search('carnival', $keys, true))
        ->and($firstMention('General Wojciech Jaruzelski'))->toBe(array_search('martial', $keys, true))
        ->and($firstMention('Father Jerzy Popieluszko'))->toBe(array_search('underground', $keys, true));
});

it('keeps the hard-won Solidarity facts the way the sources give them', function (): void {
    $frames = collect(trans('site.solidarity.frames'))->keyBy('key');

    // sugar went on ration cards in 1976; meat only after August 1980, so the 1970s frame says sugar
    expect($frames['queues']['body'])->toContain('sugar went on ration cards in 1976')
        // Poznan: the IPN figure, with its own caveat, and the documented slogan
        ->and($frames['poznan']['body'])->toContain('28 June 1956')
        ->and($frames['poznan']['body'])->toContain('We demand bread')
        ->and($frames['poznan']['body'])->toContain('359 tanks')
        ->and($frames['poznan']['body'])->toContain('not fewer than 58')
        ->and($frames['poznan']['body'])->toContain('still argued over')
        ->and($frames['poznan']['body'])->toContain('Romek Strzalkowski')
        // March 1968 was not only the banned play, and the emigration figure carries its years
        ->and($frames['march']['body'])->toContain('30 January 1968')
        ->and($frames['march']['body'])->toContain('8 March')
        ->and($frames['march']['body'])->toContain('15,000')
        ->and($frames['march']['body'])->toContain('1968 and 1969')
        // December 1970: the official list and the higher count, both attributed
        ->and($frames['coast']['body'])->toContain('12 December 1970')
        ->and($frames['coast']['body'])->toContain('17 December')
        ->and($frames['coast']['body'])->toContain('44 names')
        ->and($frames['coast']['body'])->toContain('at least 45')
        ->and($frames['coast']['body'])->toContain('20 December')
        // 1976 was three towns, and KOR had fourteen founders, Michnik not among them
        ->and($frames['kor']['body'])->toContain('24 June 1976')
        ->and($frames['kor']['body'])->toContain('Plock')
        ->and($frames['kor']['body'])->toContain('health paths')
        ->and($frames['kor']['body'])->toContain('23 September')
        ->and($frames['kor']['body'])->toContain('fourteen')
        ->and($frames['kor']['body'])->toContain('argued their case from abroad')
        // the Vatican text prints the appeal twice, and the crowd figures are attributed, never a million
        ->and($frames['pope']['body'])->toContain('16 October 1978')
        ->and($frames['pope']['body'])->toContain('2 to 10 June 1979')
        ->and(substr_count($frames['pope']['body'], 'Let your Spirit descend'))->toBe(2)
        ->and($frames['pope']['body'])->toContain('200,000')
        ->and($frames['pope']['body'])->not->toContain('a million')
        // August 1980: the dismissal, the wavering strike, the boards of 17 August
        ->and($frames['august']['body'])->toContain('7 August 1980')
        ->and($frames['august']['body'])->toContain('14 August')
        ->and($frames['august']['body'])->toContain('17 August')
        ->and($frames['august']['body'])->toContain('plywood')
        ->and($frames['august']['body'])->toContain('Gate No 2')
        // the Supreme Court registered the union, not the provincial court
        ->and($frames['carnival']['body'])->toContain('31 August 1980')
        ->and($frames['carnival']['body'])->toContain('Huta Katowice')
        ->and($frames['carnival']['body'])->toContain('Supreme Court')
        ->and($frames['carnival']['body'])->toContain('10 November')
        ->and($frames['carnival']['body'])->toContain('always an estimate')
        ->and($frames['carnival']['body'])->toContain('19 March 1981')
        // martial law: the first-day figure is 3,173, not the total for the whole period
        ->and($frames['martial']['body'])->toContain('13 December 1981')
        ->and($frames['martial']['body'])->toContain('3,173')
        ->and($frames['martial']['body'])->toContain('first twenty-four hours')
        ->and($frames['martial']['body'])->toContain('16 December')
        ->and($frames['martial']['body'])->toContain('nine miners')
        // the underground years, with the dates martial law ended
        ->and($frames['underground']['body'])->toContain('31 December 1982')
        ->and($frames['underground']['body'])->toContain('22 July 1983')
        ->and($frames['underground']['body'])->toContain('Danuta')
        ->and($frames['underground']['body'])->toContain('19 October 1984')
        ->and($frames['underground']['body'])->toContain('Gorsk')
        ->and($frames['underground']['body'])->toContain('600,000')
        // both live disputes are stated with both sides attributed and neither settled
        ->and($frames['today']['body'])->toContain('lesser evil')
        ->and($frames['today']['body'])->toContain('10 December 1981')
        ->and($frames['today']['body'])->toContain('2017')
        ->and($frames['today']['body'])->toContain('He says they were forged');

    // the Roads to Freedom exhibition closed in 2014 and is not offered as a place to visit
    expect($frames['today']['body'])->not->toContain('Roads to Freedom')
        ->and(trans('site.solidarity.sources.body'))->toContain('closed when the European Solidarity Centre opened');
});

it('writes the Solidarity names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.solidarity.frames'))->keyBy('key');

    expect($frames['queues']['body'])->toContain('Edward Gierek')
        ->and($frames['poznan']['body'])->toContain('Żądamy chleba')
        ->and($frames['poznan']['body'])->toContain('Romek Strzałkowski')
        ->and($frames['march']['body'])->toContain('Dziady')
        ->and($frames['march']['body'])->toContain('Mieczysław Moczar')
        ->and($frames['coast']['body'])->toContain('Gdynia Stocznia')
        ->and($frames['kor']['body'])->toContain('Komitet Obrony Robotników')
        ->and($frames['kor']['body'])->toContain('ścieżkami zdrowia')
        ->and($frames['pope']['body'])->toContain('Karola Wojtyłę')
        ->and($frames['pope']['body'])->toContain('Niech zstąpi Duch Twój')
        ->and($frames['august']['body'])->toContain('Anna Walentynowicz')
        ->and($frames['august']['body'])->toContain('Lech Wałęsa')
        ->and($frames['august']['body'])->toContain('Bramie nr 2')
        ->and($frames['carnival']['body'])->toContain('Sąd Najwyższy')
        ->and($frames['martial']['body'])->toContain('Wojskowa Rada Ocalenia Narodowego')
        ->and($frames['martial']['body'])->toContain('Wujek')
        ->and($frames['underground']['body'])->toContain('Jerzego Popiełuszkę')
        ->and($frames['today']['body'])->toContain('Europejskie Centrum Solidarności');

    app()->setLocale('en');
});

it('publishes no invented map on the Solidarity article', function (): void {
    // the story is not spatial, so nothing hand-drawn stands in for evidence
    expect(file_get_contents(resource_path('js/pages/HistorySolidarity.vue')))
        ->not->toContain('Map.vue')
        ->not->toContain('solidarity-map');
});

it('renders the article on June 1989 as a filmstrip', function (): void {
    $this->get('/history/june-1989')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryJune1989')
            ->has('translations.june1989.frames', 9)
        );
});

it('credits every frame of the June 1989 article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['june1989']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/june1989-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/june1989-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/june1989-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/june1989-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['june1989']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/june1989-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.june1989.sources.body'))->toContain('Title picture')
        ->and(trans('site.june1989.sources.body'))->toContain('Adrian Grycuk')
        // the pictures under a share-alike licence say so
        ->and(collect(trans('site.june1989.frames'))->keyBy('key')['broke']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the June 1989 article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/june1989-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'june1989-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the June 1989 article after Solidarity, as the fourth communism topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/june-1989'))
        ->toBeGreaterThan(strpos($order, '/history/solidarity-1980'))
        ->and($order)->toMatch("#'/history/june-1989',\s+titleKey: 'june1989.meta.title',\s+era: 'communism',\s+index: 3,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.june1989.hero.kicker'))->toContain(trans('site.history.eras.communism.label'))
            ->and(trans('site.june1989.hero.kicker'))->toContain('04')
            ->and(trans('site.june1989.meta.title'))->toContain(trans('site.history.eras.communism.topics.3.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/june-1989')
        ->assertSee(e(trans('site.june1989.meta.title')), escape: false)
        ->assertSee('/images/cards/june1989.jpg', escape: false);
});

it('keeps every June 1989 frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['june1989']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the June 1989 article from the banned union to the new republic', function (): void {
    $frames = collect(trans('site.june1989.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['broke', 'talks', 'table', 'campaign', 'vote', 'list', 'handover', 'government', 'argued'])
        // it picks up where the article on Solidarity stopped
        ->and($byKey['broke']['body'])->toContain('The last topic');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('General Wojciech Jaruzelski, the officer who had declared martial law'))->toBe(array_search('broke', $keys, true))
        ->and($firstMention('General Czeslaw Kiszczak, who had run the police'))->toBe(array_search('talks', $keys, true))
        ->and($firstMention('Lech Walesa, the Gdansk electrician'))->toBe(array_search('talks', $keys, true))
        ->and($firstMention('Mikhail Gorbachev, the Soviet leader since 1985'))->toBe(array_search('talks', $keys, true))
        ->and($firstMention('Adam Michnik, a writer'))->toBe(array_search('campaign', $keys, true))
        ->and($firstMention('Tomasz Sarnecki, a 23-year-old art student'))->toBe(array_search('vote', $keys, true))
        ->and($firstMention('Mikolaj Kozakiewicz, who went on to chair the new Sejm'))->toBe(array_search('list', $keys, true))
        ->and($firstMention('Tadeusz Mazowiecki'))->toBe(array_search('government', $keys, true))
        ->and($firstMention('Leszek Balcerowicz'))->toBe(array_search('government', $keys, true))
        ->and($firstMention('Nicolae Ceausescu, the Romanian dictator'))->toBe(array_search('argued', $keys, true));
});

it('keeps the hard-won June 1989 facts the way the sources give them', function (): void {
    $frames = collect(trans('site.june1989.frames'))->keyBy('key');

    // the referendum failed on the threshold, not on the count of yes votes
    expect($frames['broke']['body'])->toContain('29 November 1987')
        ->and($frames['broke']['body'])->toContain('67.32 per cent')
        ->and($frames['broke']['body'])->toContain('more than half of everyone on the register')
        // the turnout was the lowest so far, not the lowest ever: June 1989 was lower
        ->and($frames['broke']['body'])->toContain('had recorded up to then')
        // the secret talks, dated
        ->and($frames['talks']['body'])->toContain('31 August 1988')
        ->and($frames['talks']['body'])->toContain('16 September')
        ->and($frames['talks']['body'])->toContain('Magdalenka')
        // the Round Table ran from 6 February to 5 April and produced a split, not a free vote
        ->and($frames['table']['body'])->toContain('6 February 1989')
        ->and($frames['table']['body'])->toContain('5 April')
        ->and($frames['table']['body'])->toContain('65 per cent')
        ->and($frames['table']['body'])->toContain('100 freely elected senators')
        ->and($frames['table']['body'])->toContain('the Church sent observers')
        // the campaign: the paper's own figures
        ->and($frames['campaign']['body'])->toContain('8 May')
        ->and($frames['campaign']['body'])->toContain('150,000')
        ->and($frames['campaign']['body'])->toContain('eight pages')
        ->and($frames['campaign']['body'])->toContain('Jerzy Janiszewski')
        // the first round, with the seats that were actually contestable
        ->and($frames['vote']['body'])->toContain('Sixty-two per cent')
        ->and($frames['vote']['body'])->toContain('160 of the 161')
        ->and($frames['vote']['body'])->toContain('92 of the 100')
        // the national list, the two survivors and the law changed between the rounds
        ->and($frames['list']['body'])->toContain('35 senior figures')
        ->and($frames['list']['body'])->toContain('33 seats')
        ->and($frames['list']['body'])->toContain('amended the election law between the rounds')
        ->and($frames['list']['body'])->toContain('18 June')
        ->and($frames['list']['body'])->toContain('99 of the 100')
        // 4 June was not a free election, and the presidency was elected by one vote on 19 July
        ->and($frames['handover']['body'])->toContain('never in play')
        ->and($frames['handover']['body'])->toContain('Tiananmen')
        ->and($frames['handover']['body'])->toContain('3 July')
        ->and($frames['handover']['body'])->toContain('19 July')
        ->and($frames['handover']['body'])->toContain('270 votes to 233')
        ->and($frames['handover']['body'])->toContain('one more than he needed')
        ->and($frames['handover']['body'])->toContain('the two houses sitting together')
        // the government of 24 August, and the sentence Poles still argue about
        ->and($frames['government']['body'])->toContain('17 August')
        ->and($frames['government']['body'])->toContain('378 votes to 4')
        ->and($frames['government']['body'])->toContain('thick line')
        ->and($frames['government']['body'])->toContain('Kiszczak stayed on as interior minister')
        ->and($frames['government']['body'])->toContain('ten laws drafted by his finance minister')
        // the last week of 1989, and both readings of the Round Table left standing
        ->and($frames['argued']['body'])->toContain('9 November')
        ->and($frames['argued']['body'])->toContain('31 December')
        ->and($frames['argued']['body'])->toContain('crown back on its eagle')
        ->and($frames['argued']['body'])->toContain('One side')
        ->and($frames['argued']['body'])->toContain('the other')
        ->and($frames['argued']['body'])->toContain('files of the secret police')
        // the table is in a museum now, and the page says so where the reader will look
        ->and($frames['argued']['body'])->toContain('Museum of Polish History');

    // Jaruzelski was elected on 19 July 1989, not on 4 July, and the sources say so
    expect(trans('site.june1989.sources.body'))->toContain('19 July 1989 by 270 votes to 233')
        // the table is no longer in the Presidential Palace, so the page does not send anyone there
        ->and(trans('site.june1989.sources.body'))->toContain('until December 2025')
        ->and(trans('site.june1989.sources.body'))->toContain('Museum of Polish History')
        // the poster is still in copyright, so it is described and not reproduced
        ->and(trans('site.june1989.sources.body'))->toContain('described rather than shown')
        ->and(glob(public_path('images/june1989-poster-*.jpg')))->toBe([]);
});

it('writes the June 1989 names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.june1989.frames'))->keyBy('key');

    expect($frames['broke']['body'])->toContain('Wojciech Jaruzelski')
        ->and($frames['talks']['body'])->toContain('Czesław Kiszczak')
        ->and($frames['talks']['body'])->toContain('Lechem Wałęsą')
        ->and($frames['talks']['body'])->toContain('Michaił Gorbaczow')
        ->and($frames['table']['body'])->toContain('Pałacu Namiestnikowskim')
        ->and($frames['campaign']['body'])->toContain('komitety obywatelskie')
        ->and($frames['campaign']['body'])->toContain('Gazety Wyborczej')
        ->and($frames['vote']['body'])->toContain('W samo południe')
        ->and($frames['list']['body'])->toContain('Mikołaj Kozakiewicz')
        ->and($frames['handover']['body'])->toContain('Zgromadzenie Narodowe')
        ->and($frames['government']['body'])->toContain('Tadeusza Mazowieckiego')
        ->and($frames['government']['body'])->toContain('grubą linią')
        ->and($frames['argued']['body'])->toContain('Rzecząpospolitą Polską')
        ->and($frames['argued']['body'])->toContain('Muzeum Historii Polski');

    app()->setLocale('en');
});

it('publishes no invented map on the June 1989 article', function (): void {
    // the story is not spatial, so nothing hand-drawn stands in for evidence
    expect(file_get_contents(resource_path('js/pages/HistoryJune1989.vue')))
        ->not->toContain('Map.vue')
        ->not->toContain('june1989-map');
});

it('renders the article on the transition as a filmstrip', function (): void {
    $this->get('/history/the-transition')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryTransition')
            ->has('translations.transition.frames', 9)
        );
});

it('credits every frame of the transition article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['transition']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/transition-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/transition-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/transition-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/transition-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['transition']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/transition-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.transition.sources.body'))->toContain('Title picture')
        ->and(trans('site.transition.sources.body'))->toContain('Piotr Waglowski')
        // the pictures under a share-alike licence say so
        ->and(collect(trans('site.transition.frames'))->keyBy('key')['plan']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the transition article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/transition-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'transition-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the transition article after June 1989, as the first topic after 1989', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/the-transition'))
        ->toBeGreaterThan(strpos($order, '/history/june-1989'))
        ->and($order)->toMatch("#'/history/the-transition',\s+titleKey: 'transition.meta.title',\s+era: 'after',\s+index: 0,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.transition.hero.kicker'))->toContain(trans('site.history.eras.after.label'))
            ->and(trans('site.transition.hero.kicker'))->toContain('01')
            ->and(trans('site.transition.meta.title'))->toContain(trans('site.history.eras.after.topics.0.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/the-transition')
        ->assertSee(e(trans('site.transition.meta.title')), escape: false)
        ->assertSee('/images/cards/transition.jpg', escape: false);
});

it('keeps every transition frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['transition']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the transition article from the Balcerowicz laws to NATO and the European Union', function (): void {
    $frames = collect(trans('site.transition.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['plan', 'shops', 'jobs', 'land', 'bazaar', 'shares', 'politics', 'ledger', 'today'])
        // it picks up where the article on June 1989 stopped
        ->and($byKey['plan']['body'])->toContain('The last topic ended with those ten laws')
        // and it hands the reader on to the topic that follows
        ->and($byKey['today']['body'])->toContain('NATO in 1999')
        ->and($byKey['today']['body'])->toContain('European Union in 2004');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Leszek Balcerowicz, deputy prime minister and finance minister'))->toBe(array_search('plan', $keys, true))
        ->and($firstMention('under Tadeusz Mazowiecki, the first prime minister'))->toBe(array_search('plan', $keys, true))
        ->and($firstMention("The Central Statistical Office, the government's own statisticians"))->toBe(array_search('shops', $keys, true))
        ->and($firstMention('the Agricultural Property Agency'))->toBe(array_search('land', $keys, true))
        ->and($firstMention('Lech Walesa, the Gdansk electrician who had led the Solidarity union'))->toBe(array_search('politics', $keys, true))
        ->and($firstMention('Stan Tyminski, a businessman nobody had heard of'))->toBe(array_search('politics', $keys, true))
        ->and($firstMention('Aleksander Kwasniewski'))->toBe(array_search('ledger', $keys, true))
        ->and($firstMention('Grzegorz Kolodko, finance minister from 1994'))->toBe(array_search('ledger', $keys, true));
});

it('keeps the hard-won transition facts the way the sources give them', function (): void {
    $frames = collect(trans('site.transition.frames'))->keyBy('key');

    // the package was passed on 28 December 1989, and it did not free prices that were free already
    expect($frames['plan']['body'])->toContain('28 December 1989')
        ->and($frames['plan']['body'])->toContain('Most shop prices had already been freed during 1989')
        ->and($frames['plan']['body'])->toContain('popiwek')
        // the price figures are the published statistical office series
        ->and($frames['shops']['body'])->toContain('79.6 per cent in January 1990')
        ->and($frames['shops']['body'])->toContain('585.8 per cent')
        ->and($frames['shops']['body'])->toContain('251.1 per cent')
        ->and($frames['shops']['body'])->toContain('70.3 per cent')
        // the fall in real wages is a range across sources, so the copy hedges it
        ->and($frames['shops']['body'])->toContain('roughly a quarter')
        // registered unemployment, counted rather than estimated
        ->and($frames['jobs']['body'])->toContain('1,126,100')
        ->and($frames['jobs']['body'])->toContain('6.5 per cent')
        ->and($frames['jobs']['body'])->toContain('2,155,600')
        ->and($frames['jobs']['body'])->toContain('2,889,600')
        ->and($frames['jobs']['body'])->toContain('16.9 per cent in July 1994')
        // the shipyard was declared bankrupt by a court, on a dated decision
        ->and($frames['jobs']['body'])->toContain('8 August 1996')
        // the state farms: the law, the count, the employment and the flats
        ->and($frames['land']['body'])->toContain('19 October 1991')
        ->and($frames['land']['body'])->toContain('1,667')
        ->and($frames['land']['body'])->toContain('475,000')
        ->and($frames['land']['body'])->toContain('123,000')
        ->and($frames['land']['body'])->toContain('328,000')
        // trading began in 1989, the name Jarmark Europa only in 1996, and it ended in 2007
        ->and($frames['bazaar']['body'])->toContain('from 1989')
        ->and($frames['bazaar']['body'])->toContain('From 1996 the market was called Jarmark Europa')
        ->and($frames['bazaar']['body'])->toContain('30 September 2007')
        // the largest-market claim is attributed to the press, not asserted as fact
        ->and($frames['bazaar']['body'])->toContain('Polish newspapers called it')
        // mass privatisation, with the numbers that can be checked
        ->and($frames['shares']['body'])->toContain('30 April 1993')
        ->and($frames['shares']['body'])->toContain('512 state enterprises')
        ->and($frames['shares']['body'])->toContain('20 zlotys')
        ->and($frames['shares']['body'])->toContain('25.9 million')
        ->and($frames['shares']['body'])->toContain('96 per cent')
        // the elections, with the official figures
        ->and($frames['politics']['body'])->toContain('9 December 1990')
        ->and($frames['politics']['body'])->toContain('74.25 per cent')
        ->and($frames['politics']['body'])->toContain('27 October 1991')
        ->and($frames['politics']['body'])->toContain('43.2 per cent')
        ->and($frames['politics']['body'])->toContain('29 separate committees')
        // 1993 was a free vote, and the page says so rather than implying a restoration
        ->and($frames['ledger']['body'])->toContain('19 September 1993')
        ->and($frames['ledger']['body'])->toContain('wholly free election')
        ->and($frames['ledger']['body'])->toContain('171 of the 460')
        // both readings of the decade stand, each attributed to a named economist
        ->and($frames['ledger']['body'])->toContain('Balcerowicz argues')
        ->and($frames['ledger']['body'])->toContain('Kolodko, finance minister from 1994, argues')
        // the constitution, and the recovery year left vague because the estimates differ
        ->and($frames['today']['body'])->toContain('25 May 1997')
        ->and($frames['today']['body'])->toContain('around the middle of the decade');

    expect(trans('site.transition.sources.body'))->toContain('1995 or in 1996')
        // the provinces were redrawn in 1999, so the old names are not used as if they were current
        ->and(trans('site.transition.sources.body'))->toContain('Poland redrew its provinces on 1 January 1999');
});

it('writes the transition names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.transition.frames'))->keyBy('key');

    expect($frames['plan']['body'])->toContain('Leszek Balcerowicz')
        ->and($frames['plan']['body'])->toContain('Tadeusza Mazowieckiego')
        ->and($frames['plan']['body'])->toContain('popiwkiem')
        ->and($frames['shops']['body'])->toContain('Główny Urząd Statystyczny')
        ->and($frames['jobs']['body'])->toContain('Stocznię Gdańską')
        ->and($frames['land']['body'])->toContain('państwowe gospodarstwa rolne')
        ->and($frames['land']['body'])->toContain('Agencji Własności Rolnej Skarbu Państwa')
        ->and($frames['bazaar']['body'])->toContain('Stadion Dziesięciolecia')
        ->and($frames['bazaar']['body'])->toContain('Jarmark Europa')
        ->and($frames['shares']['body'])->toContain('powszechne świadectwo udziałowe')
        ->and($frames['politics']['body'])->toContain('Lech Wałęsa')
        ->and($frames['politics']['body'])->toContain('Stanisławem Tymińskim')
        ->and($frames['ledger']['body'])->toContain('Sojusz Lewicy Demokratycznej')
        ->and($frames['ledger']['body'])->toContain('Grzegorz Kołodko')
        ->and($frames['today']['body'])->toContain('Nowa Huta');

    app()->setLocale('en');
});

it('publishes no invented map on the transition article', function (): void {
    // the story is economic rather than spatial, and no published map of it was found,
    // so nothing hand-drawn stands in for evidence
    expect(file_get_contents(resource_path('js/pages/HistoryTransition.vue')))
        ->not->toContain('Map.vue')
        ->not->toContain('transition-map');
});

it('renders the article on NATO and the European Union as a filmstrip', function (): void {
    $this->get('/history/nato-and-the-eu')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryEurope')
            ->has('translations.euro.frames', 9)
        );
});

it('credits every frame of the NATO and European Union article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['euro']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/euro-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/euro-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/euro-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/euro-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['euro']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/euro-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.euro.sources.body'))->toContain('Title picture')
        ->and(trans('site.euro.sources.body'))->toContain('Bahnfrend')
        // the pictures under a share-alike licence say so, and the government ones say they are free
        ->and(collect(trans('site.euro.frames'))->keyBy('key')['grey']['credit'])->toContain('CC BY-SA 2.0')
        ->and(collect(trans('site.euro.frames'))->keyBy('key')['army']['credit'])->toContain('public domain');
});

it('uses pictures on the NATO and European Union article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/euro-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'euro-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the NATO and European Union article after the transition', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/nato-and-the-eu'))
        ->toBeGreaterThan(strpos($order, '/history/the-transition'))
        ->and($order)->toMatch("#'/history/nato-and-the-eu',\s+titleKey: 'euro.meta.title',\s+era: 'after',\s+index: 1,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.euro.hero.kicker'))->toContain(trans('site.history.eras.after.label'))
            ->and(trans('site.euro.hero.kicker'))->toContain('02')
            ->and(trans('site.euro.meta.title'))->toContain(trans('site.history.eras.after.topics.1.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/nato-and-the-eu')
        ->assertSee(e(trans('site.euro.meta.title')), escape: false)
        ->assertSee('/images/cards/euro.jpg', escape: false);
});

it('keeps every NATO and European Union frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['euro']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the NATO and European Union article from the constitution of 1997', function (): void {
    $frames = collect(trans('site.euro.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['grey', 'door', 'missouri', 'army', 'road', 'treaty', 'firstmay', 'money', 'argument'])
        // it picks up where the article on the transition stopped
        ->and($byKey['grey']['body'])->toContain('The last topic ended with the constitution of 1997')
        // and the previous article hands the reader on to these two dates
        ->and(trans('site.transition.frames.8.body'))->toContain('NATO in 1999')
        ->and(trans('site.transition.frames.8.body'))->toContain('European Union in 2004');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('President Lech Walesa'))->toBe(array_search('grey', $keys, true))
        ->and($firstMention('NATO, the North Atlantic Treaty Organisation'))->toBe(array_search('door', $keys, true))
        ->and($firstMention('the prime minister, Waldemar Pawlak'))->toBe(array_search('door', $keys, true))
        ->and($firstMention('The Sejm, the lower house of the Polish parliament'))->toBe(array_search('missouri', $keys, true))
        ->and($firstMention("Bronislaw Geremek, Poland's foreign minister"))->toBe(array_search('missouri', $keys, true))
        ->and($firstMention('Madeleine Albright, the American secretary of state'))->toBe(array_search('missouri', $keys, true))
        ->and($firstMention('Polska Zbrojna'))->toBe(array_search('army', $keys, true))
        ->and($firstMention('Andrzej Olechowski, handed in the application'))->toBe(array_search('road', $keys, true))
        ->and($firstMention("The Central Statistical Office, the government's own statisticians"))->toBe(array_search('firstmay', $keys, true))
        ->and($firstMention('The roads authority, GDDKiA'))->toBe(array_search('money', $keys, true))
        ->and($firstMention('The European Commission, which runs the Union day to day'))->toBe(array_search('argument', $keys, true))
        ->and($firstMention('the polling institute CBOS'))->toBe(array_search('argument', $keys, true));
});

it('keeps the hard-won NATO and European Union facts the way the sources give them', function (): void {
    $frames = collect(trans('site.euro.frames'))->keyBy('key');

    // the Soviet withdrawal has three dates and the page uses all three
    expect($frames['grey']['body'])->toContain('1 July 1991')
        ->and($frames['grey']['body'])->toContain('22 May 1992')
        ->and($frames['grey']['body'])->toContain('28 October 1992')
        ->and($frames['grey']['body'])->toContain('18 September 1993')
        // the Partnership for Peace came first, and it guaranteed nothing
        ->and($frames['door']['body'])->toContain('2 February 1994')
        ->and($frames['door']['body'])->toContain('promising them no defence at all')
        ->and($frames['door']['body'])->toContain('8 July 1997')
        // the accession: the protocols, the Sejm vote, the ceremony and who was in the room
        ->and($frames['missouri']['body'])->toContain('16 December 1997')
        ->and($frames['missouri']['body'])->toContain('409 votes to 7')
        ->and($frames['missouri']['body'])->toContain('12 March 1999')
        ->and($frames['missouri']['body'])->toContain('Independence, Missouri')
        ->and($frames['missouri']['body'])->toContain('Jan Kavan')
        ->and($frames['missouri']['body'])->toContain('Janos Martonyi')
        // conscription ended in three steps, and the page gives all three rather than one year
        ->and($frames['army']['body'])->toContain('last compulsory call-up was in 2008')
        ->and($frames['army']['body'])->toContain('from January 2009')
        ->and($frames['army']['body'])->toContain('1 January 2010')
        ->and($frames['army']['body'])->toContain('48 American F-16 fighters on 18 April 2003')
        ->and($frames['army']['body'])->toContain('Afghanistan from 2002')
        ->and($frames['army']['body'])->toContain('Iraq from 2003')
        // the Copenhagen criteria, the dates of the talks and the new external border
        ->and($frames['road']['body'])->toContain('16 December 1991')
        ->and($frames['road']['body'])->toContain('8 April 1994')
        ->and($frames['road']['body'])->toContain('what was then the European Communities')
        ->and($frames['road']['body'])->toContain('Copenhagen in June 1993')
        ->and($frames['road']['body'])->toContain('1 October 2003')
        ->and($frames['road']['body'])->toContain('31 March 1998')
        ->and($frames['road']['body'])->toContain('13 December 2002')
        // the referendum ran over two days for a reason, and the figures are the official ones
        ->and($frames['treaty']['body'])->toContain('16 April 2003')
        ->and($frames['treaty']['body'])->toContain('7 and 8 June 2003')
        ->and($frames['treaty']['body'])->toContain('58.85 per cent')
        ->and($frames['treaty']['body'])->toContain('77.45 per cent')
        // three countries opened their labour markets in 2004, not all of them
        ->and($frames['firstmay']['body'])->toContain('1 May 2004')
        ->and($frames['firstmay']['body'])->toContain('Ireland, Sweden and the United Kingdom')
        ->and($frames['firstmay']['body'])->toContain('2,540,000')
        ->and($frames['firstmay']['body'])->toContain('793,000')
        // the emigration numbers are estimates and the page says so
        ->and($frames['firstmay']['body'])->toContain('approximations rather than hard counts')
        // the money is given gross and net, with the window it covers
        ->and($frames['money']['body'])->toContain('245.5 billion euros')
        ->and($frames['money']['body'])->toContain('83.7 billion')
        ->and($frames['money']['body'])->toContain('end of 2023')
        ->and($frames['money']['body'])->toContain('720 kilometres')
        ->and($frames['money']['body'])->toContain('5,593 kilometres')
        ->and($frames['money']['body'])->toContain('21 December 2007')
        ->and($frames['money']['body'])->toContain('in 2011 and in 2025')
        // the row with Brussels is dated at both ends and attributed, never framed for a side
        ->and($frames['argument']['body'])->toContain('December 2017')
        ->and($frames['argument']['body'])->toContain('29 February 2024')
        ->and($frames['argument']['body'])->toContain('29 May 2024')
        ->and($frames['argument']['body'])->toContain('137 billion euros')
        // support is a poll with a date on it, not an assertion
        ->and($frames['argument']['body'])->toContain('82 per cent')
        ->and($frames['argument']['body'])->toContain('February 2026')
        ->and($frames['argument']['body'])->toContain('89 per cent for NATO in April 2025');

    expect(trans('site.euro.sources.body'))->toContain('Historic Documents of 1999')
        ->and(trans('site.euro.sources.body'))->toContain('dzieje.pl')
        ->and(trans('site.euro.sources.body'))->toContain('EUR-Lex');
});

it('writes the NATO and European Union names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.euro.frames'))->keyBy('key');

    expect($frames['grey']['body'])->toContain('Układ Warszawski')
        ->and($frames['grey']['body'])->toContain('Lecha Wałęsy')
        ->and($frames['door']['body'])->toContain('Sojusz Północnoatlantycki')
        ->and($frames['door']['body'])->toContain('Partnerstwo dla Pokoju')
        ->and($frames['door']['body'])->toContain('Waldemar Pawlak')
        ->and($frames['missouri']['body'])->toContain('Bronisław Geremek')
        ->and($frames['army']['body'])->toContain('Polska Zbrojna')
        ->and($frames['road']['body'])->toContain('Układ Europejski')
        ->and($frames['road']['body'])->toContain('Andrzej Olechowski')
        ->and($frames['treaty']['body'])->toContain('Państwowa Komisja Wyborcza')
        ->and($frames['firstmay']['body'])->toContain('Główny Urząd Statystyczny')
        ->and($frames['money']['body'])->toContain('Ministerstwo Finansów')
        ->and($frames['money']['body'])->toContain('Generalna Dyrekcja Dróg Krajowych i Autostrad')
        ->and($frames['money']['body'])->toContain('Radą Unii Europejskiej')
        ->and($frames['argument']['body'])->toContain('Komisja Europejska');

    app()->setLocale('en');
});

it('publishes no invented map on the NATO and European Union article', function (): void {
    // no published map of either enlargement was found under a licence this site can use,
    // so nothing hand-drawn stands in for evidence
    expect(file_get_contents(resource_path('js/pages/HistoryEurope.vue')))
        ->not->toContain('Map.vue')
        ->not->toContain('euro-map');
});

it('renders the article on Poland since 2004 as a filmstrip', function (): void {
    $this->get('/history/poland-since-2004')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryToday')
            ->has('translations.today.frames', 9)
        );
});

it('credits every frame of the article on Poland since 2004 and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['today']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/today-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/today-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/today-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/today-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['today']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/today-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.today.sources.body'))->toContain('Title picture')
        ->and(trans('site.today.sources.body'))->toContain('Cybularny')
        // the pictures under a share-alike licence say so, and the freest one says it is CC0
        ->and(collect(trans('site.today.frames'))->keyBy('key')['money']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.today.frames'))->keyBy('key')['refuge']['credit'])->toContain('CC BY 4.0');
});

it('uses pictures on the article about Poland since 2004 that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/today-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'today-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('closes the reading order with the article on Poland since 2004', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/poland-since-2004'))
        ->toBeGreaterThan(strpos($order, '/history/nato-and-the-eu'))
        ->and($order)->toMatch("#'/history/poland-since-2004',\s+titleKey: 'today.meta.title',\s+era: 'after',\s+index: 2,#");

    // it really is the last one, and the page therefore offers the first article instead of a next one
    preg_match_all("#href: '([^']+)'#", $order, $hrefs);
    expect(end($hrefs[1]))->toBe('/history/poland-since-2004');

    $component = file_get_contents(resource_path('js/pages/HistoryToday.vue'));

    expect($component)->toContain('const first = articles[0]')
        ->and($component)->toContain("t('today.restart')")
        ->and($component)->toContain('next ? next.href : first.href');

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.today.hero.kicker'))->toContain(trans('site.history.eras.after.label'))
            ->and(trans('site.today.hero.kicker'))->toContain('03')
            ->and(trans('site.today.meta.title'))->toContain(trans('site.history.eras.after.topics.2.title'))
            ->and(trans('site.today.restart'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/poland-since-2004')
        ->assertSee(e(trans('site.today.meta.title')), escape: false)
        ->assertSee('/images/cards/today.jpg', escape: false);
});

it('keeps every frame about Poland since 2004 inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['today']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the article on Poland since 2004 from the European Union one and back to the start', function (): void {
    $frames = collect(trans('site.today.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['leaving', 'fewer', 'arrivals', 'refuge', 'frontline', 'money', 'made', 'argues', 'now'])
        // it picks up where the article on NATO and the European Union stopped
        ->and($byKey['leaving']['body'])->toContain('The last topic counted the people who left after 1 May 2004')
        // and it sends the reader back to the first article rather than stopping dead
        ->and($byKey['now']['body'])->toContain('Mieszko')
        ->and($byKey['now']['body'])->toContain('where this section started');

    // every seam is written, so no frame starts as if it were a new page
    expect($byKey['fewer']['body'])->toStartWith('Emigration was only half of it')
        ->and($byKey['arrivals']['body'])->toStartWith('While Poles were going west')
        ->and($byKey['frontline']['body'])->toStartWith('The war did not stay on the far side');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('The Central Statistical Office, the government statisticians'))->toBe(array_search('leaving', $keys, true))
        ->and($firstMention('Eurostat, the statistical office of the European Union'))->toBe(array_search('fewer', $keys, true))
        ->and($firstMention('the social insurance institution, ZUS'))->toBe(array_search('arrivals', $keys, true))
        ->and($firstMention('the United Nations refugee agency, UNHCR'))->toBe(array_search('refuge', $keys, true))
        ->and($firstMention('BLIK, the Polish phone payment standard'))->toBe(array_search('money', $keys, true))
        ->and($firstMention('POLIN, the Museum of the History of Polish Jews'))->toBe(array_search('made', $keys, true))
        ->and($firstMention('Law and Justice, a conservative party'))->toBe(array_search('argues', $keys, true))
        ->and($firstMention('the Constitutional Tribunal, the court that reads the constitution'))->toBe(array_search('argues', $keys, true));
});

it('keeps the hard-won facts about Poland since 2004 the way the sources give them', function (): void {
    $frames = collect(trans('site.today.frames'))->keyBy('key');

    // the statisticians changed their definition, so the two emigration series are never lined up
    expect($frames['leaving']['body'])->toContain('1.51 million')
        ->and($frames['leaving']['body'])->toContain('projects')
        ->and($frames['leaving']['body'])->toContain('stricter rule than the old series')
        ->and($frames['leaving']['body'])->toContain('2.63 million')
        ->and($frames['leaving']['body'])->toContain('2.35 million')
        // deaths have outrun births since 2013, and the page gives both sides of the sum
        ->and($frames['fewer']['body'])->toContain('Since 2013')
        ->and($frames['fewer']['body'])->toContain('859,000')
        ->and($frames['fewer']['body'])->toContain('251,800')
        ->and($frames['fewer']['body'])->toContain('408,500')
        ->and($frames['fewer']['body'])->toContain('1.14 children per woman in 2024')
        ->and($frames['fewer']['body'])->toContain('1 April 2016')
        ->and($frames['fewer']['body'])->toContain('800 zloty a month on 1 January 2024')
        // the statisticians credit wages and jobs as well as the benefit, so the page does not claim the benefit did it
        ->and($frames['fewer']['body'])->toContain('helped as much by rising wages')
        // the Ukrainian labour migration was already large before the war
        ->and($frames['arrivals']['body'])->toContain('after 2014')
        ->and($frames['arrivals']['body'])->toContain('1.64 million')
        ->and($frames['arrivals']['body'])->toContain('627,000')
        ->and($frames['arrivals']['body'])->toContain('875,100')
        // no purpose-built camps: the state paid households, and the allowance had a limit
        ->and($frames['refuge']['body'])->toContain('24 February 2022')
        ->and($frames['refuge']['body'])->toContain('no refugee camps')
        ->and($frames['refuge']['body'])->toContain('40 zloty a person a day')
        ->and($frames['refuge']['body'])->toContain('for up to sixty days')
        ->and($frames['refuge']['body'])->toContain('4,567')
        ->and($frames['refuge']['body'])->toContain('35 per cent')
        ->and($frames['refuge']['body'])->toContain('15 per cent')
        ->and($frames['refuge']['body'])->toContain('969,250')
        ->and($frames['refuge']['body'])->toContain('857,100')
        // the aid share is an official claim, and the page says so instead of printing it as a measurement
        ->and($frames['frontline']['body'])->toContain('Rzeszow-Jasionka')
        ->and($frames['frontline']['body'])->toContain('their claim rather than a measurement')
        ->and($frames['frontline']['body'])->toContain('3.75 per cent')
        ->and($frames['frontline']['body'])->toContain('4.25 per cent')
        // convergence, wages and prices all carry the year they were measured
        ->and($frames['money']['body'])->toContain('52 per cent')
        ->and($frames['money']['body'])->toContain('81 per cent')
        ->and($frames['money']['body'])->toContain('9,509 zloty a month in July 2026')
        ->and($frames['money']['body'])->toContain('2.9 billion')
        ->and($frames['money']['body'])->toContain('7,510 zloty in 2015')
        ->and($frames['money']['body'])->toContain('16,783')
        // the museums and the prizes are dated from the institutions' own records
        ->and($frames['made']['body'])->toContain('October 2004')
        ->and($frames['made']['body'])->toContain('28 October 2014')
        ->and($frames['made']['body'])->toContain('1996')
        ->and($frames['made']['body'])->toContain('2018 prize')
        ->and($frames['made']['body'])->toContain('85 million copies by March 2026')
        // the political frame is dated and attributed at every point and takes nobody's side
        ->and($frames['argues']['body'])->toContain('15 October 2023')
        ->and($frames['argues']['body'])->toContain('74.38 per cent')
        ->and($frames['argues']['body'])->toContain('Donald Tusk')
        // the coal share is hedged to what the one institute behind it will carry
        ->and($frames['argues']['body'])->toContain("over half of Polish electricity in 2025 on Forum Energii's count")
        ->and($frames['argues']['body'])->toContain('April 2025')
        ->and($frames['argues']['body'])->toContain('22 October 2020')
        // the crowd is the city's estimate, never a count
        ->and($frames['argues']['body'])->toContain('The city put the march that followed at 100,000');

    expect(trans('site.today.sources.body'))->toContain('UNHCR')
        ->and(trans('site.today.sources.body'))->toContain('National Security Bureau')
        ->and(trans('site.today.sources.body'))->toContain('Journal of Laws')
        // the one figure the page rounds is given exactly where precision belongs
        ->and(trans('site.today.sources.body'))->toContain('52.2 per cent')
        // organiser-counted crowds are declined rather than quoted
        ->and(trans('site.today.sources.body'))->toContain('no attendance is quoted here');
});

it('writes the names on the article about Poland since 2004 in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.today.frames'))->keyBy('key');

    expect($frames['leaving']['body'])->toContain('Główny Urząd Statystyczny')
        ->and($frames['leaving']['body'])->toContain('województwie łódzkim')
        ->and($frames['fewer']['body'])->toContain('500 plus')
        ->and($frames['arrivals']['body'])->toContain('Zakład Ubezpieczeń Społecznych')
        ->and($frames['refuge']['body'])->toContain('UNHCR')
        ->and($frames['frontline']['body'])->toContain('Rzeszów-Jasionkę')
        ->and($frames['money']['body'])->toContain('Bank Światowy')
        ->and($frames['made']['body'])->toContain('Muzeum Powstania Warszawskiego')
        ->and($frames['made']['body'])->toContain('Muzeum Historii Żydów Polskich')
        ->and($frames['made']['body'])->toContain('Wisława Szymborska')
        ->and($frames['made']['body'])->toContain('Olga Tokarczuk')
        ->and($frames['argues']['body'])->toContain('Prawo i Sprawiedliwość')
        ->and($frames['argues']['body'])->toContain('Trybunał Konstytucyjny')
        ->and($frames['now']['body'])->toContain('Mieszka');

    app()->setLocale('en');
});

it('publishes no invented map on the article about Poland since 2004', function (): void {
    // the story is social and economic rather than spatial, and no published map of it was found,
    // so nothing hand-drawn stands in for evidence
    expect(file_get_contents(resource_path('js/pages/HistoryToday.vue')))
        ->not->toContain('Map.vue')
        ->not->toContain('today-map');
});

it('renders the September 1939 article as a filmstrip', function (): void {
    $this->get('/history/september-1939')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistorySeptember1939')
            ->has('translations.sept1939.frames', 9)
        );
});

it('credits every frame of the September 1939 article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['sept1939']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/sept1939-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/sept1939-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/sept1939-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/sept1939-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['sept1939']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/sept1939-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.sept1939.sources.body'))->toContain('Title picture')
        ->and(trans('site.sept1939.sources.body'))->toContain('defenders of Warsaw')
        // the two pictures under a share-alike licence say so
        ->and(collect(trans('site.sept1939.frames'))->keyBy('key')['forces']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.sept1939.frames'))->keyBy('key')['visiting']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the September 1939 article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/sept1939-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'sept1939-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the demarcation map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the September 1939 article after the Battle of Warsaw, as the third wars topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/september-1939'))
        ->toBeGreaterThan(strpos($order, '/history/battle-of-warsaw-1920'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/september-1939',\s+titleKey: 'sept1939.meta.title',\s+era: 'wars',\s+index: 2,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.sept1939.hero.kicker'))->toContain(trans('site.history.eras.wars.label'))
            ->and(trans('site.sept1939.hero.kicker'))->toContain('03')
            ->and(trans('site.sept1939.meta.title'))->toContain(trans('site.history.eras.wars.topics.2.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/september-1939')
        ->assertSee(e(trans('site.sept1939.meta.title')), escape: false)
        ->assertSee('/images/cards/sept1939.jpg', escape: false);
});

it('keeps every September 1939 frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['sept1939']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the September 1939 article from the Second Republic to the occupation', function (): void {
    $frames = collect(trans('site.sept1939.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['road', 'forces', 'first', 'battles', 'capital', 'soviet', 'zones', 'cost', 'visiting'])
        // it picks the story up where the Second Republic article left it
        ->and($byKey['road']['body'])->toContain('The republic was twenty years old')
        // the campaign runs in order, and the pact is explained before the second invasion
        ->and($byKey['road']['body'])->toContain('23 August')
        ->and($byKey['soviet']['body'])->toContain('the other half of the August pact')
        ->and($byKey['zones']['body'])->toContain('6 October')
        // Katyn is a signpost forward, not this article's subject
        ->and($byKey['cost']['body'])->toContain('Katyn')
        ->and($frames->take(7)->pluck('body')->implode(' '))->not->toContain('Katyn');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Joachim von Ribbentrop'))->toBe(array_search('road', $keys, true))
        ->and($firstMention('Jozef Lipski'))->toBe(array_search('road', $keys, true))
        ->and($firstMention('Neville Chamberlain'))->toBe(array_search('road', $keys, true))
        ->and($firstMention('Colonel Kazimierz Mastalerz'))->toBe(array_search('forces', $keys, true))
        ->and($firstMention('Indro Montanelli'))->toBe(array_search('forces', $keys, true))
        ->and($firstMention('Major Henryk Sucharski'))->toBe(array_search('first', $keys, true))
        ->and($firstMention('Grzegorz Bebnik'))->toBe(array_search('first', $keys, true))
        ->and($firstMention('Colonel Julian Filipowicz'))->toBe(array_search('battles', $keys, true))
        ->and($firstMention('General Tadeusz Kutrzeba'))->toBe(array_search('battles', $keys, true))
        ->and($firstMention('General Wladyslaw Bortnowski'))->toBe(array_search('battles', $keys, true))
        ->and($firstMention('General Walerian Czuma'))->toBe(array_search('capital', $keys, true))
        ->and($firstMention('Stefan Starzynski'))->toBe(array_search('capital', $keys, true))
        ->and($firstMention('Vladimir Potemkin'))->toBe(array_search('soviet', $keys, true))
        ->and($firstMention('Waclaw Grzybowski'))->toBe(array_search('soviet', $keys, true))
        ->and($firstMention('Edward Rydz-Smigly'))->toBe(array_search('soviet', $keys, true))
        ->and($firstMention('Ignacy Moscicki'))->toBe(array_search('soviet', $keys, true))
        ->and($firstMention('General Franciszek Kleeberg'))->toBe(array_search('zones', $keys, true))
        ->and($firstMention('Tadeusz Panecki'))->toBe(array_search('cost', $keys, true));
});

it('keeps the hard-won September 1939 facts the way the sources give them', function (): void {
    $frames = collect(trans('site.sept1939.frames'))->keyBy('key');

    // the demands, the denunciation, the guarantee and the pact, each with its date
    expect($frames['road']['body'])->toContain('24 October 1938')
        ->and($frames['road']['body'])->toContain('28 April')
        ->and($frames['road']['body'])->toContain('31 March 1939')
        ->and($frames['road']['body'])->toContain('Narew')
        // Danzig and the corridor are explained where they first appear
        ->and($frames['road']['body'])->toContain('League of Nations')
        ->and($frames['road']['body'])->toContain('the strip that gave Poland the sea')
        // the strengths are attributed, because the published figures differ
        ->and($frames['forces']['body'])->toContain('Institute of National Remembrance')
        ->and($frames['forces']['body'])->toContain('880')
        ->and($frames['forces']['body'])->toContain('two thirds mobilised')
        // the cavalry legend is named as a legend and traced to its source
        ->and($frames['forces']['body'])->toContain('never happened')
        ->and($frames['forces']['body'])->toContain('Krojanty')
        ->and($frames['forces']['body'])->toContain('propaganda minister, Joseph Goebbels')
        // both disputes about the first morning are shown as disputes
        ->and($frames['first']['body'])->toContain('4.40')
        ->and($frames['first']['body'])->toContain('5.40')
        ->and($frames['first']['body'])->toContain('4.45')
        ->and($frames['first']['body'])->toContain('seven days')
        ->and($frames['first']['body'])->toContain('fourteen hours')
        // Mokra is the counter-example to the legend: guns, not sabres
        ->and($frames['battles']['body'])->toContain('Mokra')
        ->and($frames['battles']['body'])->toContain('anti-tank guns')
        ->and($frames['battles']['body'])->toContain('4th Panzer Division')
        ->and($frames['battles']['body'])->toContain('9 September')
        ->and($frames['battles']['body'])->toContain('22 September')
        // the siege, with the civilian toll given as the range it is
        ->and($frames['capital']['body'])->toContain('8 September')
        ->and($frames['capital']['body'])->toContain('25 September')
        ->and($frames['capital']['body'])->toContain('13.15 on 28 September')
        ->and($frames['capital']['body'])->toContain('Estimates')
        ->and($frames['capital']['body'])->toContain('ten to twenty-five thousand')
        // the allies declared war on 3 September and did not attack, which the reader will ask about
        ->and($frames['capital']['body'])->toContain('3 September')
        ->and($frames['capital']['body'])->toContain('Britain and France')
        // the Soviet note and its stated pretext, and the first-day strength
        ->and($frames['soviet']['body'])->toContain('620,000')
        ->and($frames['soviet']['body'])->toContain('ceased to exist')
        ->and($frames['soviet']['body'])->toContain('Romania')
        // the last fights and the treaty that fixed the two zones
        ->and($frames['zones']['body'])->toContain('16,860')
        ->and($frames['zones']['body'])->toContain('2 October')
        ->and($frames['zones']['body'])->toContain('28 September')
        ->and($frames['zones']['body'])->toContain('Lithuania')
        // Kleeberg commanded something, and the page says what
        ->and($frames['zones']['body'])->toContain('Polesie group')
        // the casualty figure carries the estimate it comes from and the revision
        ->and($frames['cost']['body'])->toContain('66,000')
        ->and($frames['cost']['body'])->toContain('January 1947')
        ->and($frames['cost']['body'])->toContain('95,000 to 97,000')
        ->and($frames['cost']['body'])->toContain('420,000')
        ->and($frames['cost']['body'])->toContain('320,000 to 340,000')
        // the page says plainly that the civilian dead of the campaign were never counted
        ->and($frames['cost']['body'])->toContain('never counted nationally')
        // the NKVD is glossed the first and only time it appears
        ->and($frames['cost']['body'])->toContain('Soviet secret police, the NKVD')
        ->and($frames['visiting']['body'])->toContain('Westerplatte')
        ->and($frames['visiting']['body'])->toContain('Sochaczew');

    // the page never repeats the legend as fact
    expect(collect(trans('site.sept1939.frames'))->pluck('body')->implode(' '))
        ->not->toContain('charged German tanks');
});

it('writes the September 1939 names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.sept1939.frames'))->keyBy('key');

    expect($frames['road']['body'])->toContain('Józefa Lipskiego')
        ->and($frames['road']['body'])->toContain('Wolnego Miasta Gdańska')
        ->and($frames['forces']['body'])->toContain('18 Pułk Ułanów Pomorskich')
        ->and($frames['forces']['body'])->toContain('Kazimierza Mastalerza')
        ->and($frames['forces']['body'])->toContain('Krojantami')
        ->and($frames['first']['body'])->toContain('Wielunia')
        ->and($frames['first']['body'])->toContain('Bębnik')
        ->and($frames['first']['body'])->toContain('Sucharskiego')
        ->and($frames['battles']['body'])->toContain('Wołyńska Brygada Kawalerii')
        ->and($frames['battles']['body'])->toContain('Śmiały')
        ->and($frames['battles']['body'])->toContain('Łęczycę')
        ->and($frames['capital']['body'])->toContain('Stefan Starzyński')
        ->and($frames['capital']['body'])->toContain('Stację Filtrów')
        ->and($frames['soviet']['body'])->toContain('Rydz-Śmigły')
        ->and($frames['soviet']['body'])->toContain('Mościcki')
        ->and($frames['zones']['body'])->toContain('Kleeberga')
        ->and($frames['cost']['body'])->toContain('Katyń')
        ->and($frames['visiting']['body'])->toContain('Wieluń');

    app()->setLocale('en');
});

it('publishes the real signed demarcation map on the September 1939 article', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/sept1939-map-{$size}.jpg"))->toBeReadableFile();
    }

    // a document, captioned with what it is, who signed it, when and in what language
    expect(trans('site.sept1939.map.caption'))->toContain('28 September 1939')
        ->and(trans('site.sept1939.map.caption'))->toContain('German')
        ->and(trans('site.sept1939.map.caption'))->toContain('Ribbentrop')
        ->and(trans('site.sept1939.map.credit'))->toContain('Nuremberg')
        ->and(trans('site.sept1939.map.alt'))->not->toBeEmpty()
        ->and(trans('site.sept1939.map.caption', [], 'pl'))->toContain('28 września 1939')
        ->and(trans('site.sept1939.map.credit', [], 'pl'))->toContain('norymberskiego')
        ->and(trans('site.sept1939.map.alt', [], 'pl'))->not->toBeEmpty();

    // no hand-drawn schematic stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistorySeptember1939.vue')))
        ->not->toContain('Map.vue')
        ->toContain('sept1939-map-lg.jpg')
        ->toContain('with-caption');
});

it('renders the occupation article as a filmstrip', function (): void {
    $this->get('/history/occupation-and-holocaust')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryOccupation')
            ->has('translations.occupation.frames', 10)
        );
});

it('credits every frame of the occupation article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['occupation']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/occupation-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/occupation-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/occupation-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/occupation-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['occupation']['frames'])->pluck('photo')->unique())->toHaveCount(10);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/occupation-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.occupation.sources.body'))->toContain('Title picture')
        ->and(trans('site.occupation.sources.body'))->toContain('Palmiry')
        // the share-alike pictures say so
        ->and(collect(trans('site.occupation.frames'))->keyBy('key')['zones']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.occupation.frames'))->keyBy('key')['revolt']['credit'])->toContain('CC BY 3.0');
});

it('uses pictures on the occupation article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/occupation-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'occupation-'))
        ->map(fn (string $file): string => md5_file($file));

    // ten frames, the title picture and the map of the General Government
    expect($ours)->toHaveCount(12)
        ->and($ours->unique())->toHaveCount(12)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('places the occupation article after September 1939, as the fourth wars topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/occupation-and-holocaust'))
        ->toBeGreaterThan(strpos($order, '/history/september-1939'))
        ->toBeLessThan(strpos($order, '/history/second-world-war'))
        ->and($order)->toMatch("#'/history/occupation-and-holocaust',\s+titleKey: 'occupation.meta.title',\s+era: 'wars',\s+index: 3,#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.occupation.hero.kicker'))->toContain(trans('site.history.eras.wars.label'))
            ->and(trans('site.occupation.hero.kicker'))->toContain('04')
            ->and(trans('site.occupation.meta.title'))->toContain(trans('site.history.eras.wars.topics.3.title'));
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/occupation-and-holocaust')
        ->assertSee(e(trans('site.occupation.meta.title')), escape: false)
        ->assertSee('/images/cards/occupation.jpg', escape: false);
});

it('keeps every occupation frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['occupation']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the occupation article from September 1939 to the Warsaw Uprising', function (): void {
    $frames = collect(trans('site.occupation.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['zones', 'elites', 'east', 'daily', 'ghettos', 'camps', 'revolt', 'neighbours', 'totals', 'visiting'])
        // it picks the story up where September 1939 left it, with the country occupied and not surrendered
        ->and($byKey['zones']['body'])->toContain('never surrendered')
        ->and($byKey['zones']['body'])->toContain('October 1939')
        // the Soviet decision is announced at the end of one frame and taken at the start of the next
        ->and($byKey['elites']['body'])->toContain('Moscow worked by decision')
        ->and($byKey['east']['body'])->toContain('5 March 1940')
        // the ghettos come before the killing centres, and the reader is told who was shut in
        ->and($byKey['ghettos']['body'])->toContain('largest Jewish community in Europe')
        ->and($byKey['camps']['body'])->toContain('22 June 1941');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Hans Frank'))->toBe(array_search('zones', $keys, true))
        ->and($firstMention('Wladyslaw Anders'))->toBe(array_search('east', $keys, true))
        ->and($firstMention('Odilo Globocnik'))->toBe(array_search('camps', $keys, true))
        ->and($firstMention('Jan Karski'))->toBe(array_search('camps', $keys, true))
        ->and($firstMention('Mordechai Anielewicz'))->toBe(array_search('revolt', $keys, true))
        ->and($firstMention('Emanuel Ringelblum'))->toBe(array_search('revolt', $keys, true))
        ->and($firstMention('Irena Sendlerowa'))->toBe(array_search('neighbours', $keys, true));

    // the acronyms and the institutions are glossed where they first appear
    expect($byKey['east']['body'])->toContain('the NKVD, the Soviet secret police')
        ->and($byKey['east']['body'])->toContain('the Politburo, the Soviet party leadership')
        // the second occupier is named, not left as "the east"
        ->and($byKey['zones']['body'])->toContain('The Soviet Union took everything east of it')
        // Stalin arrives with his job attached
        ->and($byKey['east']['body'])->toContain('the Soviet leader Joseph Stalin')
        // the reader is told that Germany then held the Soviet zone too
        ->and($byKey['camps']['body'])->toContain('eastern Poland among them')
        // the government the underground state answered to is placed
        ->and($byKey['daily']['body'])->toContain('Polish government now in London');

    // the Warsaw Uprising is the next article, not this one
    expect($frames->pluck('body')->implode(' '))->not->toContain('1 August 1944');
});

it('keeps the hard-won occupation facts the way the sources give them', function (): void {
    $frames = collect(trans('site.occupation.frames'))->keyBy('key');

    // the two systems, each named and dated
    expect($frames['zones']['body'])->toContain('General Government')
        ->and($frames['zones']['body'])->toContain('22 October 1939')
        ->and($frames['zones']['body'])->toContain('1 and 2 November')
        // the attack on the elites carries the institute that counted it
        ->and($frames['elites']['body'])->toContain('Intelligenzaktion')
        ->and($frames['elites']['body'])->toContain('30 March 1940')
        ->and($frames['elites']['body'])->toContain('Institute of National Remembrance')
        ->and($frames['elites']['body'])->toContain('50,000')
        ->and($frames['elites']['body'])->toContain('Palmiry')
        ->and($frames['elites']['body'])->toContain('183')
        // Katyn and the deportations, with the archival count and the date of the admission
        ->and($frames['east']['body'])->toContain('22,000')
        ->and($frames['east']['body'])->toContain('330,000 to 340,000')
        ->and($frames['east']['body'])->toContain('the records of the NKVD')
        ->and($frames['east']['body'])->toContain('April 1943')
        ->and($frames['east']['body'])->toContain('1990')
        // daily life, with the decree dated and the forced-labour figure hedged the way USHMM hedges it
        ->and($frames['daily']['body'])->toContain('lapanka')
        ->and($frames['daily']['body'])->toContain('at least 1.5 million')
        ->and($frames['daily']['body'])->toContain('15 October 1941')
        ->and($frames['daily']['body'])->toContain('90,000')
        // the ghetto figures are attributed, because they are estimates made by an institution
        ->and($frames['ghettos']['body'])->toContain('October 1940')
        ->and($frames['ghettos']['body'])->toContain('400,000')
        ->and($frames['ghettos']['body'])->toContain('United States Holocaust Memorial Museum')
        ->and($frames['ghettos']['body'])->toContain('1,125 calories')
        ->and($frames['ghettos']['body'])->toContain('83,000')
        // the killing centres are named individually and dated
        ->and($frames['camps']['body'])->toContain('Chelmno')
        ->and($frames['camps']['body'])->toContain('8 December 1941')
        ->and($frames['camps']['body'])->toContain('Belzec')
        ->and($frames['camps']['body'])->toContain('Sobibor')
        ->and($frames['camps']['body'])->toContain('Treblinka')
        ->and($frames['camps']['body'])->toContain('Operation Reinhard')
        ->and($frames['camps']['body'])->toContain('1.7 million')
        ->and($frames['camps']['body'])->toContain('Majdanek')
        // the uprising and the revolts, each with its date
        ->and($frames['revolt']['body'])->toContain('265,000')
        ->and($frames['revolt']['body'])->toContain('19 April 1943')
        ->and($frames['revolt']['body'])->toContain('16 May')
        ->and($frames['revolt']['body'])->toContain('2 August 1943')
        ->and($frames['revolt']['body'])->toContain('milk cans')
        // Zegota is described the careful way POLIN and the institute describe it
        ->and($frames['neighbours']['body'])->toContain('4 December 1942')
        ->and($frames['neighbours']['body'])->toContain('only rescue body in occupied Europe funded by a state')
        // the number of children is not the disputed one
        ->and($frames['neighbours']['body'])->toContain('several hundred children')
        ->and($frames['neighbours']['body'])->not->toContain('2,500')
        ->and($frames['neighbours']['body'])->toContain('24 March 1944')
        // Jedwabne is given as the institute's own investigation gives it
        ->and($frames['neighbours']['body'])->toContain('10 July 1941')
        ->and($frames['neighbours']['body'])->toContain('at least 340')
        ->and($frames['neighbours']['body'])->toContain('local Polish men')
        ->and($frames['neighbours']['body'])->toContain('German instigation')
        ->and($frames['neighbours']['body'])->not->toContain('1,600')
        // the totals are a range and the page says who is counting
        ->and($frames['totals']['body'])->toContain('5.6')
        ->and($frames['totals']['body'])->toContain('1.8 to 1.9 million')
        ->and($frames['totals']['body'])->toContain('None of it is settled')
        // the visiting frame is practical and says plainly what the place is
        ->and($frames['visiting']['body'])->toContain('burial ground, not an attraction')
        ->and($frames['visiting']['body'])->toContain('visit.auschwitz.org')
        ->and($frames['visiting']['body'])->toContain('POLIN');

    // the calorie triad that circulates without an institutional source is not printed
    $all = collect(trans('site.occupation.frames'))->pluck('body')->implode(' ');

    expect($all)->not->toContain('2,600 calories')
        // the camps are never assigned to Poland, in any wording
        ->and($all)->not->toContain('Polish death camp')
        ->and($all)->not->toContain('Polish camp')
        // and the page never claims the death penalty for helping Jews was unique to Poland
        ->and($all)->not->toContain('only occupied country');

    // the sources say why that last claim is left out
    expect(trans('site.occupation.sources.body'))->toContain('not call it unique')
        ->and(trans('site.occupation.sources.body'))->toContain('Belarus, Ukraine and Serbia');
});

it('writes the occupation names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.occupation.frames'))->keyBy('key');

    expect($frames['zones']['body'])->toContain('Generalnym Gubernatorstwem')
        ->and($frames['zones']['body'])->toContain('Wolne Miasto Gdańsk')
        ->and($frames['elites']['body'])->toContain('Palmirach')
        ->and($frames['elites']['body'])->toContain('Puszczy Kampinoskiej')
        ->and($frames['east']['body'])->toContain('Katyń')
        ->and($frames['east']['body'])->toContain('Władysław Anders')
        ->and($frames['east']['body'])->toContain('Łubianki')
        ->and($frames['daily']['body'])->toContain('Łapanka')
        ->and($frames['daily']['body'])->toContain('tajne komplety')
        ->and($frames['ghettos']['body'])->toContain('Łódzkie getto')
        ->and($frames['camps']['body'])->toContain('Bełżec')
        ->and($frames['camps']['body'])->toContain('Sobibór')
        ->and($frames['camps']['body'])->toContain('Chełmno')
        ->and($frames['revolt']['body'])->toContain('Żydowska Organizacja Bojowa')
        ->and($frames['revolt']['body'])->toContain('Anielewicza')
        ->and($frames['neighbours']['body'])->toContain('Żegota')
        ->and($frames['neighbours']['body'])->toContain('Sendlerowa')
        ->and($frames['neighbours']['body'])->toContain('Ulmów')
        ->and($frames['neighbours']['body'])->toContain('Jedwabnem')
        ->and($frames['visiting']['body'])->toContain('Muzeum Katyńskie');

    app()->setLocale('en');
});

it('publishes a real wartime map of the General Government on the occupation article', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/occupation-map-{$size}.jpg"))->toBeReadableFile();
    }

    // a published map, captioned with what it is, when it was drawn and in what languages
    expect(trans('site.occupation.map.caption'))->toContain('1943')
        ->and(trans('site.occupation.map.caption'))->toContain('1:1,000,000')
        ->and(trans('site.occupation.map.caption'))->toContain('German')
        ->and(trans('site.occupation.map.caption'))->toContain('Polish')
        ->and(trans('site.occupation.map.credit'))->toContain('Archiwum Glowne Akt Dawnych')
        ->and(trans('site.occupation.map.alt'))->not->toBeEmpty()
        ->and(trans('site.occupation.map.caption', [], 'pl'))->toContain('1943')
        ->and(trans('site.occupation.map.credit', [], 'pl'))->toContain('Archiwum Główne Akt Dawnych')
        ->and(trans('site.occupation.map.alt', [], 'pl'))->not->toBeEmpty();

    // no hand-drawn schematic stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryOccupation.vue')))
        ->not->toContain('Map.vue')
        ->toContain('occupation-map-lg.jpg')
        ->toContain('with-caption');
});

it('renders the Free City of Danzig article as a filmstrip', function (): void {
    $this->get('/history/free-city-of-danzig')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryDanzig')
            ->has('translations.danzig.frames', 9)
        );
});

it('credits every frame of the Danzig article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['danzig']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/danzig-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/danzig-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/danzig-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/danzig-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['danzig']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/danzig-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.danzig.sources.body'))->toContain('Title picture')
        ->and(trans('site.danzig.sources.body'))->toContain('Motlawa waterfront')
        // the one picture under a share-alike licence says so
        ->and(collect(trans('site.danzig.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY-SA 3.0');
});

it('uses pictures on the Danzig article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/danzig-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'danzig-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the corridor map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Danzig article off the first topic of the war overview', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after September 1939, with no era band slot
    expect(strpos($order, '/history/free-city-of-danzig'))
        ->toBeGreaterThan(strpos($order, '/history/september-1939'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/free-city-of-danzig',\s+titleKey: 'danzig.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'invasion.0',#")
        // the lookup the war page uses, and the old one, both survive
        ->and($order)->toContain('export const articleForWarTopic')
        ->and($order)->toContain('export const articleFor')
        ->and($order)->toContain('export const nextAfter');

    // the war page renders a topic with an article as a link and the rest as plain text
    $page = file_get_contents(resource_path('js/pages/HistoryWar.vue'));

    expect($page)->toContain('articleForWarTopic')
        // a topic with an article becomes an Inertia link, one without stays a plain div
        ->toContain('hrefForTopic(chapter.key, index)')
        ->toContain('? Link')
        ->toContain(": 'div'");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.danzig.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.danzig.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/free-city-of-danzig')
        ->assertSee(e(trans('site.danzig.meta.title')), escape: false)
        ->assertSee('/images/cards/danzig.jpg', escape: false);
});

it('points every warTopic at a chapter and a topic that exist in both languages', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    preg_match_all("#warTopic: '([a-z0-9-]+)\.(\d+)'#", $order, $matches, PREG_SET_ORDER);

    // the mechanism is in use, otherwise this test would pass by being empty
    expect($matches)->not->toBeEmpty();

    foreach (['en', 'pl'] as $locale) {
        $chapters = (require lang_path("{$locale}/site.php"))['war']['chapters'];

        foreach ($matches as [, $chapter, $index]) {
            // a warTopic that names a chapter or a position the copy does not have
            // would silently stop linking, in one language only
            expect(array_key_exists($chapter, $chapters))->toBeTrue()
                ->and(array_key_exists((int) $index, $chapters[$chapter]['topics']))->toBeTrue();
        }
    }
});

it('keeps every Danzig frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['danzig']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Danzig article from the corridor to the borders moving', function (): void {
    $frames = collect(trans('site.danzig.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['what', 'why', 'rights', 'friction', 'gdynia', 'nazi', 'crisis', 'end', 'today'])
        // the reader is told what the League of Nations and the Volkstag were, where they first appear
        ->and($byKey['what']['body'])->toContain('the body founded that same year to keep the peace')
        ->and($byKey['what']['body'])->toContain('a parliament called the Volkstag')
        // where Poland came from, before the corridor is named
        ->and($byKey['why']['body'])->toContain('123 years carved up between Russia, Prussia and Austria')
        ->and($byKey['why']['body'])->toContain('known as the Polish corridor')
        // the 1920 refusal is the reason for both Westerplatte and Gdynia, and the text says so twice
        ->and($byKey['friction']['body'])->toContain('why Poland wanted a depot of its own')
        ->and($byKey['gdynia']['body'])->toContain('drew the obvious conclusion')
        // it hands off to the article on the borders rather than retelling 1945
        // the end of the city hands off to the article on the borders instead of retelling it
        ->and($byKey['end']['body'])->toContain('30 March 1945')
        ->and($byKey['end']['body'])->toContain('the lands Poland lost in the east');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Woodrow Wilson'))->toBe(array_search('why', $keys, true))
        ->and($firstMention('Roman Dmowski'))->toBe(array_search('why', $keys, true))
        ->and($firstMention('David Lloyd George'))->toBe(array_search('why', $keys, true))
        ->and($firstMention('Marian Chodacki'))->toBe(array_search('rights', $keys, true))
        ->and($firstMention('Tadeusz Wenda'))->toBe(array_search('gdynia', $keys, true))
        ->and($firstMention('Eugeniusz Kwiatkowski'))->toBe(array_search('gdynia', $keys, true))
        ->and($firstMention('Albert Forster'))->toBe(array_search('nazi', $keys, true))
        ->and($firstMention('Arthur Greiser'))->toBe(array_search('nazi', $keys, true))
        ->and($firstMention('Sean Lester'))->toBe(array_search('nazi', $keys, true))
        ->and($firstMention('Joachim von Ribbentrop'))->toBe(array_search('crisis', $keys, true))
        ->and($firstMention('Jozef Lipski'))->toBe(array_search('crisis', $keys, true))
        ->and($firstMention('Marcel Deat'))->toBe(array_search('crisis', $keys, true));

    // the September campaign has its own article and is not retold here
    expect($frames->pluck('body')->implode(' '))
        ->not->toContain('Bzura')
        ->not->toContain('Krojanty')
        ->not->toContain('Kock');
});

it('keeps the hard-won Danzig facts the way the sources give them', function (): void {
    $frames = collect(trans('site.danzig.frames'))->keyBy('key');

    // what the Free City was, with the dates the sources actually support
    expect($frames['what']['body'])->toContain('15 November 1920')
        ->and($frames['what']['body'])->toContain('articles 100 to 108')
        // the constitution was passed on 11 August 1920, not on the election day of 16 May
        ->and($frames['what']['body'])->toContain('11 August 1920')
        // the gulden is a 1923 currency, the Bank von Danzig is the 1924 part
        ->and($frames['what']['body'])->toContain('from 1923')
        // the census figure is given as the census, so the reader can see what it measures
        ->and($frames['what']['body'])->toContain('366,730')
        ->and($frames['what']['body'])->toContain('mother tongue')
        // and the sources explain why 95 per cent is not the whole story
        ->and(trans('site.danzig.sources.body'))->toContain('35,000 to 40,000')
        ->and(trans('site.danzig.sources.body'))->toContain('over 90 per cent')
        // Wilson's point is dated and numbered
        ->and($frames['why']['body'])->toContain('8 January 1918')
        ->and($frames['why']['body'])->toContain('thirteenth')
        // Poland's rights, and Westerplatte with both of its dates
        ->and($frames['rights']['body'])->toContain('9 November 1920')
        ->and($frames['rights']['body'])->toContain('5 January 1925')
        ->and($frames['rights']['body'])->toContain('March 1924')
        ->and($frames['rights']['body'])->toContain('October 1925')
        ->and($frames['rights']['body'])->toContain('18 January 1926')
        ->and($frames['rights']['body'])->toContain('88')
        // the 1920 refusal, with the date and who actually unloaded the ship
        ->and($frames['friction']['body'])->toContain('21 July 1920')
        ->and($frames['friction']['body'])->toContain('Triton')
        ->and($frames['friction']['body'])->toContain('27 July')
        // the count of disputes is attributed, not asserted
        ->and($frames['friction']['body'])->toContain('One legal study counts 106')
        // the Wicher, without the shelling order that traces only to popular history
        ->and($frames['friction']['body'])->toContain('15 June 1932')
        ->and($frames['friction']['body'])->toContain('Nothing was fired')
        // Gdynia, with the tonnages attributed to the encyclopaedia that publishes them
        ->and($frames['gdynia']['body'])->toContain('23 September 1922')
        ->and($frames['gdynia']['body'])->toContain('1,200')
        ->and($frames['gdynia']['body'])->toContain('29 April 1923')
        ->and($frames['gdynia']['body'])->toContain('June 1925')
        ->and($frames['gdynia']['body'])->toContain('Encyklopedia Gdanska')
        ->and($frames['gdynia']['body'])->toContain('8.6 million tonnes in 1928')
        ->and($frames['gdynia']['body'])->toContain('9.2 million')
        // Forster was 28, and the party fell short of two thirds in 1935
        ->and($frames['nazi']['body'])->toContain('28-year-old')
        ->and($frames['nazi']['body'])->toContain('28 May 1933')
        ->and($frames['nazi']['body'])->toContain('38 of the 72 seats')
        ->and($frames['nazi']['body'])->toContain('7 April 1935')
        ->and($frames['nazi']['body'])->toContain('two thirds')
        ->and($frames['nazi']['body'])->toContain('14 October 1936')
        // the Jewish community, with the community's own decision and the numbers that are solid
        ->and($frames['crisis']['body'])->toContain('24 October 1938')
        ->and($frames['crisis']['body'])->toContain('12 and 13 November 1938')
        ->and($frames['crisis']['body'])->toContain('21 November')
        ->and($frames['crisis']['body'])->toContain('16 December')
        ->and($frames['crisis']['body'])->toContain('1,272')
        ->and($frames['crisis']['body'])->toContain('4 May')
        // the customs inspectors affair, and how the Senate got out of it
        ->and($frames['crisis']['body'])->toContain('4 August')
        ->and($frames['crisis']['body'])->toContain('customs inspectors')
        ->and($frames['crisis']['body'])->toContain('denied it had ever said so')
        // the Senate, not the Volkstag, made Forster head of state
        ->and($frames['end']['body'])->toContain('the Senate made Forster head of state')
        // the post office defenders were shot after a trial, and the page says so
        ->and($frames['end']['body'])->toContain('after two courts martial')
        // and the ship was a training ship, not a modern battleship
        ->and($frames['end']['body'])->toContain('training ship')
        ->and($frames['end']['body'])->toContain('25 August')
        ->and($frames['end']['body'])->toContain('1 September')
        ->and($frames['end']['body'])->toContain('5 October')
        ->and($frames['end']['body'])->toContain('2 September')
        ->and($frames['today']['body'])->toContain('5 October 2026')
        ->and($frames['today']['body'])->toContain('23 March 2017');

    // figures the research could not settle are not printed as facts
    $everything = collect(trans('site.danzig.frames'))->pluck('body')->implode(' ').' '.trans('site.danzig.sources.body');

    expect($everything)->not->toContain('the nearest Danzig government building is shelled')
        ->and(trans('site.danzig.sources.body'))->toContain('no archival source for it was found')
        // how many Jews were left in September 1939 is unsettled, so no number is printed
        ->and(trans('site.danzig.sources.body'))->toContain('so no number is printed')
        // the busiest-port claim is declined rather than repeated
        ->and(trans('site.danzig.sources.body'))->toContain('busiest port on the Baltic is a claim this page does not make');
});

it('writes the Danzig names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.danzig.frames'))->keyBy('key');

    expect($frames['what']['body'])->toContain('Wolne Miasto Gdańsk')
        ->and($frames['what']['body'])->toContain('Ligi Narodów')
        ->and($frames['what']['body'])->toContain('guldena gdańskiego')
        ->and($frames['why']['body'])->toContain('Wisły')
        ->and($frames['why']['body'])->toContain('polskim korytarzem')
        ->and($frames['rights']['body'])->toContain('Komisarz Generalny RP')
        ->and($frames['rights']['body'])->toContain('Westerplatte')
        ->and($frames['friction']['body'])->toContain('Armia Czerwona')
        ->and($frames['gdynia']['body'])->toContain('Encyklopedia Gdańska')
        ->and($frames['gdynia']['body'])->toContain('Kwiatkowski')
        ->and($frames['nazi']['body'])->toContain('Volkstagu')
        ->and($frames['crisis']['body'])->toContain('Józefa Lipskiego')
        ->and($frames['crisis']['body'])->toContain('Żydzi')
        ->and($frames['end']['body'])->toContain('Poczty Polskiej')
        ->and($frames['end']['body'])->toContain('Zaspie')
        ->and($frames['today']['body'])->toContain('Główne Miasto')
        ->and($frames['today']['body'])->toContain('Solidarność');

    app()->setLocale('en');
});

it('publishes a real published map of the corridor on the Danzig article', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/danzig-map-{$size}.jpg"))->toBeReadableFile();
    }

    // a real map is a source: maker, date and language in the caption
    expect(trans('site.danzig.map.caption'))->toContain('Meyers Grosser Hausatlas')
        ->and(trans('site.danzig.map.caption'))->toContain('1938')
        ->and(trans('site.danzig.map.caption'))->toContain('in German')
        ->and(trans('site.danzig.map.caption'))->toContain('corridor')
        ->and(trans('site.danzig.map.alt'))->not->toBeEmpty()
        ->and(trans('site.danzig.map.caption', [], 'pl'))->toContain('1938')
        ->and(trans('site.danzig.map.caption', [], 'pl'))->toContain('po niemiecku')
        ->and(trans('site.danzig.map.alt', [], 'pl'))->not->toBeEmpty();

    // no hand-drawn schematic stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryDanzig.vue')))
        ->not->toContain('Map.vue')
        ->toContain('danzig-map-lg.jpg')
        ->toContain('with-caption');
});

it('renders the Westerplatte article as a filmstrip', function (): void {
    $this->get('/history/westerplatte')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryWesterplatte')
            ->has('translations.westerplatte.frames', 9)
        );
});

it('credits every frame of the Westerplatte article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['westerplatte']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/westerplatte-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/westerplatte-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/westerplatte-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/westerplatte-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['westerplatte']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/westerplatte-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.westerplatte.sources.body'))->toContain('Title picture')
        ->and(trans('site.westerplatte.sources.body'))->toContain('Monument to the Coast Defenders')
        // the pictures under a share-alike licence say so
        ->and(collect(trans('site.westerplatte.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.westerplatte.frames'))->keyBy('key')['seven']['credit'])->toContain('CC BY-SA 3.0 DE');
});

it('uses pictures on the Westerplatte article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/westerplatte-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'westerplatte-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the sea chart
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Westerplatte article off the second topic of the war overview', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the Free City, with no era band slot
    expect(strpos($order, '/history/westerplatte'))
        ->toBeGreaterThan(strpos($order, '/history/free-city-of-danzig'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/westerplatte',\s+titleKey: 'westerplatte.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'invasion.1',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.westerplatte.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.westerplatte.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/westerplatte')
        ->assertSee(e(trans('site.westerplatte.meta.title')), escape: false)
        ->assertSee('/images/cards/westerplatte.jpg', escape: false);
});

it('keeps every Westerplatte frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['westerplatte']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Westerplatte article from the depot to the peninsula today', function (): void {
    $frames = collect(trans('site.westerplatte.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['depot', 'night', 'dawn', 'seven', 'dispute', 'surrender', 'cost', 'after', 'today'])
        // the reader is told what the League of Nations was doing in Gdansk before the depot is granted
        ->and($byKey['depot']['body'])->toContain('a small German-speaking state under the League of Nations')
        // Gdansk is glossed, because the page calls the city Danzig throughout
        ->and($byKey['depot']['body'])->toContain('today Gdansk')
        // the 88 of the treaty and the two hundred actually there are set against each other
        ->and($byKey['depot']['body'])->toContain('88 men')
        ->and($byKey['night']['body'])->toContain('rather than the permitted 88')
        // the SS-Heimwehr Danzig is explained where it first appears
        ->and($byKey['surrender']['body'])->toContain('the German force raised in the city')
        // the campaign and the Free City have their own articles and are not retold
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Bzura')
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Volkstag')
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Gdynia');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Henryk Sucharski'))->toBe(array_search('night', $keys, true))
        ->and($firstMention('Franciszek Dabrowski'))->toBe(array_search('night', $keys, true))
        ->and($firstMention('Wilhelm Henningsen'))->toBe(array_search('night', $keys, true))
        ->and($firstMention('Jaroslaw Tuliszka'))->toBe(array_search('dawn', $keys, true))
        ->and($firstMention('Grzegorz Bebnik'))->toBe(array_search('dawn', $keys, true))
        ->and($firstMention('Mieczyslaw Slaby'))->toBe(array_search('seven', $keys, true))
        ->and($firstMention('Walter Schug'))->toBe(array_search('dispute', $keys, true))
        ->and($firstMention('Melchior Wankowicz'))->toBe(array_search('dispute', $keys, true))
        ->and($firstMention('Andrzej Drzycimski'))->toBe(array_search('dispute', $keys, true))
        ->and($firstMention('Friedrich-Georg Eberhardt'))->toBe(array_search('surrender', $keys, true))
        ->and($firstMention('Kazimierz Rasinski'))->toBe(array_search('cost', $keys, true))
        ->and($firstMention('Franciszek Duszenko'))->toBe(array_search('today', $keys, true));
});

it('keeps the hard-won Westerplatte facts the way the sources give them', function (): void {
    $frames = collect(trans('site.westerplatte.frames'))->keyBy('key');
    $sources = trans('site.westerplatte.sources.body');

    // one field gun and two anti-tank guns, not two field guns
    expect($frames['depot']['body'])->toContain('one 75 mm field gun, two 37 mm anti-tank guns')
        ->and($sources)->toContain('the popular version with two field guns confuses')
        // six guardhouses, four of them concrete in 1933 and 1934, the barracks about 1936
        ->and($frames['depot']['body'])->toContain('Six guardhouses')
        ->and($frames['depot']['body'])->toContain('1933 and 1934')
        ->and($frames['depot']['body'])->toContain('March 1924')
        // the garrison is a range in the sources and about two hundred in the copy
        ->and($frames['night']['body'])->toContain('about two hundred men')
        ->and($sources)->toContain('182 to 240')
        // the ship was a training ship of 1906, not a modern battleship
        ->and($frames['night']['body'])->toContain('pre-dreadnought battleship of 1906')
        ->and($frames['night']['body'])->toContain('1936')
        ->and($frames['night']['body'])->toContain('SMS Magdeburg')
        ->and($frames['night']['body'])->toContain('about 225 marines')
        ->and($frames['night']['body'])->toContain('26 August')
        // both hours of the first salvo, with the historian who reconciles them
        ->and($frames['dawn']['body'])->toContain('4.45')
        ->and($frames['dawn']['body'])->toContain('4.48')
        ->and($frames['dawn']['body'])->toContain('Jaroslaw Tuliszka')
        // the first-shot question stays open, with Tczew named
        ->and($frames['dawn']['body'])->toContain('Tczew')
        ->and($frames['dawn']['body'])->toContain('4.40')
        ->and($frames['dawn']['body'])->toContain('1961')
        // Bebnik doubts the early hour, he does not argue it away with German summer time
        ->and($sources)->toContain('the clocks in Germany and Poland agreed')
        // the raid of 2 September, with the numbers that hold up
        ->and($frames['seven']['body'])->toContain('2 September')
        ->and($frames['seven']['body'])->toContain('sixty Ju 87')
        ->and($frames['seven']['body'])->toContain('26.5 tonnes')
        ->and($frames['seven']['body'])->toContain('Guardhouse No 5')
        ->and($frames['seven']['body'])->toContain('at least six men')
        ->and($frames['seven']['body'])->toContain('6 September')
        // Slaby was a captain and a doctor, and the customs men had taken his kit
        ->and($frames['seven']['body'])->toContain('Captain Mieczyslaw Slaby, the only doctor')
        ->and($sources)->toContain('a captain and a doctor, not a junior lieutenant')
        // the command dispute is a dispute, with the German report dated and attributed
        ->and($frames['dispute']['body'])->toContain('2 September')
        ->and($frames['dispute']['body'])->toContain('No Polish document confirms any of it')
        ->and($frames['dispute']['body'])->toContain('1959')
        ->and($frames['dispute']['body'])->toContain('2001')
        // the surrender times and the general who took it
        ->and($frames['surrender']['body'])->toContain('9.45')
        ->and($frames['surrender']['body'])->toContain('10.15')
        // the sabre is given as contested, with what actually happened to it
        ->and($frames['surrender']['body'])->toContain('taken from him in his first prison camp')
        ->and($sources)->toContain('The sabre is left as contested on purpose')
        // the casualties, attributed on both sides
        ->and($frames['cost']['body'])->toContain('Fifteen Polish soldiers')
        ->and($frames['cost']['body'])->toContain('12 September')
        ->and($frames['cost']['body'])->toContain('Encyklopedia Gdanska')
        ->and($frames['cost']['body'])->toContain('three to four hundred dead, which no German casualty return supports')
        ->and($frames['cost']['body'])->toContain('about fifty killed')
        // what became of the two officers
        ->and($frames['after']['body'])->toContain('30 August 1946')
        ->and($frames['after']['body'])->toContain('Casamassima')
        ->and($frames['after']['body'])->toContain('21 August 1971')
        ->and($frames['after']['body'])->toContain('1 September')
        ->and($frames['after']['body'])->toContain('kiosk in Krakow')
        ->and($frames['after']['body'])->toContain('1962')
        // the grave was found in 2019 and the burial was in 2022, which are not the same year
        ->and($frames['today']['body'])->toContain('2019')
        ->and($frames['today']['body'])->toContain('4 November 2022')
        ->and($frames['today']['body'])->toContain('9 October 1966')
        ->and($frames['today']['body'])->toContain('1967')
        ->and($frames['today']['body'])->toContain('February 2026')
        ->and($frames['today']['body'])->toContain('4.45');

    // figures the research could not settle are not printed as facts
    $everything = collect(trans('site.westerplatte.frames'))->pluck('body')->implode(' ').' '.$sources;

    expect($everything)->not->toContain('thirteen attacks')
        ->and($sources)->toContain('no source outside Wikipedia was found for that figure')
        // the hour of the dive-bomber raid could not be confirmed, so it is not given
        ->and(collect(trans('site.westerplatte.frames'))->pluck('body')->implode(' '))->not->toContain('18.05')
        ->and($sources)->toContain('could not be confirmed, so it is not given')
        // the guardhouses disguised as villas is a claim this page declines to make
        ->and($sources)->toContain('the guardhouses being disguised as villas')
        // the distance guardhouse No 1 was moved is not published, so no figure appears
        ->and($sources)->toContain('by a distance none of these sources gives')
        // and the poem is named as the source of the belief that they all died
        ->and($sources)->toContain('Piesn o zolnierzach z Westerplatte')
        ->and($sources)->toContain('the defenders all died, which they did not');
});

it('writes the Westerplatte names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $frames = collect(trans('site.westerplatte.frames'))->keyBy('key');

    expect($frames['depot']['body'])->toContain('Martwej Wisły')
        ->and($frames['depot']['body'])->toContain('Ligi Narodów')
        ->and($frames['depot']['body'])->toContain('Wolnego Miasta Gdańska')
        ->and($frames['depot']['body'])->toContain('składnicę tranzytową')
        ->and($frames['night']['body'])->toContain('major Henryk Sucharski')
        ->and($frames['night']['body'])->toContain('kapitan Franciszek Dąbrowski')
        ->and($frames['dawn']['body'])->toContain('Grzegorz Bębnik')
        ->and($frames['dawn']['body'])->toContain('Instytutu Pamięci Narodowej')
        ->and($frames['seven']['body'])->toContain('Mieczysław Słaby')
        ->and($frames['dispute']['body'])->toContain('Melchior Wańkowicz')
        ->and($frames['surrender']['body'])->toContain('skapitulował')
        ->and($frames['cost']['body'])->toContain('Kazimierza Rasińskiego')
        ->and($frames['cost']['body'])->toContain('Encyklopedia Gdańska')
        ->and($frames['after']['body'])->toContain('Drugiego Korpusu Polskiego')
        ->and($frames['today']['body'])->toContain('Muzeum II Wojny Światowej')
        ->and($frames['today']['body'])->toContain('Franciszka Duszeńki')
        ->and($frames['today']['body'])->toContain('Muzeum Gdańska');

    app()->setLocale('en');
});

it('publishes a real published chart of the harbour mouth on the Westerplatte article', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/westerplatte-map-{$size}.jpg"))->toBeReadableFile();
    }

    // a real map is a source: maker, date and language in the caption
    expect(trans('site.westerplatte.map.caption'))->toContain('Reichs-Marine-Amt')
        ->and(trans('site.westerplatte.map.caption'))->toContain('1909')
        ->and(trans('site.westerplatte.map.caption'))->toContain('in German')
        ->and(trans('site.westerplatte.map.caption'))->toContain('1:15000')
        ->and(trans('site.westerplatte.map.alt'))->not->toBeEmpty()
        ->and(trans('site.westerplatte.map.caption', [], 'pl'))->toContain('1909')
        ->and(trans('site.westerplatte.map.caption', [], 'pl'))->toContain('po niemiecku')
        ->and(trans('site.westerplatte.map.alt', [], 'pl'))->not->toBeEmpty();

    // no hand-drawn schematic stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryWesterplatte.vue')))
        ->not->toContain('Map.vue')
        ->toContain('westerplatte-map-lg.jpg')
        ->toContain('with-caption');
});

it('renders the pact article as a filmstrip', function (): void {
    $this->get('/history/molotov-ribbentrop-pact')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryPact')
            ->has('translations.pact.frames', 9)
        );
});

it('credits every frame of the pact article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['pact']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/pact-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/pact-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/pact-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/pact-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['pact']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/pact-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.pact.sources.body'))->toContain('Title picture')
        ->and(trans('site.pact.sources.body'))->toContain('Reich law gazette')
        // the pictures under a share-alike licence say so, and the free one says that
        ->and(collect(trans('site.pact.frames'))->keyBy('key')['protocol']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.pact.frames'))->keyBy('key')['memory']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.pact.frames'))->keyBy('key')['denial']['credit'])->toContain('CC0');
});

it('uses pictures on the pact article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/pact-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'pact-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the newspaper map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the pact article off the fourth topic of the war overview', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the peninsula, with no era band slot
    expect(strpos($order, '/history/molotov-ribbentrop-pact'))
        ->toBeGreaterThan(strpos($order, '/history/september-1939'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/molotov-ribbentrop-pact',\s+titleKey: 'pact.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'invasion.3',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.pact.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.pact.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/molotov-ribbentrop-pact')
        ->assertSee(e(trans('site.pact.meta.title')), escape: false)
        ->assertSee('/images/cards/pact.jpg', escape: false);
});

it('keeps every pact frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['pact']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the pact article from the Moscow talks to the museums', function (): void {
    $frames = collect(trans('site.pact.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['talks', 'signing', 'protocol', 'poland', 'cost', 'trade', 'denial', 'memory', 'visiting'])
        // the reader is told what the three earlier partitions were before a fourth is named
        ->and($byKey['talks']['body'])->toContain('erased his country from the map between 1772 and 1795')
        // Munich explains why Stalin did not trust the offer from the west
        ->and($byKey['talks']['body'])->toContain('At Munich six months earlier')
        // Stalin is introduced with his role in the sentence that first names him
        ->and($byKey['signing']['body'])->toStartWith('Joseph Stalin, the Soviet leader,')
        // Leningrad and Saint Petersburg are the same city and the page says so
        ->and($byKey['cost']['body'])->toContain('the city now called Saint Petersburg')
        // the Reichsmark, the Congress and the archive are all placed where they appear
        ->and($byKey['trade']['body'])->toContain('the German currency of the day')
        ->and($byKey['denial']['body'])->toContain('the new Soviet parliament')
        ->and($byKey['denial']['body'])->toContain('Communist Party archive in Moscow')
        // Putin held two different offices in 2009 and in 2019
        ->and($byKey['memory']['body'])->toContain('then prime minister')
        ->and($byKey['memory']['body'])->toContain('as president')
        // the Karelian Isthmus and Bessarabia are placed, not dropped as bare names
        ->and($byKey['cost']['body'])->toContain('the land bridge north of Leningrad')
        ->and($byKey['protocol']['body'])->toContain('then a province of Romania')
        // the campaign and the two Gdansk articles are not retold here
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Westerplatte')
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Bzura')
        ->and($frames->pluck('body')->implode(' '))->not->toContain('Danzig');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Reginald Drax'))->toBe(array_search('talks', $keys, true))
        ->and($firstMention('Joseph Doumenc'))->toBe(array_search('talks', $keys, true))
        ->and($firstMention('Kliment Voroshilov'))->toBe(array_search('talks', $keys, true))
        ->and($firstMention('Jozef Beck'))->toBe(array_search('talks', $keys, true))
        ->and($firstMention('Joachim von Ribbentrop'))->toBe(array_search('signing', $keys, true))
        ->and($firstMention('Vyacheslav Molotov'))->toBe(array_search('signing', $keys, true))
        ->and($firstMention('Adolf Hitler'))->toBe(array_search('signing', $keys, true))
        ->and($firstMention('Edward Ericson'))->toBe(array_search('trade', $keys, true))
        ->and($firstMention('Jan Szumski'))->toBe(array_search('denial', $keys, true))
        ->and($firstMention('Vladimir Putin'))->toBe(array_search('memory', $keys, true));
});

it('keeps the hard-won pact facts the way the documents give them', function (): void {
    $frames = collect(trans('site.pact.frames'))->keyBy('key');
    $sources = trans('site.pact.sources.body');

    // the transit question and the Polish refusal, both dated
    expect($frames['talks']['body'])->toContain('On 12 August')
        ->and($frames['talks']['body'])->toContain('cross Poland and Romania')
        ->and($frames['talks']['body'])->toContain('20 August')
        // the treaty was signed at two in the morning of the 24th, not during the 23rd
        ->and($frames['signing']['body'])->toContain('at two the following morning')
        // the toast exists only in the German record and is attributed there
        ->and($frames['signing']['body'])->toContain('The German record of the evening')
        ->and($frames['signing']['body'])->toContain('No Soviet record of that toast')
        // the protocol has four points and names the three rivers
        ->and($frames['protocol']['body'])->toContain('four numbered points')
        ->and($frames['protocol']['body'])->toContain('Narew, Vistula and San')
        ->and($frames['protocol']['body'])->toContain('northern border of Lithuania')
        ->and($frames['protocol']['body'])->toContain('Bessarabia')
        // the 28 September swap, and the split attributed to the museum that gives it
        ->and($frames['poland']['body'])->toContain('province of Lublin')
        ->and($frames['poland']['body'])->toContain('which records the Soviet killing of Polish prisoners in 1940')
        ->and($frames['poland']['body'])->toContain('38 per cent')
        // the Winter War and the 1940 annexations are dated
        ->and($frames['cost']['body'])->toContain('30 November 1939')
        ->and($frames['cost']['body'])->toContain('northern Bukovina')
        // the trade figures are the contract sums, and the judgement is attributed
        ->and($frames['trade']['body'])->toContain('200 million Reichsmarks')
        ->and($frames['trade']['body'])->toContain('180 million Reichsmarks')
        ->and($frames['trade']['body'])->toContain('22 June 1941')
        ->and($sources)->toContain('No tonnage of grain or oil actually delivered is printed here')
        // the denial, the admission and the find are each dated
        ->and($frames['denial']['body'])->toContain('25 March 1946')
        ->and($frames['denial']['body'])->toContain('24 December 1989')
        ->and($frames['denial']['body'])->toContain('no original had been found')
        ->and($frames['denial']['body'])->toContain('invalid from the day it was signed')
        // the remembrance dates and the two Putin statements, dated and unjudged
        ->and($frames['memory']['body'])->toContain('about two million people')
        ->and($frames['memory']['body'])->toContain('23 September 2008')
        ->and($frames['memory']['body'])->toContain('31 August 2009')
        ->and($frames['memory']['body'])->toContain('20 December 2019')
        ->and($sources)->toContain('This page takes no position on that argument')
        // where a reader can go and what they will find
        ->and($frames['visiting']['body'])->toContain('Warsaw Citadel')
        ->and($frames['visiting']['body'])->toContain('22,000')
        ->and($frames['visiting']['body'])->toContain('Avalon Project');

    app()->setLocale('pl');

    $polish = collect(trans('site.pact.frames'))->keyBy('key');

    expect($polish['talks']['body'])->toContain('Klimenta Woroszyłowa')
        ->and($polish['talks']['body'])->toContain('Monachium')
        ->and($polish['signing']['body'])->toStartWith('Józef Stalin, przywódca Związku Sowieckiego,')
        ->and($polish['cost']['body'])->toContain('Petersburgiem')
        ->and($polish['memory']['body'])->toContain('wtedy premier')
        ->and($polish['talks']['body'])->toContain('czwartym rozbiorem')
        ->and($polish['signing']['body'])->toContain('Wiaczesławem Mołotowem')
        ->and($polish['protocol']['body'])->toContain('Narwi, Wisły i Sanu')
        ->and($polish['poland']['body'])->toContain('dokumentuje sowieckie wymordowanie polskich jeńców w 1940 roku')
        ->and($polish['cost']['body'])->toContain('Przesmyk Karelski')
        ->and($polish['trade']['body'])->toContain('22 czerwca 1941')
        ->and($polish['denial']['body'])->toContain('Zjazd Deputowanych Ludowych ZSRR')
        ->and($polish['denial']['body'])->toContain('Fałszerze historii')
        ->and($polish['memory']['body'])->toContain('Parlament Europejski')
        ->and($polish['visiting']['body'])->toContain('Instytut Pamięci Narodowej');

    app()->setLocale('en');
});

it('publishes the newspaper map of the 1939 line on the pact article', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/pact-map-{$size}.jpg"))->toBeReadableFile();
    }

    // a real map is a source: maker, date and language in the caption
    expect(trans('site.pact.map.caption'))->toContain('Izvestia')
        ->and(trans('site.pact.map.caption'))->toContain('18 September 1939')
        ->and(trans('site.pact.map.caption'))->toContain('in Russian')
        ->and(trans('site.pact.map.alt'))->not->toBeEmpty()
        ->and(trans('site.pact.map.caption', [], 'pl'))->toContain('Izwiestia')
        ->and(trans('site.pact.map.caption', [], 'pl'))->toContain('po rosyjsku')
        ->and(trans('site.pact.map.alt', [], 'pl'))->not->toBeEmpty();

    // no hand-drawn schematic stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryPact.vue')))
        ->not->toContain('Map.vue')
        ->toContain('pact-map-lg.jpg')
        ->toContain('with-caption');
});

it('renders the Second World War article as chapters over a chronology', function (): void {
    $this->get('/history/second-world-war')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryWar')
            ->has('translations.war.chapters', 6)
            ->has('translations.war.timeline.items', 9)
            ->has('translations.war.visiting.sites', 5)
        );

    // the topics are notes on the page, not links promising pages that will never exist
    $source = file_get_contents(resource_path('js/pages/HistoryWar.vue'));

    expect($source)->not->toContain('href="#"')
        ->and($source)->toContain("t('war.sources.label')");
});

it('ships and credits the pictures the war page shows', function (): void {
    // the backdrop behind the title, which is also the card the history index shows
    foreach ([1000, 1400] as $width) {
        expect(public_path("images/tlo-memory-{$width}.jpg"))->toBeReadableFile();
    }

    $entry = PageSeo::entry('HistoryWar');

    expect($entry['group'])->toBe('war')
        ->and($entry['image'])->toBe('/images/tlo-memory-1400.jpg');

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        // author and licence, both, for the backdrop
        expect($copy['war']['sources']['body'])->toContain('MichalPL')
            ->and($copy['war']['sources']['body'])->toContain('CC0')
            ->and($copy['war']['sources']['label'])->not->toBeEmpty();
    }

    // the background is decoration, so it carries an empty alt rather than prose
    expect(file_get_contents(resource_path('js/pages/HistoryWar.vue')))
        ->toContain('src="/images/tlo-memory-1000.jpg"')
        ->toContain('alt=""');
});

/**
 * Every chapter of the war page, and the visiting section, carries one piece of
 * evidence: a photograph taken at the time, a document, or a memorial today.
 *
 * @return list<string>
 */
function warPictureSlots(): array
{
    return ['invasion', 'occupation', 'holocaust', 'resistance', 'uprising', 'aftermath', 'visiting'];
}

it('gives every war chapter its own captioned, credited picture', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");
        $blocks = $copy['war']['chapters'];
        $blocks['visiting'] = $copy['war']['visiting'];

        expect(array_keys($blocks))->toBe(warPictureSlots());

        foreach ($blocks as $slot => $block) {
            expect($block)->toHaveKeys(['photo', 'alt', 'caption', 'credit'])
                ->and($block['photo'])->toBe($slot)
                ->and(mb_strlen($block['caption']))->toBeGreaterThanOrEqual(40, "{$locale} {$slot} caption is too short")
                ->and(mb_strlen($block['alt']))->toBeGreaterThanOrEqual(40, "{$locale} {$slot} alt is too short");

            // a credit names a person or an institution and the licence it was released under
            expect($block['credit'])->not->toBeEmpty()
                ->and($block['credit'])->toMatch('#(public domain|domena publiczna|CC BY|CC0)#')
                ->and(preg_replace('#(public domain|domena publiczna|CC BY[A-Z0-9 .-]*|CC0|Wikimedia Commons|,)#', '', $block['credit']))
                ->toMatch('#\p{Lu}#u', "{$locale} {$slot} credit names nobody");
        }
    }
});

it('ships both sizes of every war picture, at the right width and weight', function (): void {
    foreach (warPictureSlots() as $slot) {
        foreach (['sm' => 960, 'lg' => 1280] as $size => $width) {
            $file = public_path("images/war-{$slot}-{$size}.jpg");

            expect($file)->toBeReadableFile()
                ->and(getimagesize($file)[0])->toBe($width, "war-{$slot}-{$size} is not {$width} wide");
        }

        // a heavy picture is a slow page on a phone abroad
        expect(filesize(public_path("images/war-{$slot}-lg.jpg")))->toBeLessThan(460_000);
    }
});

it('uses pictures on the war page that no other page already shows', function (): void {
    $ours = collect(warPictureSlots())
        ->map(fn (string $slot): string => md5_file(public_path("images/war-{$slot}-lg.jpg")));

    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'war-'))
        ->map(fn (string $file): string => md5_file($file));

    expect($ours->unique())->toHaveCount(count(warPictureSlots()))
        ->and($ours->intersect($others))->toBeEmpty();
});

it('renders the war pictures next to the text, each one openable and captioned', function (): void {
    $source = file_get_contents(resource_path('js/pages/HistoryWar.vue'));

    expect($source)->toContain('ExhibitImage')
        // the chapter pictures are addressed by the chapter key, so none can be forgotten
        ->and($source)->toContain('/images/war-${chapter.key}-sm.jpg')
        ->and($source)->toContain('/images/war-${chapter.key}-lg.jpg')
        ->and($source)->toContain('war.chapters.${chapter.key}.alt')
        ->and($source)->toContain('war.chapters.${chapter.key}.caption')
        ->and($source)->toContain('war.chapters.${chapter.key}.credit')
        ->and($source)->toContain("t('war.visiting.caption')")
        ->and($source)->toContain("t('war.visiting.credit')")
        // the visiting picture is wired by name, because its slot is not a chapter key
        ->and($source)->toContain('/images/war-visiting-sm.jpg')
        ->and($source)->toContain('/images/war-visiting-lg.jpg');

    // the caption and the credit are printed under the picture, not only inside the viewer
    expect(substr_count($source, '<figcaption'))->toBe(2);
});

it('places the Second World War article after September 1939 and answers the 1944 era topic', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    expect(strpos($order, '/history/second-world-war'))
        ->toBeGreaterThan(strpos($order, '/history/battle-of-warsaw-1920'))
        ->toBeGreaterThan(strpos($order, '/history/september-1939'))
        ->toBeGreaterThan(strpos($order, '/history/occupation-and-holocaust'))
        // the deep articles answer 1939 and the occupation now; the overview keeps 1944
        ->and($order)->toMatch("#'/history/second-world-war',\s+titleKey: 'war.meta.title',\s+era: 'wars',\s+(//[^\n]*\n\s+)*index: 4,#")
        ->and($order)->not->toContain('alsoIndexes: [4]')
        // the helper still supports one article answering several topics
        ->and($order)->toContain('article.alsoIndexes?.includes(index)');

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.war.hero.kicker'))->toContain(trans('site.history.eras.wars.label'));
    }

    app()->setLocale('en');

    $this->get('/history/second-world-war')
        ->assertSee(e(trans('site.war.meta.title')), escape: false)
        ->assertSee('/images/cards/war.jpg', escape: false);
});

it('keeps every war chapter inside the layout budgets in every language', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['war']['chapters'] as $key => $chapter) {
            expect($chapter)->toHaveKeys(['index', 'label', 'title', 'lede', 'topics'])
                ->and(count($chapter['topics']))->toBeGreaterThanOrEqual(4, "{$key} needs topics")
                ->and(mb_strlen($chapter['lede']))
                ->toBeGreaterThanOrEqual(210, "{$locale} {$key}.lede is too short")
                ->toBeLessThanOrEqual(340, "{$locale} {$key}.lede is too long");

            foreach ($chapter['topics'] as $index => $topic) {
                // short titles are proper names: Enigma, Getta, Straty
                expect(mb_strlen($topic['title']))
                    ->toBeGreaterThanOrEqual(5, "{$locale} {$key}.{$index}.title is too short")
                    ->toBeLessThanOrEqual(38, "{$locale} {$key}.{$index}.title is too long")
                    ->and(mb_strlen($topic['note']))
                    ->toBeGreaterThanOrEqual(53, "{$locale} {$key}.{$index}.note is too short")
                    ->toBeLessThanOrEqual(175, "{$locale} {$key}.{$index}.note is too long");
            }
        }
    }
});

it('bridges the war article from Danzig to the Soviet zone and on to 1945', function (): void {
    $chapters = trans('site.war.chapters');
    $timeline = collect(trans('site.war.timeline.items'));

    // the reader is told what Gdansk was before the shelling starts there
    expect($chapters['invasion']['topics'][0]['title'])->toContain('Free City of Danzig')
        ->and($chapters['invasion']['topics'][0]['note'])->toContain('League of Nations')
        // the pact has the two men who signed it, not just their surnames on a label
        ->and($chapters['invasion']['lede'])->toContain('Joachim von Ribbentrop')
        ->and($chapters['invasion']['lede'])->toContain('Vyacheslav Molotov')
        // the government leaves with a name attached
        ->and($chapters['invasion']['topics'][4]['note'])->toContain('Wladyslaw Sikorski')
        // June 1941 is the hinge: without it the Soviet zone vanishes off the page
        ->and($chapters['occupation']['lede'])->toContain('June 1941')
        ->and($timeline->pluck('title')->implode(' '))->toContain('Germany attacks the Soviet Union')
        ->and($timeline->pluck('year')->implode(' '))->toContain('22 Jun 1941')
        // the resistance army is named before the uprising uses the name
        ->and($chapters['resistance']['lede'])->toContain('Home Army')
        ->and($chapters['resistance']['lede'])->toContain('Armia Krajowa')
        // who ran the General Government, and who shot the officers at Katyn
        ->and($chapters['occupation']['lede'])->toContain('Hans Frank')
        ->and($chapters['occupation']['topics'][2]['note'])->toContain('NKVD')
        // the powers at Yalta are named, not counted
        ->and($chapters['aftermath']['topics'][0]['note'])->toContain('The United States, Britain and the Soviet Union');
});

it('keeps the hard-won war facts the way the sources give them', function (): void {
    $chapters = trans('site.war.chapters');
    $all = collect($chapters)->pluck('topics')->flatten(1)->pluck('note')->implode(' ');

    // Operation Reinhard was three camps, not all six
    expect($chapters['holocaust']['topics'][1]['note'])->toContain('Belzec, Sobibor and Treblinka')
        ->and($chapters['holocaust']['topics'][1]['note'])->toContain('Operation Reinhard')
        ->and($chapters['holocaust']['lede'])->not->toContain('Operation Reinhard')
        // the September campaign has its two end dates
        ->and($chapters['invasion']['topics'][2]['note'])->toContain('28 September')
        ->and($chapters['invasion']['topics'][2]['note'])->toContain('6 October')
        ->and($chapters['invasion']['topics'][3]['note'])->toContain('23 August 1939')
        // the ghetto uprising in the museum's own terms
        ->and($chapters['holocaust']['topics'][2]['note'])->toContain('largest Jewish revolt')
        ->and($chapters['holocaust']['topics'][2]['note'])->toContain('first significant urban revolt')
        // Zegota is dated instead of being called the only one of its kind
        ->and($chapters['holocaust']['topics'][3]['note'])->toContain('December 1942')
        ->and($all)->not->toContain('only state-backed')
        // rescue is not the whole story, and the page says the rest of it
        ->and($chapters['holocaust']['topics'][4]['note'])->toContain('denounced')
        ->and($chapters['holocaust']['topics'][4]['note'])->toContain('blackmailed')
        // the codebreakers are named
        ->and($chapters['resistance']['topics'][3]['note'])->toContain('Marian Rejewski')
        ->and($chapters['resistance']['topics'][3]['note'])->toContain('Jerzy Rozycki')
        ->and($chapters['resistance']['topics'][3]['note'])->toContain('Henryk Zygalski')
        ->and($chapters['resistance']['topics'][1]['note'])->toContain('1948')
        // 1944: the civilian toll is a range, and the Soviet halt is not asserted as a motive
        ->and($chapters['uprising']['topics'][1]['note'])->toContain('150,000 to 200,000')
        ->and($chapters['uprising']['lede'])->toContain('historians still argue')
        ->and($chapters['uprising']['topics'][2]['note'])->toContain('January 1945')
        // the 85 per cent is cumulative, and the page says what it counts
        ->and($chapters['uprising']['topics'][2]['note'])->toContain('Counting 1939 and the ghetto')
        // war dead as a range, not the round six million
        ->and($chapters['aftermath']['topics'][1]['note'])->toContain('5.6 million')
        ->and($chapters['aftermath']['topics'][1]['note'])->toContain('three million of them Polish Jews')
        ->and($chapters['aftermath']['topics'][3]['note'])->toContain('Bernardo Bellotto')
        // Westerplatte is the symbolic start, never "the first shots"
        ->and(trans('site.war.timeline.items.0.note'))->toContain('symbolic start')
        ->and($all)->not->toContain('first shots');

    // the wording correction stays, in both languages
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        expect($copy['war']['language']['body'])->not->toBeEmpty()
            ->and($copy['war']['hero']['note'])->not->toBeEmpty();
    }
});

it('writes the war names in their Polish forms in Polish', function (): void {
    app()->setLocale('pl');
    $chapters = trans('site.war.chapters');

    expect($chapters['invasion']['topics'][0]['title'])->toBe('Wolne Miasto Gdańsk')
        ->and($chapters['invasion']['lede'])->toContain('Wiaczesław Mołotow')
        ->and($chapters['invasion']['topics'][4]['note'])->toContain('Władysława Sikorskiego')
        ->and($chapters['occupation']['topics'][3]['note'])->toContain('Zamościa')
        ->and($chapters['holocaust']['topics'][1]['note'])->toContain('Bełżec, Sobibór i Treblinka')
        ->and($chapters['holocaust']['topics'][1]['note'])->toContain('akcji Reinhardt')
        ->and($chapters['holocaust']['topics'][3]['note'])->toContain('Żegotę')
        ->and($chapters['holocaust']['topics'][4]['note'])->toContain('szmalcowników')
        ->and($chapters['resistance']['lede'])->toContain('Armię Krajową')
        ->and($chapters['resistance']['topics'][3]['note'])->toContain('Jerzym Różyckim')
        ->and($chapters['resistance']['topics'][3]['note'])->toContain('Henrykiem Zygalskim')
        ->and($chapters['uprising']['lede'])->toContain('decyzji Stalina')
        ->and($chapters['aftermath']['topics'][3]['note'])->toContain('Bernarda Bellotta')
        ->and(trans('site.war.timeline.items.3.title'))->toBe('Niemcy atakują Związek Radziecki');

    app()->setLocale('en');
});

it('captions every published map with its maker and lets the reader open it', function (): void {
    $maps = [
        'origins' => ['map', 'origins-tribesmap'],
        'faiths' => ['commonwealth', 'faiths-commonwealth'],
        'grunwald' => ['periodmap', 'grunwald-prussia'],
        'partition2' => ['periodmap', 'partition2-laurie'],
        'partition3' => ['periodmap', 'partition3-faden'],
        'republic' => ['periodmap', 'republic-atlas1920'],
        'war1920' => ['periodmap', 'war1920-sketch'],
        'danzig' => ['map', 'danzig-map'],
        'westerplatte' => ['map', 'westerplatte-map'],
        'pact' => ['map', 'pact-map'],
    ];

    foreach ($maps as $group => [$key, $file]) {
        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/{$file}-{$size}.jpg"))->toBeReadableFile();
        }

        foreach (['en', 'pl'] as $locale) {
            $copy = (require lang_path("{$locale}/site.php"))[$group][$key];

            // a real map is a source: it carries its maker and a date, like a painting
            expect($copy['alt'])->not->toBeEmpty()
                ->and($copy['caption'])->not->toBeEmpty()
                ->and($copy['credit'])->toMatch('/1[5-9]\d\d|20\d\d/');
        }
    }
});

it('renders the first city page', function (): void {
    $this->get('/places/gdansk')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PlaceGdansk')
            ->has('translations.gdansk.route.stops', 6)
            ->has('translations.gdansk.gallery.shots', 11)
            ->has('translations.gdansk.history.items', 8)
        );
});

it('ships every photograph the city page asks for, with a credit', function (): void {
    foreach (['gdansk', 'warszawa', 'krakow', 'wroclaw', 'lodz', 'poznan', 'szczecin', 'bydgoszcz', 'lublin', 'katowice', 'zakopane', 'torun', 'malbork', 'wieliczka', 'bialowieza', 'mazury'] as $city) {
        $copy = trans("site.{$city}");

        $slots = collect($copy['route']['stops'])->pluck('photo')
            ->merge(collect($copy['gallery']['shots'])->pluck('photo'))
            ->merge(collect($copy['neighbours']['items'] ?? [])->pluck('photo'))
            ->push('panorama')
            ->unique();

        foreach ($slots as $slot) {
            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/{$city}-{$slot}-{$size}.jpg"))->toBeReadableFile();
            }
        }

        foreach ($copy['gallery']['shots'] as $shot) {
            expect($shot['credit'])->not->toBeEmpty()
                ->and($shot['w'])->toBeGreaterThan(0)
                ->and($shot['h'])->toBeGreaterThan(0);
        }

        foreach ($copy['route']['stops'] as $stop) {
            expect($stop['credit'])->toMatch('/CC0|CC BY|public domain|domena publiczna/i')
                // typed into Google Maps, so it stays plain ASCII
                ->and($stop['query'])->toMatch('/^[A-Za-z0-9 ]+$/');
        }
    }

    $copy = trans('site.gdansk');

    foreach ($copy['route']['stops'] as $stop) {
        expect($stop['credit'])->toContain('CC');
    }

    foreach ($copy['gallery']['shots'] as $shot) {
        expect($shot['credit'])->not->toBeEmpty()
            // the gallery reserves each picture's space, so it needs its real size
            ->and($shot['w'])->toBeGreaterThan(0)
            ->and($shot['h'])->toBeGreaterThan(0);
    }

    // the city is on the sea, and the page says so rather than leaving it to the map
    expect(collect($copy['facts']['items'])->pluck('v')->implode(' '))->toContain('Baltic');
});

it('sends every route stop to a searchable map query', function (): void {
    foreach (trans('site.gdansk.route.stops') as $stop) {
        // the query is typed into Google Maps, so it stays plain ASCII
        expect($stop['query'])->toMatch('/^[A-Za-z0-9 ]+$/')
            ->and($stop['query'])->toContain('Gdansk');
    }
});

it('points the city page at its two neighbours', function (): void {
    $neighbours = trans('site.gdansk.neighbours.items');

    expect($neighbours)->toHaveCount(2);

    foreach ($neighbours as $neighbour) {
        expect($neighbour)->toHaveKeys(['photo', 'name', 'query', 'reach', 'lede', 'note', 'credit'])
            ->and($neighbour['credit'])->toContain('CC')
            ->and($neighbour['query'])->toMatch('/^[A-Za-z0-9 ]+$/');

        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/gdansk-{$neighbour['photo']}-{$size}.jpg"))->toBeReadableFile();
        }
    }

    // Gdynia exists because Gdansk was a Free City; the page says so rather than
    // listing it as another nice town on the coast
    $gdynia = collect($neighbours)->firstWhere('name', 'Gdynia');

    expect($gdynia['note'])->toContain('Free City')
        ->and($gdynia['note'])->toContain('1926');
});

it('writes the copy without semicolons in any language', function (): void {
    $found = [];

    foreach (glob(lang_path('*/site.php')) as $file) {
        $locale = basename(dirname($file));

        $walk = function (array $node, string $path) use (&$walk, $locale, &$found): void {
            foreach ($node as $key => $value) {
                $here = $path === '' ? (string) $key : "{$path}.{$key}";

                if (is_array($value)) {
                    $walk($value, $here);

                    continue;
                }

                if (str_contains((string) $value, ';')) {
                    $found[] = "{$locale}: {$here}";
                }
            }
        };

        $walk(require $file, '');
    }

    expect($found)->toBe([]);
});

it('lists the cities and links the one that is written', function (): void {
    $this->get('/places')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Places')
            ->has('translations.places.cities', 16)
        );

    $order = file_get_contents(resource_path('js/places.ts'));

    // every listed city has copy, and the order module and the copy agree
    foreach (array_keys(trans('site.places.cities')) as $key) {
        expect($order)->toContain("key: '{$key}'");
    }

    foreach (trans('site.places.cities') as $key => $city) {
        expect($city)->toHaveKeys(['name', 'region', 'note'])
            ->and($city['note'])->not->toBeEmpty();
    }

    // every city on the list now has a page, and the list links all of them
    foreach (['gdansk', 'warszawa', 'krakow', 'wroclaw', 'lodz', 'poznan', 'szczecin', 'bydgoszcz', 'lublin', 'katowice', 'zakopane', 'torun', 'malbork', 'wieliczka', 'bialowieza', 'mazury'] as $written) {
        expect($order)->toContain("href: '/places/{$written}'");
    }

    expect($order)->not->toMatch("/\{ key: '[a-z]+' \}/");
});

it('renders the Malbork page and says who the Teutonic Knights were', function (): void {
    $this->get('/places/malbork')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PlaceMalbork')
            ->has('translations.malbork.route.stops', 7)
            ->has('translations.malbork.gallery.shots', 10)
            ->has('translations.malbork.history.items', 9)
        );

    // the reader is told where the Order came from before the castle appears
    expect(trans('site.malbork.opening.body_1'))->toContain('1190')
        ->and(trans('site.malbork.opening.body_1'))->toContain('Konrad of Mazovia')
        // the Baltic Prussians are not the later German kingdom, and the page says so
        ->and(trans('site.malbork.opening.body_1'))->toContain('nothing to do with the later German kingdom')
        // the grand master moved his seat here, which is why the place is this size
        ->and(trans('site.malbork.opening.body_2'))->toContain('1309');

    // the battle is told once, on its own page, and linked rather than repeated
    expect(trans('site.malbork.reading.items.0.href'))->toBe('/history/grunwald-1410');

    // a place with nothing next door leaves the neighbours section out rather than faking it
    expect(trans('site.malbork.neighbours'))->toBe('site.malbork.neighbours');
});

it('renders the Wieliczka page and explains what salt was worth', function (): void {
    $this->get('/places/wieliczka')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PlaceWieliczka')
            ->has('translations.wieliczka.route.stops', 7)
            ->has('translations.wieliczka.gallery.shots', 10)
        );

    // why the mine is this grand: salt was a royal monopoly and a third of the revenue
    expect(trans('site.wieliczka.opening.body_1'))->toContain('Casimir the Great')
        ->and(trans('site.wieliczka.opening.body_1'))->toContain('third')
        // on the first UNESCO list of all, and worked until 1996
        ->and(trans('site.wieliczka.opening.body_2'))->toContain('1978')
        ->and(trans('site.wieliczka.opening.body_2'))->toContain('1996');

    // the reader is warned about the stairs, the temperature and the guide
    $warning = trans('site.wieliczka.myths.items.2.note');

    expect($warning)->toContain('380')
        ->and($warning)->toContain('guide')
        ->and($warning)->toContain('14 to 17 degrees');

    // the ring is a legend and is labelled as one, with the archaeology beside it
    expect(trans('site.wieliczka.myths.items.1.tag'))->toContain('legend');
});

it('renders the Bialowieza page as a forest rather than a town', function (): void {
    $this->get('/places/bialowieza')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PlaceBialowieza')
            ->has('translations.bialowieza.route.stops', 6)
            ->has('translations.bialowieza.gallery.shots', 10)
        );

    // the stops are trails and points, so none of them is a street corner
    $stops = collect(trans('site.bialowieza.route.stops'));

    expect($stops->pluck('name')->implode(' '))->toContain('reserve')
        ->and($stops->pluck('arrive')->implode(' '))->not->toContain('on foot');

    // why the forest survived, and the bison brought back from extinction in the wild
    expect(trans('site.bialowieza.opening.body_1'))->toContain('hunting')
        ->and(trans('site.bialowieza.opening.body_2'))->toContain('1927')
        ->and(trans('site.bialowieza.opening.body_2'))->toContain('1952')
        // the strict reserve rule is stated plainly, with the reason
        ->and(trans('site.bialowieza.opening.body_2'))->toContain('licensed guide')
        ->and(trans('site.bialowieza.myths.items.2.note'))->toContain('marked path');
});

it('renders the Masuria page as a region and dates its people to 1945', function (): void {
    $this->get('/places/mazury')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PlaceMazury')
            ->has('translations.mazury.route.stops', 8)
            ->has('translations.mazury.gallery.shots', 9)
        );

    // the pin is one point, so the caption has to say the subject is a region
    expect(trans('site.mazury.map.caption'))->toContain('region, not a point')
        ->and(trans('site.mazury.map.caption'))->toContain('Mikolajki');

    // the ice made the lakes, which is why they lie in chains
    expect(trans('site.mazury.opening.body_1'))->toContain('ice')
        ->and(trans('site.mazury.opening.body_1'))->toContain('Sniardwy');

    // 1945 is told factually, naming both the expelled Germans and the resettled Poles
    $after = trans('site.mazury.opening.body_2');

    expect($after)->toContain('East Prussia')
        ->and($after)->toContain('expelled')
        ->and($after)->toContain('Masurians')
        ->and($after)->toContain('Soviet Union');

    // the summer crowds are stated rather than sold around
    expect(trans('site.mazury.myths.items.2.note'))->toContain('July');
});

it('sends the home page to the places index', function (): void {
    expect(file_get_contents(resource_path('js/pages/Welcome.vue')))
        ->toContain("href: '/places'");
});

it('renders the Warsaw page and says why the old town is not old', function (): void {
    $this->get('/places/warszawa')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PlaceWarszawa')
            ->has('translations.warszawa.route.stops', 7)
            ->has('translations.warszawa.gallery.shots', 10)
        );

    // the point of the page: it is a reconstruction, and UNESCO listed it as one
    expect(trans('site.warszawa.opening.body_2'))->toContain('1980')
        ->and(trans('site.warszawa.opening.body_1'))->toContain('85')
        ->and(trans('site.warszawa.myths.items.1.note'))->toContain('Bellotto');

    // a city with nothing next door leaves the section out rather than faking it
    expect(trans('site.warszawa.neighbours'))->toBe('site.warszawa.neighbours');
});

it('renders the Krakow page and keeps the trumpet legend honest', function (): void {
    $this->get('/places/krakow')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('PlaceKrakow')
            ->has('translations.krakow.route.stops', 7)
            ->has('translations.krakow.neighbours.items', 2)
        );

    // the bugle call breaks off for real; the arrow was invented in 1925 and the page says so
    expect(trans('site.krakow.myths.items.1.note'))->toContain('1925')
        ->and(trans('site.krakow.myths.items.1.note'))->toContain('Kelly');

    // the city kept its buildings and lost its people, and the page does not let the first stand alone
    expect(trans('site.krakow.myths.items.0.note'))->toContain('65,000');

    // Auschwitz is offered as a memorial, never as a thing to do
    $auschwitz = collect(trans('site.krakow.neighbours.items'))->firstWhere('photo', 'auschwitz');

    expect($auschwitz['note'])->toContain('burial ground')
        ->and($auschwitz['note'])->toContain('not suitable for young children');
});

it('renders the government in exile article as a filmstrip', function (): void {
    $this->get('/history/government-in-exile')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryExile')
            ->has('translations.exile.frames', 9)
        );
});

it('credits every frame of the exile article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['exile']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/exile-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/exile-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/exile-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/exile-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['exile']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/exile-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.exile.sources.body'))->toContain('Title picture')
        ->and(trans('site.exile.sources.body'))->toContain('Narodowe Archiwum Cyfrowe')
        // the pictures under a share-alike licence say so, and the attribution one says that
        ->and(collect(trans('site.exile.frames'))->keyBy('key')['romania']['credit'])->toContain('CC BY-SA 2.0')
        ->and(collect(trans('site.exile.frames'))->keyBy('key')['katyn']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.exile.frames'))->keyBy('key')['visiting']['credit'])->toContain('CC BY 3.0');
});

it('uses pictures on the exile article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/exile-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'exile-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames plus the title picture, and no map
    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty()
        ->and(glob(public_path('images/exile-map-*.jpg')))->toBeEmpty();
});

it('hangs the exile article off the fifth topic of the war overview', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the pact, with no era band slot
    expect(strpos($order, '/history/government-in-exile'))
        ->toBeGreaterThan(strpos($order, '/history/molotov-ribbentrop-pact'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/government-in-exile',\s+titleKey: 'exile.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'invasion.4',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.exile.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.exile.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/government-in-exile')
        ->assertSee(e(trans('site.exile.meta.title')), escape: false)
        ->assertSee('/images/cards/exile.jpg', escape: false);
});

it('keeps every exile frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['exile']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the exile article from Romania to the museums', function (): void {
    $frames = collect(trans('site.exile.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['romania', 'london', 'work', 'majski', 'katyn', 'gibraltar', 'mikolajczyk', 'end', 'visiting'])
        // the reader is told how the office could move at all before the new president appears
        ->and($byKey['romania']['body'])->toContain('let a president at war name his own successor')
        // Paderewski is placed, because a pianist chairing a council needs explaining
        ->and($byKey['london']['body'])->toContain('prime minister of Poland in 1919')
        // the Polish word is glossed the first time it is used
        ->and($byKey['work']['body'])->toContain('the silent and unseen')
        // the alliance with Moscow has a reason, not just a date
        ->and($byKey['majski']['body'])->toContain('Germany attacked the Soviet Union on 22 June 1941')
        // the army of the previous frame is followed out before Katyn is reached
        ->and($byKey['katyn']['body'])->toContain('That army did not stay')
        // the Dakota of the third frame carries Arciszewski in the seventh
        ->and($byKey['mikolajczyk']['body'])->toContain('that Dakota')
        // the border decisions are handed to the article that tells them
        ->and($byKey['mikolajczyk']['body'])->toContain('has its own article here');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Ignacy Moscicki'))->toBe(array_search('romania', $keys, true))
        ->and($firstMention('Wladyslaw Raczkiewicz'))->toBe(array_search('romania', $keys, true))
        ->and($firstMention('Wladyslaw Sikorski'))->toBe(array_search('romania', $keys, true))
        ->and($firstMention('Ignacy Jan Paderewski'))->toBe(array_search('london', $keys, true))
        ->and($firstMention('Ivan Maisky'))->toBe(array_search('majski', $keys, true))
        ->and($firstMention('Wladyslaw Anders'))->toBe(array_search('majski', $keys, true))
        ->and($firstMention('Tadeusz Klimecki'))->toBe(array_search('gibraltar', $keys, true))
        ->and($firstMention('Stanislaw Mikolajczyk'))->toBe(array_search('mikolajczyk', $keys, true))
        ->and($firstMention('Tomasz Arciszewski'))->toBe(array_search('mikolajczyk', $keys, true))
        ->and($firstMention('Ryszard Kaczorowski'))->toBe(array_search('end', $keys, true));
});

it('keeps the hard-won exile facts the way the sources give them', function (): void {
    $frames = collect(trans('site.exile.frames'))->keyBy('key');
    $sources = trans('site.exile.sources.body');

    expect($frames['romania']['body'])->toContain('17 September 1939')
        ->and($frames['romania']['body'])->toContain('30 September 1939')
        ->and($frames['romania']['body'])->toContain('22 November')
        ->and($frames['romania']['body'])->toContain('Angers')
        // the constitution is the one of 1935 and the article is named in the sources
        ->and($sources)->toContain('article 24 of the constitution of 23 April 1935')
        // Paderewski chaired the council from its first session, which the sources qualify
        ->and($sources)->toContain('23 January 1940')
        ->and($frames['london']['body'])->toContain('10 May 1940')
        ->and($frames['london']['body'])->toContain('43 Buckingham Palace Road')
        // the couriers and the rocket, both dated and placed
        ->and($frames['work']['body'])->toContain('316')
        ->and($frames['work']['body'])->toContain('25 July 1944')
        ->and($frames['work']['body'])->toContain('Wal-Ruda')
        ->and($frames['work']['body'])->toContain('V-2')
        // no intelligence percentage is printed, and the sources say why
        ->and($sources)->toContain('No percentage is printed here')
        // the agreement, the amnesty and the command, each dated
        ->and($frames['majski']['body'])->toContain('30 July 1941')
        ->and($frames['majski']['body'])->toContain('amnesty')
        ->and($frames['majski']['body'])->toContain('10 August 1941')
        // the evacuation numbers are the attributed ones
        ->and($frames['katyn']['body'])->toContain('115,000')
        ->and($frames['katyn']['body'])->toContain('78,500')
        // Katyn: the announcement, the request, the break and the perpetrator
        ->and($frames['katyn']['body'])->toContain('13 April 1943')
        ->and($frames['katyn']['body'])->toContain('International Committee of the Red Cross')
        ->and($frames['katyn']['body'])->toContain('25 April 1943')
        ->and($frames['katyn']['body'])->toContain('Soviet security police')
        ->and($frames['katyn']['body'])->toContain('22,000')
        // Gibraltar: the inquiry, the exhumation, the closing, and the dispute named as a dispute
        ->and($frames['gibraltar']['body'])->toContain('4 July 1943')
        ->and($frames['gibraltar']['body'])->toContain('ruled out sabotage')
        ->and($frames['gibraltar']['body'])->toContain('argued about it ever since')
        ->and($frames['gibraltar']['body'])->toContain('2008')
        ->and($frames['gibraltar']['body'])->toContain('30 December 2013')
        ->and($sources)->toContain('are not repeated here')
        // the end: recognition, the return, and the handover
        ->and($frames['end']['body'])->toContain('28 June 1945')
        ->and($frames['end']['body'])->toContain('5 July')
        ->and($frames['end']['body'])->toContain('1947')
        ->and($frames['end']['body'])->toContain('22 December 1990')
        ->and($frames['end']['body'])->toContain('Lech Walesa')
        // where a reader can go
        ->and($frames['visiting']['body'])->toContain('20 Princes Gate')
        ->and($frames['visiting']['body'])->toContain('Saint Leonard')
        ->and($frames['visiting']['body'])->toContain('1993')
        ->and($frames['visiting']['body'])->toContain('Warsaw Citadel')
        ->and($frames['visiting']['body'])->toContain('Royal Castle');

    app()->setLocale('pl');

    $polish = collect(trans('site.exile.frames'))->keyBy('key');

    expect($polish['romania']['body'])->toContain('Ignacy Mościcki')
        ->and($polish['romania']['body'])->toContain('30 września 1939')
        ->and($polish['london']['body'])->toContain('Ignacy Jan Paderewski')
        ->and($polish['work']['body'])->toContain('cichociemnych')
        ->and($polish['majski']['body'])->toContain('Iwan Majski')
        ->and($polish['majski']['body'])->toContain('amnestia')
        ->and($polish['katyn']['body'])->toContain('Międzynarodowy Komitet Czerwonego Krzyża')
        ->and($polish['katyn']['body'])->toContain('sowiecka policja polityczna')
        ->and($polish['gibraltar']['body'])->toContain('umorzył śledztwo')
        ->and($polish['mikolajczyk']['body'])->toContain('Tomasz Arciszewski')
        ->and($polish['end']['body'])->toContain('Tymczasowy Rząd Jedności Narodowej')
        ->and($polish['end']['body'])->toContain('22 grudnia 1990')
        ->and($polish['visiting']['body'])->toContain('Zamku Królewskim');

    app()->setLocale('en');
});

it('keeps the bridges the exile fact gate had to add', function (): void {
    $frames = collect(trans('site.exile.frames'))->keyBy('key');

    // the reader is not dropped into a war already running
    expect($frames['romania']['body'])->toStartWith('Nazi Germany invaded Poland on 1 September 1939')
        // Sikorski arrives with a role, not just a rank
        ->and($frames['romania']['body'])->toContain('the pre-war rulers had pushed aside')
        // the institution the government funded is named, not left as "an underground administration"
        ->and($frames['work']['body'])->toContain('Polish Underground State')
        // Katyn sat in German-held ground in 1943, which is why Berlin could dig there
        ->and($frames['katyn']['body'])->toContain('in ground the German army then held')
        // the three men at Tehran and Yalta are placed by the countries they spoke for
        ->and($frames['mikolajczyk']['body'])->toContain('the British, American and Soviet leaders')
        // the Warsaw government was settled elsewhere, and the page says where
        ->and($frames['end']['body'])->toContain('settled in talks in Moscow');

    // what was consciously left out is declared rather than silently dropped
    expect(trans('site.exile.sources.body'))->toContain('Warsaw Uprising')
        ->and(trans('site.exile.sources.body'))->toContain('sixteen leaders of the Underground State');

    app()->setLocale('pl');

    $polish = collect(trans('site.exile.frames'))->keyBy('key');

    expect($polish['romania']['body'])->toStartWith('Nazistowskie Niemcy napadły na Polskę 1 września 1939')
        ->and($polish['work']['body'])->toContain('Polskie Państwo Podziemne')
        ->and($polish['katyn']['body'])->toContain('na terenie zajętym wtedy przez Niemców')
        ->and($polish['end']['body'])->toContain('uzgodniony wcześniej na rozmowach w Moskwie')
        // the Polish title said "zmieniła ręce", which is an English idiom in Polish clothes
        ->and($polish['romania']['title'])->toContain('przeszła w inne ręce');

    app()->setLocale('en');
});

it('renders the General Government article as a filmstrip', function (): void {
    $this->get('/history/general-government')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryGeneralGovernment')
            ->has('translations.gg.frames', 9)
        );
});

it('credits every frame of the General Government article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['gg']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/gg-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/gg-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the large size really is the large one, not a thumbnail that slipped through
            expect(getimagesize(public_path("images/gg-{$frame['photo']}-lg.jpg"))[0])->toBe(1280)
                ->and(getimagesize(public_path("images/gg-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['gg']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/gg-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.gg.sources.body'))->toContain('Title picture')
        ->and(trans('site.gg.sources.body'))->toContain('Paul Colin Hennig')
        // the pictures under a share-alike licence say so, and the attribution one says that
        ->and(collect(trans('site.gg.frames'))->keyBy('key')['culture']['credit'])->toContain('CC BY 4.0')
        ->and(collect(trans('site.gg.frames'))->keyBy('key')['krakow']['credit'])->toContain('CC BY-SA 3.0 PL')
        ->and(collect(trans('site.gg.frames'))->keyBy('key')['carved']['credit'])->toContain('public domain');
});

it('uses pictures on the General Government article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/gg-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'gg-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the General Government article off the first topic of the occupation chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the government in exile, with no era band slot
    expect(strpos($order, '/history/general-government'))
        ->toBeGreaterThan(strpos($order, '/history/government-in-exile'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/general-government',\s+titleKey: 'gg.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'occupation.0',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.gg.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.gg.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/general-government')
        ->assertSee(e(trans('site.gg.meta.title')), escape: false)
        ->assertSee('/images/cards/gg.jpg', escape: false);
});

it('keeps every General Government frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['gg']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the General Government article from the carve-up to the museums', function (): void {
    $frames = collect(trans('site.gg.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['carved', 'colony', 'rule', 'shortage', 'culture', 'krakow', 'districts', 'end', 'today'])
        // the territory is explained before anyone is put in charge of it
        ->and($byKey['carved']['body'])->toContain('divide Poland between them')
        // Frank arrives with a role and an address, and somebody puts him there
        ->and($byKey['colony']['body'])->toContain('Hitler put a Nazi Party lawyer')
        ->and($byKey['colony']['body'])->toContain('in charge as Governor General')
        // the Polish apparatus of the third frame is what the first proclamation left standing
        ->and($byKey['rule']['body'])->toContain('stay at his post')
        // the Polish words are glossed where they are first used
        ->and($byKey['rule']['body'])->toContain('the navy blue police')
        ->and($byKey['shortage']['body'])->toContain('A lapanka was a round-up')
        ->and($byKey['shortage']['body'])->toContain('the building service')
        // the Wawel of the second frame is explained when Krakow gets its own frame
        ->and($byKey['krakow']['body'])->toContain('where Polish kings are buried')
        // the museum is described for what it actually holds
        ->and($byKey['today']['body'])->toContain('the German owner Oskar Schindler')
        ->and($byKey['today']['body'])->toContain('rather than about him');

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Adolf Hitler'))->toBe(array_search('carved', $keys, true))
        ->and($firstMention('Hans Frank'))->toBe(array_search('colony', $keys, true))
        ->and($firstMention('Zygmunt Nowicki'))->toBe(array_search('culture', $keys, true))
        ->and($firstMention('Kajetan Muehlmann'))->toBe(array_search('culture', $keys, true))
        ->and($firstMention('Otto von Waechter'))->toBe(array_search('krakow', $keys, true))
        ->and($firstMention('Tadeusz Pankiewicz'))->toBe(array_search('today', $keys, true));
});

it('keeps the hard-won General Government facts the way the sources give them', function (): void {
    $frames = collect(trans('site.gg.frames'))->keyBy('key');
    $sources = trans('site.gg.sources.body');

    // the decree, its effective date and the four annexed units, each named
    expect($frames['carved']['body'])->toContain('12 October 1939')
        ->and($frames['carved']['body'])->toContain('26 October')
        ->and($frames['carved']['body'])->toContain('Reichsgau Wartheland')
        ->and($frames['carved']['body'])->toContain('Danzig-West Prussia')
        ->and($frames['carved']['body'])->toContain('Ciechanow')
        ->and($frames['carved']['body'])->toContain('east of the river Bug')
        // the purpose, in Frank own dated words, and the legal trick of 1940
        ->and($frames['colony']['body'])->toContain('3 October 1939')
        ->and($frames['colony']['body'])->toContain('31 July 1940')
        ->and($frames['colony']['body'])->toContain('860,000')
        // the two ordinances that made terror the ordinary law
        ->and($frames['rule']['body'])->toContain('31 October 1939')
        ->and($frames['rule']['body'])->toContain('2 October 1943')
        // the ration card is dated and no calorie table is invented
        ->and($frames['shortage']['body'])->toContain('2 December 1939')
        ->and($frames['shortage']['body'])->toContain('Karczew')
        ->and($frames['shortage']['body'])->toContain('Jablonna')
        ->and($sources)->toContain('No calorie table is printed')
        // clandestine teaching, named and counted
        ->and($frames['culture']['body'])->toContain('Tajna Organizacja Nauczycielska')
        ->and($frames['culture']['body'])->toContain('eight and a half thousand')
        ->and($frames['culture']['body'])->toContain('Veit Stoss')
        // the Krakow ghetto decree with its date, deadline and size
        ->and($frames['krakow']['body'])->toContain('3 March 1941')
        ->and($frames['krakow']['body'])->toContain('20 March')
        ->and($frames['krakow']['body'])->toContain('twenty hectares')
        ->and($frames['krakow']['body'])->toContain('three hundred and twenty buildings')
        // four districts, then five, and the tram detail that dates itself
        ->and($frames['districts']['body'])->toContain('Radom')
        ->and($frames['districts']['body'])->toContain('spring of 1942')
        ->and($frames['districts']['body'])->toContain('August 1941')
        ->and($frames['districts']['body'])->toContain('Lwow')
        // the end, dated where the sources agree and hedged where they do not
        ->and($frames['end']['body'])->toContain('12 January 1945')
        ->and($frames['end']['body'])->toContain('within the week')
        ->and($frames['end']['body'])->toContain('thirty eight volumes')
        ->and($frames['end']['body'])->toContain('16 October 1946')
        // the addresses a reader can walk to
        ->and($frames['today']['body'])->toContain('18 Plac Bohaterow Getta')
        ->and($frames['today']['body'])->toContain('4 Lipowa Street')
        ->and($frames['today']['body'])->toContain('Palmiry in 1948');

    // the Nuremberg judgment is named as the source of the quoted diary line
    expect($sources)->toContain('Avalon Project')
        // what could not be confirmed is declared rather than guessed
        ->and($sources)->toContain('No wartime German street sign')
        ->and($sources)->toContain('No count of underground newspaper titles')
        ->and($sources)->toContain('two different places')
        // the umbrella article keeps its own subjects
        ->and($sources)->toContain('occupation and the Holocaust');

    app()->setLocale('pl');

    $polish = collect(trans('site.gg.frames'))->keyBy('key');

    expect($polish['carved']['body'])->toContain('12 października 1939')
        ->and($polish['carved']['body'])->toContain('Krajem Warty')
        ->and($polish['colony']['body'])->toContain('860 tysięcy')
        ->and($polish['rule']['body'])->toContain('granatową')
        ->and($polish['shortage']['body'])->toContain('Jabłonny')
        ->and($polish['culture']['body'])->toContain('Tajną Organizacją Nauczycielską')
        ->and($polish['krakow']['body'])->toContain('3 marca 1941')
        ->and($polish['districts']['body'])->toContain('Lwowem')
        ->and($polish['end']['body'])->toContain('12 stycznia 1945')
        ->and($polish['end']['body'])->toContain('16 października 1946')
        ->and($polish['today']['body'])->toContain('Lipowej 4')
        ->and($polish['today']['body'])->toContain('Palmirach');

    app()->setLocale('en');
});

it('publishes a real period map on the General Government article', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/gg-map-{$size}.jpg"))->toBeReadableFile();
    }

    // a published map, captioned with its maker, its date, its scale and its language
    expect(trans('site.gg.map.caption'))->toContain('1940')
        ->and(trans('site.gg.map.caption'))->toContain('1:7,500,000')
        ->and(trans('site.gg.map.caption'))->toContain('Russian')
        ->and(trans('site.gg.map.credit'))->toContain('Geodesy and Cartography')
        ->and(trans('site.gg.map.alt'))->not->toBeEmpty()
        ->and(trans('site.gg.map.caption', [], 'pl'))->toContain('1940')
        ->and(trans('site.gg.map.caption', [], 'pl'))->toContain('1:7 500 000')
        ->and(trans('site.gg.map.credit', [], 'pl'))->toContain('Geodezji i Kartografii')
        ->and(trans('site.gg.map.alt', [], 'pl'))->not->toBeEmpty();

    // no hand-drawn schematic stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryGeneralGovernment.vue')))
        ->not->toContain('Map.vue')
        ->toContain('gg-map-lg.jpg')
        ->toContain('with-caption');
});

it('renders the Katyn article as a filmstrip', function (): void {
    $this->get('/history/katyn')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryKatyn')
            ->has('translations.katyn.frames', 9)
        );
});

it('credits every frame of the Katyn article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['katyn']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/katyn-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/katyn-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/katyn-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['katyn']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file, except the one scan that exists
    // nowhere wider: the first page of the note Beria sent to Stalin
    foreach (collect(trans('site.katyn.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/katyn-{$photo}-lg.jpg"))[0])
            ->toBe($photo === 'decision' ? 992 : 1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/katyn-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.katyn.sources.body'))->toContain('Title picture')
        ->and(trans('site.katyn.sources.body'))->toContain('Natalia Shkurenok')
        // the licences are carried over, share-alike, public dedication and public domain alike
        ->and(collect(trans('site.katyn.frames'))->keyBy('key')['settlements']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.katyn.frames'))->keyBy('key')['waves']['credit'])->toContain('CC0')
        ->and(collect(trans('site.katyn.frames'))->keyBy('key')['decision']['credit'])->toContain('public domain');
});

it('uses pictures on the Katyn article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/katyn-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'katyn-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames and the title picture, and no map
    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Katyn article off the third topic of the occupation chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the General Government, with no era band slot
    expect(strpos($order, '/history/katyn'))
        ->toBeGreaterThan(strpos($order, '/history/general-government'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/katyn',\s+titleKey: 'katyn.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'occupation.2',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.katyn.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.katyn.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/katyn')
        ->assertSee(e(trans('site.katyn.meta.title')), escape: false)
        ->assertSee('/images/cards/katyn.jpg', escape: false);
});

it('keeps every Katyn frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['katyn']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Katyn article from the Soviet zone to the oaks', function (): void {
    $frames = collect(trans('site.katyn.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['zone', 'camps', 'decision', 'waves', 'settlements', 'discovery', 'lie', 'truth', 'today'])
        // the prisoners exist before anybody decides what to do with them
        ->and($byKey['camps']['body'])->toContain('handed about 126,000 of them to the NKVD')
        // every institution is glossed where it first appears
        ->and($byKey['camps']['body'])->toContain('the Soviet secret police')
        ->and($byKey['decision']['body'])->toContain('the Soviet party leadership')
        // the deportations are tied to the shootings rather than dropped in
        ->and($byKey['waves']['body'])->toContain('Beside the shootings ran the deportations')
        // the Russian saying is translated in the same breath
        ->and($byKey['settlements']['body'])->toContain('who does not work does not eat')
        // the break with Moscow is handed on to the article that tells it
        ->and($byKey['discovery']['body'])->toContain('government in exile')
        // the forbidden date is explained, not just asserted
        ->and($byKey['lie']['body'])->toContain('because 1940 said who had done it')
        // the woman named in the camps frame is the one the museum object belongs to
        ->and($byKey['today']['body'])->toContain('Janina Lewandowska')
        // nobody is left in Siberia with no way out of the page
        ->and($byKey['settlements']['body'])->toContain('an amnesty then let many of them out')
        // how Poland got the government that told the lie
        ->and($byKey['lie']['body'])->toContain('a government Moscow installed')
        // the question a reader asks at the end of it
        ->and($byKey['truth']['body'])->toContain('Nobody has ever been tried');

    // the two invaders are named in the opening, with the pact that put them there
    expect(trans('site.katyn.hero.lede'))->toContain('Germany and the Soviet Union')
        ->and(trans('site.katyn.hero.lede'))->toContain('under a pact they had signed');

    app()->setLocale('pl');

    $polish = collect(trans('site.katyn.frames'))->keyBy('key');

    expect(trans('site.katyn.hero.lede'))->toContain('Niemcy i Związek Sowiecki')
        ->and($polish['settlements']['body'])->toContain('ogłoszono amnestię')
        ->and($polish['lie']['body'])->toContain('narzuconym przez Moskwę')
        ->and($polish['truth']['body'])->toContain('Nikogo nigdy nie osądzono');

    app()->setLocale('en');

    expect(true)->toBeTrue();

    // everyone is on stage, with a role, where they first appear
    expect($firstMention('Janina Lewandowska'))->toBe(array_search('camps', $keys, true))
        ->and($firstMention('Lavrentiy Beria'))->toBe(array_search('decision', $keys, true))
        ->and($firstMention('Helena Lapinska'))->toBe(array_search('settlements', $keys, true))
        ->and($firstMention('Nikolai Burdenko'))->toBe(array_search('discovery', $keys, true))
        ->and($firstMention('Andrzej Panufnik'))->toBe(array_search('lie', $keys, true))
        ->and($firstMention('Aleksandr Shelepin'))->toBe(array_search('truth', $keys, true));
});

it('keeps the hard-won Katyn facts the way the sources give them', function (): void {
    $frames = collect(trans('site.katyn.frames'))->keyBy('key');
    $sources = trans('site.katyn.sources.body');

    // the staged vote, the annexation and the arrests, each dated
    expect($frames['zone']['body'])->toContain('17 September 1939')
        ->and($frames['zone']['body'])->toContain('22 October 1939')
        ->and($frames['zone']['body'])->toContain('1 and 2 November')
        ->and($frames['zone']['body'])->toContain('Presidium of the Supreme Soviet')
        ->and($frames['zone']['body'])->toContain('150,000')
        // the camps, counted on the days the institute counted them
        ->and($frames['camps']['body'])->toContain('4,727')
        ->and($frames['camps']['body'])->toContain('3,907')
        ->and($frames['camps']['body'])->toContain('5,963')
        ->and($frames['camps']['body'])->toContain('Lake Seliger')
        ->and($frames['camps']['body'])->toContain('the letters stopped')
        // the note, its two numbers, the Politburo date and who signed what
        ->and($frames['decision']['body'])->toContain('14,700')
        ->and($frames['decision']['body'])->toContain('11,000')
        ->and($frames['decision']['body'])->toContain('5 March')
        ->and($frames['decision']['body'])->toContain('Kliment Voroshilov')
        ->and($frames['decision']['body'])->toContain('Anastas Mikoyan')
        ->and($frames['decision']['body'])->toContain('noted as in favour')
        // the three killing places, each with the camp it emptied
        ->and($frames['decision']['body'])->toContain('Katyn forest near Smolensk')
        ->and($frames['decision']['body'])->toContain('NKVD cellars in Kalinin')
        ->and($frames['decision']['body'])->toContain('cellars in Kharkiv')
        ->and($frames['decision']['body'])->toContain('About 22,000')
        // four waves, four dates, four sizes
        ->and($frames['waves']['body'])->toContain('10 February 1940')
        ->and($frames['waves']['body'])->toContain('13 April 1940')
        ->and($frames['waves']['body'])->toContain('29 June 1940')
        ->and($frames['waves']['body'])->toContain('May and June 1941')
        ->and($frames['waves']['body'])->toContain('140,000')
        ->and($frames['waves']['body'])->toContain('61,000')
        // 1943, and the break dated the way the institute dates it
        ->and($frames['discovery']['body'])->toContain('13 April 1943')
        ->and($frames['discovery']['body'])->toContain('25 April 1943')
        ->and($frames['discovery']['body'])->toContain('January 1944')
        ->and($frames['discovery']['body'])->toContain('the judgment never mentioned it')
        // the censorship instruction and the monument that lasted one night
        ->and($frames['lie']['body'])->toContain('1975')
        ->and($frames['lie']['body'])->toContain('31 July 1981')
        ->and($frames['lie']['body'])->toContain('1967')
        // the admissions, the exact count and the ruling, each attributed
        ->and($frames['truth']['body'])->toContain('13 April 1990')
        ->and($frames['truth']['body'])->toContain('Vsevolod Merkulov')
        ->and($frames['truth']['body'])->toContain('14 October 1992')
        ->and($frames['truth']['body'])->toContain('21,857')
        ->and($frames['truth']['body'])->toContain('September 2004')
        ->and($frames['truth']['body'])->toContain('2013')
        // today, with the dates the cemeteries actually opened
        ->and($frames['today']['body'])->toContain('Warsaw Citadel')
        ->and($frames['today']['body'])->toContain('2000')
        ->and($frames['today']['body'])->toContain('2012')
        ->and($frames['today']['body'])->toContain('10 April 2010')
        ->and($frames['today']['body'])->toContain('96');

    // the two counts of the deportations are both attributed and neither is adopted
    expect($sources)->toContain('320,000 to 340,000')
        ->and($sources)->toContain('about a million')
        ->and($sources)->toContain('Memorial')
        ->and($sources)->toContain('chooses between them by printing neither')
        // the perpetrating institution is named, and the note is dated as the archive dates it
        ->and($sources)->toContain('dated by the institute only to March 1940')
        // the Strasbourg holding is quoted by article, not summarised into a side
        ->and($sources)->toContain('Janowiec and Others v. Russia')
        ->and($sources)->toContain('no competence to examine the complaint under article 2')
        ->and($sources)->toContain('no violation of article 3')
        ->and($sources)->toContain('failed to comply with article 38')
        ->and($sources)->toContain('present-day Polish and Russian politics')
        // the restraint about pictures is stated, not merely practised
        ->and($sources)->toContain('No photograph of a body or of an exhumation')
        ->and($sources)->toContain('propaganda campaign')
        // what could not be confirmed is declared rather than guessed
        ->and($sources)->toContain('Scurvy is not mentioned')
        ->and($sources)->toContain('No day is printed for the last letter')
        ->and($sources)->toContain('Black Book of Censorship is not cited')
        ->and($sources)->toContain('Nothing about its causes')
        // the neighbouring articles keep their own subjects
        ->and($sources)->toContain('government in exile');

    app()->setLocale('pl');

    $polish = collect(trans('site.katyn.frames'))->keyBy('key');

    expect($polish['zone']['body'])->toContain('17 września 1939')
        ->and($polish['zone']['body'])->toContain('22 października 1939')
        ->and($polish['camps']['body'])->toContain('4727')
        ->and($polish['camps']['body'])->toContain('Janina Lewandowska')
        ->and($polish['decision']['body'])->toContain('14 700')
        ->and($polish['decision']['body'])->toContain('5 marca')
        ->and($polish['decision']['body'])->toContain('Woroszyłow')
        ->and($polish['decision']['body'])->toContain('Mikojan')
        ->and($polish['waves']['body'])->toContain('10 lutego 1940')
        ->and($polish['waves']['body'])->toContain('13 kwietnia 1940')
        ->and($polish['settlements']['body'])->toContain('kto nie pracuje, ten nie je')
        ->and($polish['discovery']['body'])->toContain('25 kwietnia 1943')
        ->and($polish['discovery']['body'])->toContain('Burdenki')
        ->and($polish['lie']['body'])->toContain('31 lipca 1981')
        ->and($polish['truth']['body'])->toContain('21 857')
        ->and($polish['truth']['body'])->toContain('Szelepin')
        ->and($polish['today']['body'])->toContain('10 kwietnia 2010')
        ->and(trans('site.katyn.sources.body'))->toContain('Janowiec i inni przeciwko Rosji');

    app()->setLocale('en');
});

it('publishes no map on the Katyn article and says so', function (): void {
    // nothing hand-drawn stands in for a map that was not found
    expect(file_get_contents(resource_path('js/pages/HistoryKatyn.vue')))
        ->not->toContain('Map.vue')
        ->not->toContain('katyn-map');

    expect(glob(public_path('images/katyn-map-*.jpg')))->toBeEmpty();

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.katyn.sources.body'))->toContain(
            $locale === 'en' ? 'No map is published on this page' : 'Na tej stronie nie ma mapy'
        );
    }

    app()->setLocale('en');
});

it('renders the Zamosc expulsions article as a filmstrip', function (): void {
    $this->get('/history/zamosc-expulsions')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryZamosc')
            ->has('translations.zamosc.frames', 9)
        );
});

it('credits every frame of the Zamosc article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['zamosc']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/zamosc-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/zamosc-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/zamosc-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['zamosc']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file, except the one period photograph
    // that exists nowhere wider: the Bessarabian settlers waiting at Galatz
    foreach (collect(trans('site.zamosc.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/zamosc-{$photo}-lg.jpg"))[0])
            ->toBe($photo === 'settlers' ? 1100 : 1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/zamosc-hero-{$size}.jpg"))->toBeReadableFile();
        expect(public_path("images/zamosc-map-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.zamosc.sources.body'))->toContain('Title picture')
        ->and(trans('site.zamosc.sources.body'))->toContain('Rotunda')
        // the licences are carried over, attribution, share-alike and public domain alike
        ->and(collect(trans('site.zamosc.frames'))->keyBy('key')['plan']['credit'])->toContain('CC BY 3.0')
        ->and(collect(trans('site.zamosc.frames'))->keyBy('key')['night']['credit'])->toContain('public domain')
        ->and(collect(trans('site.zamosc.frames'))->keyBy('key')['sochy']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.zamosc.frames'))->keyBy('key')['after']['credit'])->toContain('CC BY-SA 3.0 DE');
});

it('uses pictures on the Zamosc article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/zamosc-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'zamosc-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Zamosc article off the fourth topic of the occupation chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after Katyn, with no era band slot
    expect(strpos($order, '/history/zamosc-expulsions'))
        ->toBeGreaterThan(strpos($order, '/history/katyn'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/zamosc-expulsions',\s+titleKey: 'zamosc.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'occupation.3',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.zamosc.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.zamosc.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/zamosc-expulsions')
        ->assertSee(e(trans('site.zamosc.meta.title')), escape: false)
        ->assertSee('/images/cards/zamosc.jpg', escape: false);
});

it('keeps every Zamosc frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['zamosc']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Zamosc article from the plan to the square', function (): void {
    $frames = collect(trans('site.zamosc.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');
    $firstMention = fn (string $name): int => $frames->search(fn (array $frame): bool => str_contains($frame['body'], $name));

    expect($keys)->toBe(['plan', 'night', 'children', 'rescue', 'settlers', 'rising', 'sochy', 'after', 'today'])
        // the man who runs the action is on stage before he gives orders
        ->and($byKey['plan']['body'])->toContain('his police commander in Lublin, Odilo Globocnik')
        ->and($firstMention('Globocnik'))->toBe(array_search('plan', $keys, true))
        // every institution is glossed where it first appears
        ->and($byKey['children']['body'])->toContain('the Polish state body that researches these years')
        ->and($byKey['rising']['body'])->toContain('the Polish government that had gone into exile in London')
        ->and($byKey['rising']['body'])->toContain('the underground peasant movement')
        // the trains follow from the sorting rather than arriving out of nowhere
        ->and($byKey['rescue']['body'])->toContain('The people the Germans had no use for')
        // the reprisal frame is tied to the battle in the frame before it
        ->and($byKey['sochy']['body'])->toContain('The day after Zaboreczno')
        // the reader is told why the town looks untouched when the villages do not
        ->and($byKey['today']['body'])->toContain('came through the war intact');

    // the region is named in Polish once, in the opening, and explained there
    expect(trans('site.zamosc.hero.lede'))->toContain('which Poles call Zamojszczyzna')
        ->and(trans('site.zamosc.hero.lede'))->toContain('settled with Germans');

    app()->setLocale('pl');

    $polish = collect(trans('site.zamosc.frames'))->keyBy('key');

    expect(trans('site.zamosc.hero.lede'))->toContain('czyli Zamojszczyzna')
        ->and($polish['plan']['body'])->toContain('Odilowi Globocnikowi')
        ->and($polish['rising']['body'])->toContain('Bataliony Chłopskie')
        ->and($polish['sochy']['body'])->toContain('Dzień po Zaborecznem');

    app()->setLocale('en');
});

it('keeps the hard-won Zamosc facts the way the sources give them', function (): void {
    $frames = collect(trans('site.zamosc.frames'))->keyBy('key');
    $sources = trans('site.zamosc.sources.body');

    // the plan, its author, its month and the order that picked the region
    expect($frames['plan']['body'])->toContain('June 1942')
        ->and($frames['plan']['body'])->toContain('Konrad Meyer')
        ->and($frames['plan']['body'])->toContain('12 November 1942')
        ->and($frames['plan']['body'])->toContain('Heinrich Himmler')
        // the renaming is hedged rather than asserted
        ->and($frames['plan']['body'])->toContain('Himmlerstadt')
        ->and($frames['plan']['body'])->toContain('never formally adopted')
        // the first night, the first village and the camp with its documented capacity
        ->and($frames['night']['body'])->toContain('27 to 28 November 1942')
        ->and($frames['night']['body'])->toContain('Skierbieszow')
        ->and($frames['night']['body'])->toContain('10,500')
        ->and($frames['night']['body'])->toContain('four groups')
        ->and($frames['night']['body'])->toContain('six months')
        // the counties, the totals and the children, each attributed
        ->and($frames['children']['body'])->toContain('Bilgoraj')
        ->and($frames['children']['body'])->toContain('August 1943')
        ->and($frames['children']['body'])->toContain('110,000')
        ->and($frames['children']['body'])->toContain('300 villages')
        ->and($frames['children']['body'])->toContain('30,000 children')
        ->and($frames['children']['body'])->toContain('4,500')
        ->and($frames['children']['body'])->toContain('44 girls and five boys')
        // the transports, counted and attributed to the historian who counted them
        ->and($frames['rescue']['body'])->toContain('5,321')
        ->and($frames['rescue']['body'])->toContain('Beata Kozaczynska')
        ->and($frames['rescue']['body'])->toContain('465 households')
        ->and($frames['rescue']['body'])->toContain('fifty zloty')
        // the colonists, their range and where they came from
        ->and($frames['settlers']['body'])->toContain('ten and thirteen thousand')
        ->and($frames['settlers']['body'])->toContain('Bessarabia and Bukovina')
        ->and($frames['settlers']['body'])->toContain('January 1943')
        // the two battles, dated, with the commanders the institute names
        ->and($frames['rising']['body'])->toContain('23 December 1942')
        ->and($frames['rising']['body'])->toContain('Stefan Rowecki')
        ->and($frames['rising']['body'])->toContain('Wojda on 30 December')
        ->and($frames['rising']['body'])->toContain('Jerzy Miller')
        ->and($frames['rising']['body'])->toContain('Zaboreczno on 1 February 1943')
        ->and($frames['rising']['body'])->toContain('Franciszek Bartlomowicz')
        // Sochy, dated, attributed, with the range of counts rather than one round number
        ->and($frames['sochy']['body'])->toContain('1 June 1943')
        ->and($frames['sochy']['body'])->toContain('183, or 185, or 194')
        ->and($frames['sochy']['body'])->toContain('180 were identified')
        // the perpetrators are named, never left vague
        ->and($frames['sochy']['body'])->toContain('German police and an SS unit')
        // the postwar search, with the office, the years and the card index
        ->and($frames['after']['body'])->toContain('Roman Hrabar')
        ->and($frames['after']['body'])->toContain('1947 to 1950')
        ->and($frames['after']['body'])->toContain('Polish Red Cross')
        ->and($frames['after']['body'])->toContain('5,490')
        ->and($frames['after']['body'])->toContain('about 800')
        // today, with the inscription and the institutions that keep the places
        ->and($frames['today']['body'])->toContain('Jan Zamoyski')
        ->and($frames['today']['body'])->toContain('Bernardo Morando')
        ->and($frames['today']['body'])->toContain('UNESCO')
        ->and($frames['today']['body'])->toContain('Zamosc Museum')
        ->and($frames['today']['body'])->toContain('1 February')
        ->and($frames['today']['body'])->toContain('State Museum at Majdanek');

    // the camps are attributed to the state that built them, never to the country they stood in
    expect($frames['night']['body'])->toContain('Auschwitz')
        ->and($sources)->not->toContain('Polish camp')
        ->and($sources)->not->toContain('Polish death camp');

    // what the sources disagree about is declared rather than smoothed over
    expect($sources)->toContain('three different days for that visit')
        ->and($sources)->toContain('a Himmlerstadt that never was')
        ->and($sources)->toContain('41,000 to about 50,000')
        ->and($sources)->toContain('13,000 dead children')
        ->and($sources)->toContain('two different streets')
        ->and($sources)->toContain('both captain and major')
        ->and($sources)->toContain('103 coffins')
        // the myth is named as a myth, and what is documented is kept
        ->and($sources)->toContain('postwar myth')
        ->and($sources)->toContain('Home Army freed the trains')
        // the restraint about pictures is stated, not merely practised
        ->and($sources)->toContain('No photograph of a dead or dying person')
        ->and($sources)->toContain('Czeslawa Kwoka')
        // what could not be established is declared rather than guessed
        ->and($sources)->toContain('black earth is not used here')
        ->and($sources)->toContain('Transnistria is not mentioned')
        ->and($sources)->toContain('No figure for villages burned')
        ->and($sources)->toContain('No opening hours are printed')
        // the reader is not sent to a museum that does not exist
        ->and($sources)->toContain('no Museum of the Children of Zamojszczyzna')
        // the neighbouring articles keep their own subjects
        ->and($sources)->toContain('General Government');

    app()->setLocale('pl');

    $polish = collect(trans('site.zamosc.frames'))->keyBy('key');

    expect($polish['plan']['body'])->toContain('12 listopada 1942')
        ->and($polish['plan']['body'])->toContain('Konrada Meyera')
        ->and($polish['night']['body'])->toContain('27 na 28 listopada 1942')
        ->and($polish['night']['body'])->toContain('10 500')
        ->and($polish['children']['body'])->toContain('110 tysięcy')
        ->and($polish['children']['body'])->toContain('44 dziewczynki')
        ->and($polish['rescue']['body'])->toContain('5321')
        ->and($polish['rescue']['body'])->toContain('Beata Kozaczyńska')
        ->and($polish['rising']['body'])->toContain('1 lutego 1943')
        ->and($polish['sochy']['body'])->toContain('1 czerwca 1943')
        ->and($polish['sochy']['body'])->toContain('183, 185 albo 194')
        ->and($polish['after']['body'])->toContain('5490')
        ->and(trans('site.zamosc.sources.body'))->toContain('powojennym mitem')
        ->and(trans('site.zamosc.sources.body'))->toContain('Muzeum Dzieci Zamojszczyzny');

    app()->setLocale('en');
});

it('keeps the Zamosc story joined up where the verification pass found it broken', function (): void {
    $frames = collect(trans('site.zamosc.frames'))->keyBy('key');
    $sources = trans('site.zamosc.sources.body');

    // the reader is told Germany was already occupying the country before the plan arrives
    expect(trans('site.zamosc.hero.lede'))->toContain('since 1939')
        ->and(trans('site.zamosc.hero.lede'))->toContain('General Government')
        // the SS is explained the first time it is named, rather than assumed
        ->and($frames['plan']['body'])->toContain('the armed and police corps of the Nazi party')
        // the camp is attributed to the state that built it, never to the country it stood in
        ->and($frames['night']['body'])->toContain('the German camp at Auschwitz')
        // the 44 girls and five boys survived out of the children, not out of the 1,300 deportees
        ->and($frames['children']['body'])->toContain('at least 150 of them children')
        ->and($frames['children']['body'])->toContain('of those children 44 girls and five boys')
        ->and($sources)->toContain('not of the 1,300')
        // the half year between Zaboreczno and August 1943 is filled rather than jumped
        ->and($frames['sochy']['body'])->toContain('On 24 June the clearances themselves came back')
        // every perpetrator on this page is German and said to be German
        ->and($frames['night']['body'])->toContain('German police surrounded the village')
        ->and($frames['rescue']['body'])->toContain('the Warsaw district of the General Government')
        ->and($frames['sochy']['body'])->toContain('Werwolf')
        ->and($frames['sochy']['body'])->toContain('until August 1943')
        // Sochy is three weeks before Werwolf and is not folded into it
        ->and($sources)->toContain('came three weeks before that operation')
        // the occupation is given an end before the survivors walk home
        ->and($frames['after']['body'])->toContain('Red Army')
        ->and($frames['after']['body'])->toContain('summer of 1944')
        // the Rotunda is one round gun emplacement of the fortress, not a ring of them
        ->and($frames['today']['body'])->toContain('a round brick gun emplacement of the old fortress');

    // both battles were the Peasant Battalions, and Wojda ended with the village burnt
    expect($frames['rising']['body'])->toContain('a Peasant Battalions company under Jerzy Miller')
        ->and($frames['rising']['body'])->toContain('Peasant Battalions companies under Franciszek Bartlomowicz')
        ->and($frames['rising']['body'])->toContain('Vasily Volodin')
        ->and($frames['rising']['body'])->toContain('The Germans burned the village')
        ->and($sources)->toContain('Wojda was not a victory')
        ->and($sources)->toContain('rather than the Home Army');

    // Majdanek is the camp these people were sent to, not merely the museum that remembers them
    expect($frames['today']['body'])->toContain('9,000 people from these four counties')
        ->and($frames['today']['body'])->toContain('the German camp at Lublin')
        // the museum has no permanent exhibition on them, and the page no longer claims one
        ->and($frames['today']['body'])->not->toContain('permanent exhibition')
        ->and($sources)->toContain('No permanent exhibition on the Zamosc deportees is claimed')
        // the anniversary is kept where it is actually kept
        ->and($frames['today']['body'])->toContain('Krynice and Polany')
        ->and($frames['today']['body'])->not->toContain('marked in the town')
        // the murder of the region's Jews is named rather than silently dropped
        ->and($sources)->toContain('Belzec')
        ->and($sources)->toContain('16 October 1942');

    app()->setLocale('pl');

    $polish = collect(trans('site.zamosc.frames'))->keyBy('key');

    expect(trans('site.zamosc.hero.lede'))->toContain('od 1939 roku')
        ->and(trans('site.zamosc.hero.lede'))->toContain('Generalnym Gubernatorstwem')
        ->and($polish['plan']['body'])->toContain('zbrojnego ramienia partii nazistowskiej')
        ->and($polish['night']['body'])->toContain('niemieckiego obozu Auschwitz')
        ->and($polish['children']['body'])->toContain('co najmniej 150 dzieci')
        ->and($polish['sochy']['body'])->toContain('24 czerwca wróciły same wysiedlenia')
        ->and($polish['sochy']['body'])->toContain('Wilkołak')
        ->and($polish['after']['body'])->toContain('Armia Czerwona')
        ->and($polish['rising']['body'])->toContain('kompania Batalionów Chłopskich pod dowództwem Jerzego Millera')
        ->and($polish['rising']['body'])->toContain('Wasyla Wołodina')
        ->and($polish['rising']['body'])->toContain('Wieś Niemcy spalili')
        ->and($polish['today']['body'])->toContain('Krynicach i Polanach')
        ->and($polish['today']['body'])->toContain('9 tysięcy ludzi')
        ->and($polish['today']['body'])->not->toContain('stałą wystawę')
        ->and(trans('site.zamosc.sources.body'))->toContain('a nie spośród 1300 osób')
        ->and(trans('site.zamosc.sources.body'))->toContain('Wojda nie była zwycięstwem')
        ->and(trans('site.zamosc.sources.body'))->toContain('Bełżcu');

    app()->setLocale('en');
});

it('publishes a real map on the Zamosc article rather than drawing one', function (): void {
    // nothing hand-drawn stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryZamosc.vue')))
        ->not->toContain('Map.vue')
        ->toContain('zamosc-map-lg.jpg')
        ->toContain('with-caption');

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // a map is a source, so it carries its maker, its date and its language
        expect(trans('site.zamosc.map.credit'))->toContain('Madajczyk')
            ->and(trans('site.zamosc.map.credit'))->toContain('1979')
            ->and(trans('site.zamosc.map.caption'))->toContain('1979')
            ->and(trans('site.zamosc.map.alt'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    expect(trans('site.zamosc.map.caption'))->toContain('in Polish')
        ->and(trans('site.zamosc.map.credit'))->toContain('Ludowa Spoldzielnia Wydawnicza');
});

it('renders the ghettos article as a filmstrip', function (): void {
    $this->get('/history/the-ghettos')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryGhettos')
            ->has('translations.ghettos.frames', 9)
        );
});

it('credits every frame of the ghettos article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['ghettos']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/ghettos-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/ghettos-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/ghettos-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['ghettos']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file, except the one object that
    // exists nowhere wider: the scan of the Litzmannstadt ghetto money
    foreach (collect(trans('site.ghettos.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/ghettos-{$photo}-lg.jpg"))[0])
            ->toBe($photo === 'lodz' ? 1175 : 1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/ghettos-hero-{$size}.jpg"))->toBeReadableFile();
        expect(public_path("images/ghettos-map-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.ghettos.sources.body'))->toContain('Title picture')
        ->and(trans('site.ghettos.sources.body'))->toContain('Radegast')
        // the licences are carried over, attribution, share-alike and public domain alike
        ->and(collect(trans('site.ghettos.frames'))->keyBy('key')['before']['credit'])->toContain('CC0')
        ->and(collect(trans('site.ghettos.frames'))->keyBy('key')['orders']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.ghettos.frames'))->keyBy('key')['warsaw']['credit'])->toContain('CC BY 3.0 PL')
        ->and(collect(trans('site.ghettos.frames'))->keyBy('key')['archive']['credit'])->toContain('public domain');
});

it('uses pictures on the ghettos article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/ghettos-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'ghettos-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the ghettos article off the first topic of the Holocaust chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after Zamosc, with no era band slot
    expect(strpos($order, '/history/the-ghettos'))
        ->toBeGreaterThan(strpos($order, '/history/zamosc-expulsions'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/the-ghettos',\s+titleKey: 'ghettos.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'holocaust.0',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.ghettos.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.ghettos.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/the-ghettos')
        ->assertSee(e(trans('site.ghettos.meta.title')), escape: false)
        ->assertSee('/images/cards/ghettos.jpg', escape: false);
});

it('keeps every ghettos frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['ghettos']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the ghettos article from the community to the pavement', function (): void {
    $frames = collect(trans('site.ghettos.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['before', 'orders', 'lodz', 'warsaw', 'inside', 'towns', 'trains', 'archive', 'today'])
        // the community is on stage before anything is done to it
        ->and($byKey['before']['body'])->toContain('since the twelfth century')
        // every institution and office is glossed where it first appears
        ->and($byKey['orders']['body'])->toContain('the head of the German Security Police')
        ->and($byKey['orders']['body'])->toContain('a Council of Jewish Elders, a Judenrat')
        ->and($byKey['orders']['body'])->toContain('the German governor of the General Government')
        // the reader is told why Lodz was renamed before the German name is used
        ->and($byKey['lodz']['body'])->toContain('renamed Litzmannstadt')
        // the man who runs the Warsaw council appears with his job before he acts
        ->and($byKey['inside']['body'])->toContain('an engineer named Adam Czerniakow')
        ->and($byKey['trains']['body'])->toContain('Adam Czerniakow refused')
        // the small ghettos are told against the two everybody pictures
        ->and($byKey['towns']['body'])->toContain('they were the exception')
        // the murder is given its own decision rather than following on by itself
        ->and($byKey['trains']['body'])->toContain('separate German decisions')
        // the uprising is handed on rather than told here
        ->and($byKey['archive']['body'])->toContain('article of its own');

    // the reader is told Germany was already occupying the country before the orders arrive
    expect(trans('site.ghettos.hero.lede'))->toContain('since 1939')
        ->and(trans('site.ghettos.hero.lede'))->toContain('General Government');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettos.frames'))->keyBy('key');

    expect(trans('site.ghettos.hero.lede'))->toContain('od 1939 roku')
        ->and(trans('site.ghettos.hero.lede'))->toContain('Generalne Gubernatorstwo')
        ->and($polish['orders']['body'])->toContain('radę starszych, Judenrat')
        ->and($polish['inside']['body'])->toContain('inżynier Adam Czerniaków')
        ->and($polish['archive']['body'])->toContain('osobny artykuł');

    app()->setLocale('en');
});

it('keeps the hard-won ghetto facts the way the sources give them', function (): void {
    $frames = collect(trans('site.ghettos.frames'))->keyBy('key');
    $sources = trans('site.ghettos.sources.body');

    // the community before, attributed to the museums that publish the counts
    expect($frames['before']['body'])->toContain('three million people in 1933')
        ->and($frames['before']['body'])->toContain('9.5 per cent')
        ->and($frames['before']['body'])->toContain('370,000')
        // the first orders, dated, with the man who signed each one
        ->and($frames['orders']['body'])->toContain('Reinhard Heydrich')
        ->and($frames['orders']['body'])->toContain('21 September')
        ->and($frames['orders']['body'])->toContain('railway junctions')
        ->and($frames['orders']['body'])->toContain('8 October 1939')
        ->and($frames['orders']['body'])->toContain('Piotrkow Trybunalski')
        ->and($frames['orders']['body'])->toContain('23 November')
        ->and($frames['orders']['body'])->toContain('Hans Frank')
        // Lodz, sealed on a day, counted, with the man the Germans put in charge
        ->and($frames['lodz']['body'])->toContain('30 April 1940')
        ->and($frames['lodz']['body'])->toContain('160,000')
        ->and($frames['lodz']['body'])->toContain('Chaim Rumkowski')
        ->and($frames['lodz']['body'])->toContain('74 workshops')
        // Warsaw, with the area, the density, both calorie figures and the dead
        ->and($frames['warsaw']['body'])->toContain('16 November')
        ->and($frames['warsaw']['body'])->toContain('400,000')
        ->and($frames['warsaw']['body'])->toContain('3.4 square kilometres')
        ->and($frames['warsaw']['body'])->toContain('7.2 to a room')
        ->and($frames['warsaw']['body'])->toContain('181 calories')
        ->and($frames['warsaw']['body'])->toContain('1,125')
        ->and($frames['warsaw']['body'])->toContain('83,000')
        ->and($frames['warsaw']['body'])->toContain('26 January 1942')
        ->and($frames['warsaw']['body'])->toContain('Chlodna street')
        // the small ghettos, counted the way the museum counts them
        ->and($frames['towns']['body'])->toContain('1,300 ghettos')
        ->and($frames['towns']['body'])->toContain('1,100')
        ->and($frames['towns']['body'])->toContain('open ghettos')
        // the liquidation, dated, attributed, with the people who did not come back
        ->and($frames['trains']['body'])->toContain('22 July 1942')
        ->and($frames['trains']['body'])->toContain('21 September')
        ->and($frames['trains']['body'])->toContain('265,000')
        ->and($frames['trains']['body'])->toContain('Treblinka')
        ->and($frames['trains']['body'])->toContain('23 July')
        ->and($frames['trains']['body'])->toContain('5 August')
        ->and($frames['trains']['body'])->toContain('Janusz Korczak')
        // the archive, with the addresses, the dates it came out and the register
        ->and($frames['archive']['body'])->toContain('Emanuel Ringelblum')
        ->and($frames['archive']['body'])->toContain('Oneg Shabbat')
        ->and($frames['archive']['body'])->toContain('35,000 documents')
        ->and($frames['archive']['body'])->toContain('68 Nowolipki street')
        ->and($frames['archive']['body'])->toContain('September 1946')
        ->and($frames['archive']['body'])->toContain('December 1950')
        ->and($frames['archive']['body'])->toContain('1999')
        // today, with the places and the institutions that keep them
        ->and($frames['today']['body'])->toContain('POLIN')
        ->and($frames['today']['body'])->toContain('more than twenty points')
        ->and($frames['today']['body'])->toContain('Umschlagplatz')
        ->and($frames['today']['body'])->toContain('Radegast');

    // the camps and the ghettos are attributed to the state that made them
    expect($frames['orders']['body'])->toContain('German orders')
        ->and($frames['trains']['body'])->toContain('the German plan to murder')
        ->and($sources)->not->toContain('Polish camp')
        ->and($sources)->not->toContain('Polish ghetto');

    // what the sources disagree about is declared rather than smoothed over
    expect($sources)->toContain('R58/954')
        ->and($sources)->toContain('3.3 million are often quoted')
        ->and($sources)->toContain('Counts between 250,000 and 300,000')
        ->and($sources)->toContain('about 150 by dzieje.pl')
        ->and($sources)->toContain('the figures published for it vary')
        // the restraint about pictures is stated, not merely practised
        ->and($sources)->toContain('No photograph of a dead or dying person')
        ->and($sources)->toContain('German propaganda companies')
        // what is deliberately left to another page is named
        ->and($sources)->toContain('Stefania Wilczynska')
        ->and($sources)->toContain('uprising of April 1943 is named and not told')
        ->and($sources)->toContain('Chelmno');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettos.frames'))->keyBy('key');

    expect($polish['before']['body'])->toContain('trzy miliony')
        ->and($polish['orders']['body'])->toContain('8 października 1939')
        ->and($polish['orders']['body'])->toContain('23 listopada')
        ->and($polish['lodz']['body'])->toContain('30 kwietnia 1940')
        ->and($polish['lodz']['body'])->toContain('160 tysięcy')
        ->and($polish['warsaw']['body'])->toContain('16 listopada')
        ->and($polish['warsaw']['body'])->toContain('3,4 kilometra kwadratowego')
        ->and($polish['warsaw']['body'])->toContain('181 kalorii')
        ->and($polish['trains']['body'])->toContain('22 lipca 1942')
        ->and($polish['trains']['body'])->toContain('265 tysięcy')
        ->and($polish['archive']['body'])->toContain('Nowolipkach 68')
        ->and($polish['today']['body'])->toContain('ponad dwudziestu')
        ->and(trans('site.ghettos.sources.body'))->toContain('kompanie propagandowe')
        ->and(trans('site.ghettos.sources.body'))->toContain('Chełmnie nad Nerem');

    app()->setLocale('en');
});

it('publishes a real map on the ghettos article rather than drawing one', function (): void {
    // nothing hand-drawn stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryGhettos.vue')))
        ->not->toContain('Map.vue')
        ->toContain('ghettos-map-lg.jpg')
        ->toContain('with-caption');

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // a map is a source, so it carries its maker, its date and its language
        expect(trans('site.ghettos.map.credit'))->toContain('Nowy Kurjer Warszawski')
            ->and(trans('site.ghettos.map.credit'))->toContain('1940')
            ->and(trans('site.ghettos.map.caption'))->toContain('1940')
            ->and(trans('site.ghettos.map.alt'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    expect(trans('site.ghettos.map.caption'))->toContain('in Polish')
        // the paper is placed in the occupation without claiming a publisher no institution confirmed
        ->and(trans('site.ghettos.map.caption'))->toContain('under German occupation')
        ->and(trans('site.ghettos.sources.body'))->toContain('no museum or institute page saying so was opened');
});

it('does not call the Lodz ghetto the longest lasting in Europe', function (): void {
    // Budapest was shut in November 1944 and stood into February 1945, after Lodz was gone
    foreach (['en' => ['last ghetto left in German-occupied Poland', 'Budapest'], 'pl' => ['ostatnim gettem w okupowanej Polsce', 'Budapeszcie']] as $locale => [$claim, $counterexample]) {
        app()->setLocale($locale);

        $lodz = collect(trans('site.ghettos.frames'))->keyBy('key')['lodz']['body'];

        expect($lodz)->toContain($claim)
            ->and($lodz)->not->toContain('Europe')
            ->and($lodz)->not->toContain('Europie')
            ->and(trans('site.ghettos.sources.body'))->toContain($counterexample);
    }

    app()->setLocale('en');
});

it('tells the reader how the Lodz ghetto ended', function (): void {
    // the frame is labelled 1940 to 1944 and used to stop in July 1942
    $english = collect(trans('site.ghettos.frames'))->keyBy('key')['lodz']['body'];

    expect($english)->toContain('Chelmno')
        ->and($english)->toContain('70,000 Jews and 4,300 Roma')
        ->and($english)->toContain('67,000')
        ->and($english)->toContain('Auschwitz in August 1944')
        // the argument about Rumkowski is still on the page rather than settled
        ->and($english)->toContain('Historians still argue');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettos.frames'))->keyBy('key')['lodz']['body'];

    expect($polish)->toContain('Chełmnie')
        ->and($polish)->toContain('70 tysięcy Żydów i 4300 Romów')
        ->and($polish)->toContain('67 tysięcy')
        ->and($polish)->toContain('Auschwitz w sierpniu 1944')
        ->and($polish)->toContain('Spór o Rumkowskiego trwa do dziś');

    app()->setLocale('en');
});

it('dates the killing from Chelmno rather than from the emptying of the ghettos', function (): void {
    // the first transport reached Chelmno on 7 December 1941, before any ghetto was emptied
    $english = collect(trans('site.ghettos.frames'))->keyBy('key')['trains']['body'];

    expect($english)->toContain('Chelmno in December 1941')
        ->and($english)->not->toContain('it came in 1942')
        ->and(trans('site.ghettos.sources.body'))->toContain('7 December 1941');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettos.frames'))->keyBy('key')['trains']['body'];

    expect($polish)->toContain('w grudniu 1941 roku w Chełmnie')
        ->and(trans('site.ghettos.sources.body'))->toContain('7 grudnia 1941');

    app()->setLocale('en');
});

it('names Stefania Wilczynska beside Korczak in the frame, not only in the sources', function (): void {
    expect(collect(trans('site.ghettos.frames'))->keyBy('key')['trains']['body'])
        ->toContain('Stefania Wilczynska')
        // the sources no longer carry the note that she was left out
        ->and(trans('site.ghettos.sources.body'))->not->toContain('she should be named');

    app()->setLocale('pl');

    expect(collect(trans('site.ghettos.frames'))->keyBy('key')['trains']['body'])
        ->toContain('Stefania Wilczyńska')
        ->and(trans('site.ghettos.sources.body'))->not->toContain('należy ją wymienić');

    app()->setLocale('en');
});

it('explains why Jews had to cross Chlodna street on a footbridge', function (): void {
    // the footbridge joined the large and the small ghetto, which the frame never used to say
    expect(collect(trans('site.ghettos.frames'))->keyBy('key')['warsaw']['body'])
        ->toContain('cut the district in two')
        ->and(collect(trans('site.ghettos.frames'))->keyBy('key')['warsaw']['body'])
        ->toContain('from the large ghetto to the small one');

    app()->setLocale('pl');

    expect(collect(trans('site.ghettos.frames'))->keyBy('key')['warsaw']['body'])
        ->toContain('na dwie części')
        ->and(collect(trans('site.ghettos.frames'))->keyBy('key')['warsaw']['body'])
        ->toContain('z dużego getta do małego');

    app()->setLocale('en');
});

it('describes POLIN as the museum describes itself', function (): void {
    // the core exhibition has a Holocaust gallery, so the page may not say it stops before the murder
    $english = collect(trans('site.ghettos.frames'))->keyBy('key')['today']['body'];

    expect($english)->toContain('one gallery of eight')
        ->and($english)->not->toContain('rather than the murder')
        ->and(trans('site.ghettos.sources.body'))->toContain('eight galleries');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettos.frames'))->keyBy('key')['today']['body'];

    expect($polish)->toContain('jedną z ośmiu galerii')
        ->and($polish)->not->toContain('a nie o samej zagładzie')
        ->and(trans('site.ghettos.sources.body'))->toContain('ośmiu galeriach');

    app()->setLocale('en');
});

it('does not print a count of the ghetto boundary markers it cannot source', function (): void {
    // twenty-one were set in 2008 and a twenty-second was added in 2010
    foreach (['en' => ['more than twenty points', 'Mur getta 1940 and Ghetto wall 1943', '27 January 2010'], 'pl' => ['ponad dwudziestu miejscach', 'Mur getta 1940 i Ghetto wall 1943', '27 stycznia 2010']] as $locale => [$hedge, $inscription, $later]) {
        app()->setLocale($locale);

        expect(collect(trans('site.ghettos.frames'))->keyBy('key')['today']['body'])
            ->toContain($hedge)
            ->and(collect(trans('site.ghettos.frames'))->keyBy('key')['today']['body'])->toContain($inscription)
            ->and(trans('site.ghettos.sources.body'))->toContain($later);
    }

    app()->setLocale('en');
});

it('names the ruler who put Jewish settlement in Poland on a legal footing', function (): void {
    // the page used to say only that rulers invited them, with no charter and no name
    $english = collect(trans('site.ghettos.frames'))->keyBy('key')['before']['body'];

    expect($english)->toContain('Boleslaw the Pious')
        ->and($english)->toContain('1264')
        ->and($english)->toContain('Kalisz')
        // the Warsaw share is given the way the museum gives it
        ->and($english)->toContain('under 30 per cent')
        ->and(trans('site.ghettos.sources.body'))->toContain('16 August 1264');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettos.frames'))->keyBy('key')['before']['body'];

    expect($polish)->toContain('Bolesław Pobożny')
        ->and($polish)->toContain('1264')
        ->and($polish)->toContain('Kaliszu')
        ->and($polish)->toContain('30 procent')
        ->and(trans('site.ghettos.sources.body'))->toContain('16 sierpnia 1264');

    app()->setLocale('en');
});

it('renders the death camps article as a filmstrip', function (): void {
    $this->get('/history/the-death-camps')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryCamps')
            ->has('translations.camps.frames', 9)
        );
});

it('credits every frame of the death camps article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['camps']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/camps-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/camps-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/camps-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['camps']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.camps.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/camps-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/camps-hero-{$size}.jpg"))->toBeReadableFile();
        expect(public_path("images/camps-map-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.camps.sources.body'))->toContain('Title picture')
        ->and(trans('site.camps.sources.body'))->toContain('Treblinka')
        // the licences are carried over, attribution, share-alike and public domain alike
        ->and(collect(trans('site.camps.frames'))->keyBy('key')['decision']['credit'])->toContain('public domain')
        ->and(collect(trans('site.camps.frames'))->keyBy('key')['reinhard']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.camps.frames'))->keyBy('key')['transport']['credit'])->toContain('CC BY-SA 3.0 PL')
        ->and(collect(trans('site.camps.frames'))->keyBy('key')['world']['credit'])->toContain('CC0')
        ->and(collect(trans('site.camps.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY 2.0');
});

it('uses pictures on the death camps article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/camps-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'camps-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the death camps article off the second topic of the Holocaust chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the ghettos, with no era band slot
    expect(strpos($order, '/history/the-death-camps'))
        ->toBeGreaterThan(strpos($order, '/history/the-ghettos'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/the-death-camps',\s+titleKey: 'camps.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'holocaust.1',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.camps.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.camps.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/the-death-camps')
        ->assertSee(e(trans('site.camps.meta.title')), escape: false)
        ->assertSee('/images/cards/camps.jpg', escape: false);
});

it('keeps every death camps frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['camps']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the death camps article from the decision to the visit', function (): void {
    $frames = collect(trans('site.camps.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['decision', 'reinhard', 'transport', 'auschwitz', 'majdanek', 'world', 'revolts', 'evidence', 'today'])
        // the shift from shooting to gassing is told, not assumed
        ->and($byKey['decision']['body'])->toContain('by shooting')
        // every office is glossed where the man holding it first appears
        ->and($byKey['decision']['body'])->toContain('head of the German Security Police')
        ->and($byKey['reinhard']['body'])->toContain('An SS general, Odilo Globocnik')
        ->and($byKey['reinhard']['body'])->toContain('a police captain, Christian Wirth')
        // the General Government is explained again rather than assumed from another article
        ->and($byKey['reinhard']['body'])->toContain('the colony Nazi Germany had made of central Poland')
        // Auschwitz is given its first life before it is given its second
        ->and($byKey['auschwitz']['body'])->toContain('did not begin as a killing centre')
        // the Sonderkommando are introduced before the revolt uses the word
        ->and($byKey['transport']['body'])->toContain('the Sonderkommando, prisoners kept alive')
        ->and($byKey['revolts']['body'])->toContain('Sonderkommando blew up crematorium IV')
        // Pilecki is handed on rather than told here
        ->and($byKey['world']['body'])->toContain('own article here')
        // the gate caught a name with no role attached to it
        ->and($byKey['revolts']['body'])->toContain('Leon Feldhendler, one of the prisoners there')
        ->and($byKey['revolts']['body'])->toContain('a Soviet army officer');

    // the reader arrives knowing the ghettos were being emptied and where the trains went
    expect(trans('site.camps.hero.lede'))->toContain('ghettos')
        ->and(trans('site.camps.hero.lede'))->toContain('1942');

    app()->setLocale('pl');

    $polish = collect(trans('site.camps.frames'))->keyBy('key');

    expect(trans('site.camps.hero.lede'))->toContain('getta')
        ->and($polish['reinhard']['body'])->toContain('Generalnym Gubernatorstwie')
        ->and($polish['auschwitz']['body'])->toContain('nie zaczynał jako ośrodek zagłady')
        ->and($polish['world']['body'])->toContain('własny artykuł');

    app()->setLocale('en');
});

it('keeps the hard-won death camp facts the way the sources give them', function (): void {
    $frames = collect(trans('site.camps.frames'))->keyBy('key');
    $sources = trans('site.camps.sources.body');

    // the decision, dated where it can be dated and attributed where it cannot
    expect($frames['decision']['body'])->toContain('7 December 1941')
        ->and($frames['decision']['body'])->toContain('Chelmno')
        ->and($frames['decision']['body'])->toContain('20 January 1942')
        ->and($frames['decision']['body'])->toContain('Reinhard Heydrich')
        ->and($frames['decision']['body'])->toContain('fifteen officials')
        ->and($frames['decision']['body'])->toContain('nothing was decided there')
        // Operation Reinhard, with the three camps, the two men and the arithmetic
        ->and($frames['reinhard']['body'])->toContain('17 March')
        ->and($frames['reinhard']['body'])->toContain('23 July')
        ->and($frames['reinhard']['body'])->toContain('Sobibor')
        ->and($frames['reinhard']['body'])->toContain('Trawniki')
        ->and($frames['reinhard']['body'])->toContain('434,500')
        ->and($frames['reinhard']['body'])->toContain('1.7 million')
        // the transport, with the deception, the time and a named survivor
        ->and($frames['transport']['body'])->toContain('wooden clock')
        ->and($frames['transport']['body'])->toContain('the tube')
        ->and($frames['transport']['body'])->toContain('two hours')
        ->and($frames['transport']['body'])->toContain('Samuel Willenberg')
        // Auschwitz, from the first Polish transport to the museum figures
        ->and($frames['auschwitz']['body'])->toContain('14 June 1940')
        ->and($frames['auschwitz']['body'])->toContain('October 1941')
        ->and($frames['auschwitz']['body'])->toContain('1.1 million')
        ->and($frames['auschwitz']['body'])->toContain('Auschwitz-Birkenau Memorial and Museum')
        ->and($frames['auschwitz']['body'])->toContain('70,000 Poles')
        ->and($frames['auschwitz']['body'])->toContain('21,000 Roma and Sinti')
        ->and($frames['auschwitz']['body'])->toContain('15,000 Soviet prisoners of war')
        // Majdanek and the two days of November 1943
        ->and($frames['majdanek']['body'])->toContain('Lublin')
        ->and($frames['majdanek']['body'])->toContain('3 and 4 November 1943')
        ->and($frames['majdanek']['body'])->toContain('42,000')
        ->and($frames['majdanek']['body'])->toContain('Operation Harvest Festival')
        ->and($frames['majdanek']['body'])->toContain('Poniatowa')
        // the three men who told the world, each with a date
        ->and($frames['world']['body'])->toContain('Witold Pilecki')
        ->and($frames['world']['body'])->toContain('26 April 1943')
        ->and($frames['world']['body'])->toContain('Jan Karski')
        ->and($frames['world']['body'])->toContain('Anthony Eden')
        ->and($frames['world']['body'])->toContain('Franklin Roosevelt')
        ->and($frames['world']['body'])->toContain('7 April 1944')
        ->and($frames['world']['body'])->toContain('Rudolf Vrba and Alfred Wetzler')
        // the three revolts, each dated and counted
        ->and($frames['revolts']['body'])->toContain('2 August 1943')
        ->and($frames['revolts']['body'])->toContain('14 October 1943')
        ->and($frames['revolts']['body'])->toContain('Leon Feldhendler')
        ->and($frames['revolts']['body'])->toContain('Alexander Pechersky')
        ->and($frames['revolts']['body'])->toContain('Fifty-eight')
        ->and($frames['revolts']['body'])->toContain('7 October 1944')
        // the erasure, named, and the evidence that outlived it
        ->and($frames['evidence']['body'])->toContain('Aktion 1005')
        ->and($frames['evidence']['body'])->toContain('Heinrich Himmler')
        ->and($frames['evidence']['body'])->toContain('Paul Blobel')
        ->and($frames['evidence']['body'])->toContain('Sonderkommando buried manuscripts')
        // the gate added the end of it: the reader is not left at the erasure
        ->and($frames['evidence']['body'])->toContain('27 January 1945')
        ->and($frames['evidence']['body'])->toContain('7,000')
        // today, with the booking rule and the five places
        ->and($frames['today']['body'])->toContain('visit.auschwitz.org')
        ->and($frames['today']['body'])->toContain('fourteen')
        ->and($frames['today']['body'])->toContain('funeral');

    // what the sources disagree about is declared rather than smoothed over
    expect($sources)->toContain('HW 16/23')
        ->and($sources)->toContain('1,274,166')
        ->and($sources)->toContain('the museum says May in one article and April in another')
        ->and($sources)->toContain('70,000 to 75,000 Poles')
        // the restraint about pictures is stated, not merely practised
        ->and($sources)->toContain('No photograph of a corpse')
        ->and($sources)->toContain('made by the perpetrators')
        // what is deliberately left to another page is named
        ->and($sources)->toContain('he has an article of his own in this series')
        ->and($sources)->toContain('Ringelblum');

    app()->setLocale('pl');

    $polish = collect(trans('site.camps.frames'))->keyBy('key');

    expect($polish['decision']['body'])->toContain('7 grudnia 1941')
        ->and($polish['decision']['body'])->toContain('20 stycznia 1942')
        ->and($polish['reinhard']['body'])->toContain('17 marca')
        ->and($polish['reinhard']['body'])->toContain('1,7 miliona')
        ->and($polish['transport']['body'])->toContain('Samuel Willenberg')
        ->and($polish['auschwitz']['body'])->toContain('14 czerwca 1940')
        ->and($polish['auschwitz']['body'])->toContain('1,1 miliona')
        ->and($polish['majdanek']['body'])->toContain('3 i 4 listopada 1943')
        ->and($polish['majdanek']['body'])->toContain('42 tysięcy')
        ->and($polish['world']['body'])->toContain('26 kwietnia 1943')
        ->and($polish['revolts']['body'])->toContain('14 października 1943')
        ->and($polish['evidence']['body'])->toContain('Akcja 1005')
        ->and($polish['evidence']['body'])->toContain('27 stycznia 1945')
        ->and($polish['revolts']['body'])->toContain('jeden z tamtejszych więźniów')
        ->and($polish['today']['body'])->toContain('visit.auschwitz.org')
        ->and(trans('site.camps.sources.body'))->toContain('HW 16/23')
        ->and(trans('site.camps.sources.body'))->toContain('Chełmnie nad Nerem');

    app()->setLocale('en');
});

it('renders the ghetto uprising article as a filmstrip', function (): void {
    $this->get('/history/warsaw-ghetto-uprising')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryGhettoUprising')
            ->has('translations.ghettouprising.frames', 9)
        );
});

it('credits every frame of the ghetto uprising article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['ghettouprising']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/ghettouprising-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/ghettouprising-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/ghettouprising-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['ghettouprising']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.ghettouprising.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/ghettouprising-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/ghettouprising-hero-{$size}.jpg"))->toBeReadableFile();
        expect(public_path("images/ghettouprising-aerial-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.ghettouprising.sources.body'))->toContain('Title picture')
        ->and(trans('site.ghettouprising.sources.body'))->toContain('Jabrzemski')
        // the licences are carried over, attribution, share-alike and public domain alike
        ->and(collect(trans('site.ghettouprising.frames'))->keyBy('key')['rump']['credit'])->toContain('CC BY-SA 3.0 PL')
        ->and(collect(trans('site.ghettouprising.frames'))->keyBy('key')['fighters']['credit'])->toContain('CC0')
        ->and(collect(trans('site.ghettouprising.frames'))->keyBy('key')['bunkers']['credit'])->toContain('public domain')
        ->and(collect(trans('site.ghettouprising.frames'))->keyBy('key')['april']['credit'])->toContain('CC BY 2.0')
        ->and(collect(trans('site.ghettouprising.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the ghetto uprising article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/ghettouprising-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'ghettouprising-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the aerial photograph
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the ghetto uprising article off the third topic of the Holocaust chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the death camps, with no era band slot
    expect(strpos($order, '/history/warsaw-ghetto-uprising'))
        ->toBeGreaterThan(strpos($order, '/history/the-death-camps'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/warsaw-ghetto-uprising',\s+titleKey: 'ghettouprising.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'holocaust.2',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.ghettouprising.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.ghettouprising.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/warsaw-ghetto-uprising')
        ->assertSee(e(trans('site.ghettouprising.meta.title')), escape: false)
        ->assertSee('/images/cards/ghettouprising.jpg', escape: false);
});

it('keeps every ghetto uprising frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['ghettouprising']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('tells the reader that 1943 and 1944 are two different risings', function (): void {
    foreach (['en' => ['1943', '1944', 'Home Army'], 'pl' => ['1943', '1944', 'Armii Krajowej']] as $locale => $needles) {
        app()->setLocale($locale);

        $lede = trans('site.ghettouprising.hero.lede');

        foreach ($needles as $needle) {
            expect($lede)->toContain($needle);
        }
    }

    app()->setLocale('en');

    // the distinction is made in words, not left to the dates
    expect(trans('site.ghettouprising.hero.lede'))->toContain('two Warsaw risings')
        ->and(trans('site.ghettouprising.hero.lede'))->toContain('own article here')
        ->and(trans('site.ghettouprising.meta.title'))->toContain('1944');

    app()->setLocale('pl');

    expect(trans('site.ghettouprising.hero.lede'))->toContain('powstania w Warszawie były dwa')
        ->and(trans('site.ghettouprising.hero.lede'))->toContain('osobny artykuł')
        ->and(trans('site.ghettouprising.meta.title'))->toContain('1944');

    app()->setLocale('en');
});

it('bridges the ghetto uprising article from the rump ghetto to the memorials', function (): void {
    $frames = collect(trans('site.ghettouprising.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['rump', 'fighters', 'january', 'bunkers', 'april', 'burning', 'mila', 'after', 'today'])
        // the reader arrives from the deportations of 1942 and is told what they were
        ->and($byKey['rump']['body'])->toContain('22 July and 21 September 1942')
        ->and($byKey['rump']['body'])->toContain('Treblinka')
        // every organisation is glossed where it first appears
        ->and($byKey['fighters']['body'])->toContain('The Jewish Combat Organization, the ZOB')
        ->and($byKey['fighters']['body'])->toContain('the Jewish Military Union or ZZW')
        // Anielewicz is introduced before January uses his name alone
        ->and($byKey['fighters']['body'])->toContain('Mordechai Anielewicz')
        ->and($byKey['january']['body'])->toContain('Anielewicz put his fighters')
        // Wilner is introduced in January and used again at the bunker
        ->and($byKey['january']['body'])->toContain('Arie Wilner, the ZOB emissary to it')
        ->and($byKey['mila']['body'])->toContain('Arie Wilner called on the others')
        // Stroop arrives with his office before the burning frame uses his name alone
        ->and($byKey['april']['body'])->toContain('an SS general, Jurgen Stroop')
        ->and($byKey['burning']['body'])->toContain('Stroop would not fight underground')
        // the bunker at 18 Mila street is introduced before the command dies in it
        ->and($byKey['bunkers']['body'])->toContain('command bunker at 18 Mila street')
        // Edelman is named as a survivor, not left as a name in the memory frame
        ->and($byKey['mila']['body'])->toContain('Marek Edelman')
        ->and($byKey['today']['body'])->toContain('Marek Edelman');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettouprising.frames'))->keyBy('key');

    expect($polish['fighters']['body'])->toContain('Żydowska Organizacja Bojowa')
        ->and($polish['fighters']['body'])->toContain('Żydowski Związek Wojskowy')
        ->and($polish['april']['body'])->toContain('generał SS Jurgen Stroop')
        ->and($polish['mila']['body'])->toContain('Marek Edelman');

    app()->setLocale('en');
});

it('keeps the hard-won ghetto uprising facts the way the sources give them', function (): void {
    $frames = collect(trans('site.ghettouprising.frames'))->keyBy('key');
    $sources = trans('site.ghettouprising.sources.body');

    // what was left, with the disagreement printed rather than smoothed away
    expect($frames['rump']['body'])->toContain('265,000')
        ->and($frames['rump']['body'])->toContain('60,000')
        ->and($frames['rump']['body'])->toContain('70,000 to 80,000')
        ->and($frames['rump']['body'])->toContain('45,000 to 50,000')
        ->and($frames['rump']['body'])->toContain('35,000')
        // the two organisations, dated and named
        ->and($frames['fighters']['body'])->toContain('28 July 1942')
        ->and($frames['fighters']['body'])->toContain('Ha-Szomer ha-Cair')
        ->and($frames['fighters']['body'])->toContain('3 September')
        ->and($frames['fighters']['body'])->toContain('Jozef Kaplan')
        ->and($frames['fighters']['body'])->toContain('five pistols')
        ->and($frames['fighters']['body'])->toContain('Pawel Frenkel and Leon Rodal')
        ->and($frames['fighters']['body'])->toContain('Krzysztof Persak')
        ->and($frames['fighters']['body'])->toContain('ten pistols')
        // January 1943, with the order, the count and what it changed
        ->and($frames['january']['body'])->toContain('18 January 1943')
        ->and($frames['january']['body'])->toContain('8,000')
        ->and($frames['january']['body'])->toContain('Heinrich Himmler')
        ->and($frames['january']['body'])->toContain('Cywia Lubetkin')
        ->and($frames['january']['body'])->toContain('5,000 to 6,500')
        ->and($frames['january']['body'])->toContain('fifty pistols, fifty grenades')
        // the bunkers, and why they mattered
        ->and($frames['bunkers']['body'])->toContain('Several hundred shelters')
        ->and($frames['bunkers']['body'])->toContain('six ways out')
        ->and($frames['bunkers']['body'])->toContain('four weeks instead of three days')
        // 19 April, with the command change and the flags
        ->and($frames['april']['body'])->toContain('19 April 1943')
        ->and($frames['april']['body'])->toContain('Passover')
        ->and($frames['april']['body'])->toContain('Gesia and Nalewki')
        ->and($frames['april']['body'])->toContain('twelve German casualties')
        ->and($frames['april']['body'])->toContain('Ferdinand von Sammern-Frankenegg')
        ->and($frames['april']['body'])->toContain('Muranowski square')
        ->and($frames['april']['body'])->toContain('almost four days')
        // the burning, and the German record of it
        ->and($frames['burning']['body'])->toContain('115 pictures')
        ->and($frames['burning']['body'])->toContain('Heinrich Himmler')
        ->and($frames['burning']['body'])->toContain('does not reprint those photographs')
        // 8 May and the sewers
        ->and($frames['mila']['body'])->toContain('27 April')
        ->and($frames['mila']['body'])->toContain('8 May')
        ->and($frames['mila']['body'])->toContain('three hundred people')
        ->and($frames['mila']['body'])->toContain('About a hundred fighters died')
        ->and($frames['mila']['body'])->toContain('Symcha Rotem')
        ->and($frames['mila']['body'])->toContain('sewers')
        // the end, the false total and the camp on the rubble
        ->and($frames['after']['body'])->toContain('16 May 1943')
        ->and($frames['after']['body'])->toContain('Tlomackie')
        ->and($frames['after']['body'])->toContain('56,065')
        ->and($frames['after']['body'])->toContain('certainly inflated')
        ->and($frames['after']['body'])->toContain('7,000')
        ->and($frames['after']['body'])->toContain('42,000')
        ->and($frames['after']['body'])->toContain('KL Warschau')
        ->and($frames['after']['body'])->toContain('348 prisoners')
        // memory, with the dates a visitor can check on the spot
        ->and($frames['today']['body'])->toContain('19 April 2013')
        ->and($frames['today']['body'])->toContain('19 April 1948')
        ->and($frames['today']['body'])->toContain('Nathan Rapoport')
        ->and($frames['today']['body'])->toContain('Albert Speer')
        ->and($frames['today']['body'])->toContain('7 December 1970')
        ->and($frames['today']['body'])->toContain('daffodil');

    // what the sources disagree about is declared rather than smoothed over
    expect($sources)->toContain('Krzysztof Persak')
        ->and($sources)->toContain('Przeglad Historyczno-Wojskowy')
        ->and($sources)->toContain('Stanislaw Weber')
        ->and($sources)->toContain('sixty or seventy pistols')
        // a figure repeated everywhere that no institution would stand behind
        ->and($sources)->toContain('about 13,000 killed')
        ->and($sources)->toContain('does not appear here')
        // a man historians regard as invented is named only to say he is left out
        ->and($sources)->toContain('Dawid Apfelbaum')
        ->and($sources)->toContain('fictitious figure')
        // the restraint about the perpetrators' photographs is stated, not merely practised
        ->and($sources)->toContain('No photograph from the Stroop report')
        ->and($sources)->toContain('boy with his hands raised')
        // and the decision not to publish a map is stated too
        ->and($sources)->toContain('No map is published on this page');

    app()->setLocale('pl');

    $polish = collect(trans('site.ghettouprising.frames'))->keyBy('key');

    expect($polish['rump']['body'])->toContain('265 tysięcy')
        ->and($polish['fighters']['body'])->toContain('28 lipca 1942')
        ->and($polish['fighters']['body'])->toContain('Krzysztof Persak')
        ->and($polish['january']['body'])->toContain('18 stycznia 1943')
        ->and($polish['january']['body'])->toContain('Cywii Lubetkin')
        ->and($polish['april']['body'])->toContain('19 kwietnia 1943')
        ->and($polish['april']['body'])->toContain('placu Muranowskim')
        ->and($polish['mila']['body'])->toContain('8 maja')
        ->and($polish['mila']['body'])->toContain('Symcha Rotem')
        ->and($polish['after']['body'])->toContain('56 065')
        ->and($polish['after']['body'])->toContain('KL Warschau')
        ->and($polish['after']['body'])->toContain('348 więźniów')
        ->and($polish['today']['body'])->toContain('19 kwietnia 1948')
        ->and($polish['today']['body'])->toContain('żonkila')
        ->and(trans('site.ghettouprising.sources.body'))->toContain('13 tysięcy')
        ->and(trans('site.ghettouprising.sources.body'))->toContain('Apfelbauma')
        ->and(trans('site.ghettouprising.sources.body'))->toContain('nie ma mapy');

    app()->setLocale('en');
});

it('renders the underground state article as a filmstrip', function (): void {
    $this->get('/history/underground-state')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryUnderground')
            ->has('translations.underground.frames', 9)
        );
});

it('credits every frame of the underground state article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['underground']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/underground-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/underground-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/underground-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['underground']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.underground.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/underground-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/underground-hero-{$size}.jpg"))->toBeReadableFile();
        expect(public_path("images/underground-anchor-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.underground.sources.body'))->toContain('Title picture')
        ->and(trans('site.underground.sources.body'))->toContain('Grycuk')
        // the licences are carried over, attribution, share-alike and public domain alike
        ->and(collect(trans('site.underground.frames'))->keyBy('key')['meaning']['credit'])->toContain('public domain')
        ->and(collect(trans('site.underground.frames'))->keyBy('key')['supply']['credit'])->toContain('CC BY-SA 3.0')
        ->and(collect(trans('site.underground.frames'))->keyBy('key')['intelligence']['credit'])->toContain('CC BY 2.0')
        ->and(collect(trans('site.underground.frames'))->keyBy('key')['end']['credit'])->toContain('CC BY 3.0 PL')
        ->and(collect(trans('site.underground.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY-SA 2.5')
        ->and(trans('site.underground.anchor.credit'))->toContain('CC BY-SA 4.0');
});

it('uses pictures on the underground state article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/underground-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'underground-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the anchor exhibit
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the underground state article off the first topic of the resistance chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the ghetto uprising, with no era band slot
    expect(strpos($order, '/history/underground-state'))
        ->toBeGreaterThan(strpos($order, '/history/warsaw-ghetto-uprising'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/underground-state',\s+titleKey: 'underground.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'resistance.0',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.underground.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.underground.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/underground-state')
        ->assertSee(e(trans('site.underground.meta.title')), escape: false)
        ->assertSee('/images/cards/underground.jpg', escape: false);
});

it('keeps every underground state frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['underground']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the underground state article from an occupied country to the anchor on the wall', function (): void {
    $frames = collect(trans('site.underground.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['meaning', 'beginning', 'delegatura', 'schools', 'supply', 'intelligence', 'politics', 'end', 'today'])
        // the Delegate's Office is glossed in Polish where it first appears, then used by name
        ->and($byKey['meaning']['body'])->toContain('Delegatura Rzadu na Kraj')
        ->and($byKey['delegatura']['body'])->toContain('Government Delegate')
        // the Home Army is named before the later frames lean on the name
        ->and($byKey['meaning']['body'])->toContain('Armia Krajowa')
        // the reader is handed on to the London government rather than told its story again
        ->and($byKey['meaning']['body'])->toContain('phenomenon on a world scale')
        ->and(trans('site.underground.hero.lede'))->toContain('own article here')
        // Fieldorf runs the sabotage command before the memory frame names him again
        ->and($byKey['supply']['body'])->toContain('cichociemni')
        ->and($byKey['today']['body'])->toContain('Emil Fieldorf')
        // the articles that come next are introduced in a line and handed on, not retold
        ->and($byKey['intelligence']['body'])->toContain('Jan Karski')
        ->and($byKey['intelligence']['body'])->toContain('Enigma')
        ->and($byKey['today']['body'])->toContain('Witold Pilecki')
        ->and($byKey['end']['body'])->toContain('has its own article');

    app()->setLocale('pl');

    $polish = collect(trans('site.underground.frames'))->keyBy('key');

    expect($polish['meaning']['body'])->toContain('Delegatura Rządu na Kraj')
        ->and($polish['beginning']['body'])->toContain('Służbę Zwycięstwu Polski')
        ->and($polish['delegatura']['body'])->toContain('rodzinę Ulmów')
        ->and($polish['intelligence']['body'])->toContain('Jan Karski')
        ->and($polish['politics']['body'])->toContain('Krajowa Rada Narodowa')
        ->and($polish['today']['body'])->toContain('Witold Pilecki');

    app()->setLocale('en');
});

it('keeps the hard-won underground state facts the way the sources give them', function (): void {
    $frames = collect(trans('site.underground.frames'))->keyBy('key');
    $sources = trans('site.underground.sources.body');

    // a state, not only an army: the number of departments and what the courts did
    expect($frames['meaning']['body'])->toContain('eighteen departments')
        ->and($frames['meaning']['body'])->toContain('name of the Polish Republic')
        // the beginning, dated and attributed to the men who did it
        ->and($frames['beginning']['body'])->toContain('27 September 1939')
        ->and($frames['beginning']['body'])->toContain('Michal Tokarzewski-Karaszewicz')
        ->and($frames['beginning']['body'])->toContain('Juliusz Rommel')
        ->and($frames['beginning']['body'])->toContain('13 November 1939')
        ->and($frames['beginning']['body'])->toContain('30 June 1940')
        ->and($frames['beginning']['body'])->toContain('Stefan Rowecki')
        ->and($frames['beginning']['body'])->toContain('14 February 1942')
        ->and($frames['beginning']['body'])->toContain('350,000 sworn soldiers')
        // the delegates, named, with what happened to them
        ->and($frames['delegatura']['body'])->toContain('Cyryl Ratajski')
        ->and($frames['delegatura']['body'])->toContain('3 December 1940')
        ->and($frames['delegatura']['body'])->toContain('Jan Piekalkiewicz')
        ->and($frames['delegatura']['body'])->toContain('19 February 1943')
        ->and($frames['delegatura']['body'])->toContain('Jan Stanislaw Jankowski')
        ->and($frames['delegatura']['body'])->toContain('Wlodzimierz Les')
        ->and($frames['delegatura']['body'])->toContain('10 September 1944')
        // the schools figure is printed as a disagreement, not as a round million
        ->and($frames['schools']['body'])->toContain('one and a half million')
        ->and($frames['schools']['body'])->toContain('Grabowski')
        ->and($frames['meaning']['body'])->toContain('Waldemar Grabowski')
        ->and($frames['schools']['body'])->toContain('May 1942')
        ->and($frames['schools']['body'])->toContain('one teacher and six students')
        ->and($frames['schools']['body'])->toContain('Aleksander Kaminski')
        ->and($frames['schools']['body'])->toContain('5 November 1939')
        ->and($frames['schools']['body'])->toContain('43,000')
        // the supply numbers, including the gap between sent and collected
        ->and($frames['supply']['body'])->toContain('15 February 1941')
        ->and($frames['supply']['body'])->toContain('316')
        ->and($frames['supply']['body'])->toContain('579')
        ->and($frames['supply']['body'])->toContain('670 tons')
        ->and($frames['supply']['body'])->toContain('443 tons')
        ->and($frames['supply']['body'])->toContain('732 trains')
        ->and($frames['supply']['body'])->toContain('930 locomotives')
        // the intelligence frame, with the flight dated and placed
        ->and($frames['intelligence']['body'])->toContain('Peenemunde')
        ->and($frames['intelligence']['body'])->toContain('Blizna')
        ->and($frames['intelligence']['body'])->toContain('25 July 1944')
        ->and($frames['intelligence']['body'])->toContain('Wal-Ruda')
        ->and($frames['intelligence']['body'])->toContain('4 December 1942')
        ->and($frames['intelligence']['body'])->toContain('Zegota')
        // the politics, with both flanks named and the disputed killings stated plainly
        ->and($frames['politics']['body'])->toContain('9 January 1944')
        ->and($frames['politics']['body'])->toContain('Kazimierz Puzak')
        ->and($frames['politics']['body'])->toContain('four parties')
        ->and($frames['politics']['body'])->toContain('National Council of the Homeland')
        ->and($frames['politics']['body'])->toContain('20 September 1942')
        ->and($frames['politics']['body'])->toContain('Jews in hiding')
        // the end, with the dissolution and the trial as the sources give them
        ->and($frames['end']['body'])->toContain('20 November 1943')
        ->and($frames['end']['body'])->toContain('Tadeusz Komorowski')
        ->and($frames['end']['body'])->toContain('19 January 1945')
        ->and($frames['end']['body'])->toContain('Leopold Okulicki')
        ->and($frames['end']['body'])->toContain('Pruszkow')
        ->and($frames['end']['body'])->toContain('Ivan Serov')
        ->and($frames['end']['body'])->toContain('three acquitted')
        // memory, with the dates a visitor can check on the spot
        ->and($frames['today']['body'])->toContain('200,000')
        ->and($frames['today']['body'])->toContain('25 May 1948')
        ->and($frames['today']['body'])->toContain('24 February 1953')
        ->and($frames['today']['body'])->toContain('27 September 2012')
        ->and($frames['today']['body'])->toContain('31 July 2004')
        // the anchor exhibit carries the contested attribution nowhere near a claim
        ->and(trans('site.underground.anchor.caption'))->toContain('twenty seven entries')
        ->and(trans('site.underground.anchor.caption'))->toContain('16 April');

    // what the sources disagree about is declared rather than smoothed over
    expect($sources)->toContain('400,000')
        ->and($sources)->toContain('not printed here')
        // a print run everyone repeats that no institution stands behind
        ->and($sources)->toContain('50,000 copies')
        // the locomotive figure that circulates in the wrong order of magnitude
        ->and($sources)->toContain('6,930')
        // the intelligence percentage is named only to say it is not used
        ->and($sources)->toContain('48 per cent')
        // Zegota's funding is not flattened into a single actor
        ->and($sources)->toContain('Marcin Urynowicz')
        // both the National Armed Forces killings and the Home Army's own record are stated
        ->and($sources)->toContain('Borow')
        ->and($sources)->toContain('Wierzchowiny')
        ->and($sources)->toContain('Grzegorz Motyka')
        ->and($sources)->toContain('Joshua Zimmerman')
        ->and($sources)->toContain('is not airbrushed')
        // and the decision not to publish a map is stated too
        ->and($sources)->toContain('No map is published on this page');

    app()->setLocale('pl');

    $polish = collect(trans('site.underground.frames'))->keyBy('key');

    expect($polish['beginning']['body'])->toContain('27 września 1939')
        ->and($polish['beginning']['body'])->toContain('14 lutego 1942')
        ->and($polish['delegatura']['body'])->toContain('3 grudnia 1940')
        ->and($polish['schools']['body'])->toContain('Waldemar Grabowski')
        ->and($polish['supply']['body'])->toContain('443 tony')
        ->and($polish['intelligence']['body'])->toContain('4 grudnia 1942')
        ->and($polish['politics']['body'])->toContain('9 stycznia 1944')
        ->and($polish['end']['body'])->toContain('19 stycznia 1945')
        ->and($polish['today']['body'])->toContain('27 września 2012')
        ->and($polish['today']['body'])->toContain('31 lipca 2004')
        ->and(trans('site.underground.sources.body'))->toContain('6930')
        ->and(trans('site.underground.sources.body'))->toContain('Wierzchowinach')
        ->and(trans('site.underground.sources.body'))->toContain('nie ma mapy');

    app()->setLocale('en');
});

it('turns the first resistance topic of the war overview into a link', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // the overview topic and the article it now answers agree on the subject
    expect($order)->toContain("warTopic: 'resistance.0'");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.war.chapters.resistance.topics.0.title'))->not->toBeEmpty()
            ->and(trans('site.underground.next'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    // the deep article is reachable from the overview and links back to it
    $this->get('/history/second-world-war')->assertOk();
    $this->get('/history/underground-state')->assertOk();

    expect(file_get_contents(resource_path('js/pages/HistoryUnderground.vue')))
        ->toContain('/history/second-world-war')
        ->toContain("nextAfter('/history/underground-state')");
});

it('never calls the German camps in occupied Poland Polish camps', function (): void {
    $forbidden = [
        'en' => ['Polish camp', 'Polish death camp', 'Polish concentration camp', 'Polish extermination camp'],
        'pl' => ['polski obóz', 'polskie obozy', 'polskich obozów', 'polskim obozie', 'polskiego obozu', 'polskimi obozami'],
    ];

    foreach ($forbidden as $locale => $phrases) {
        app()->setLocale($locale);

        // the one allowed occurrence is the sentence that denies the phrase
        $copy = str_replace('never Polish camps', '', json_encode(trans('site.camps'), JSON_UNESCAPED_UNICODE));

        foreach ($phrases as $phrase) {
            expect(mb_stripos($copy, $phrase))->toBeFalse("{$locale} copy contains {$phrase}");
        }
    }

    app()->setLocale('en');

    // and the page says so out loud, in both languages
    expect(collect(trans('site.camps.frames'))->keyBy('key')['today']['body'])
        ->toContain('These were German Nazi camps in occupied Poland')
        ->toContain('never Polish camps');

    app()->setLocale('pl');

    expect(collect(trans('site.camps.frames'))->keyBy('key')['today']['body'])
        ->toContain('niemieckie nazistowskie obozy w okupowanej Polsce')
        ->toContain('Nigdy polskie');

    app()->setLocale('en');
});

it('publishes a real map on the death camps article rather than drawing one', function (): void {
    // nothing hand-drawn stands in for it, and it opens full screen
    expect(file_get_contents(resource_path('js/pages/HistoryCamps.vue')))
        ->not->toContain('Map.vue')
        ->toContain('camps-map-lg.jpg')
        ->toContain('with-caption');

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // a map is a source, so it carries its maker, its date and its language
        expect(trans('site.camps.map.credit'))->toContain('OMGUS')
            ->and(trans('site.camps.map.credit'))->toContain('1947')
            ->and(trans('site.camps.map.caption'))->toContain('1947')
            ->and(trans('site.camps.map.alt'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    // the language of the sheet is declared, because it is not the language of the page
    expect(trans('site.camps.map.caption'))->toContain('in English');

    app()->setLocale('pl');

    expect(trans('site.camps.map.caption'))->toContain('po angielsku');

    app()->setLocale('en');
});

it('shows no photograph taken by the perpetrators on the death camps article', function (): void {
    // the pictures are memorials, a document and a map, and the sources say so
    $captions = collect(trans('site.camps.frames'))->pluck('caption')->implode(' ');

    expect($captions)->not->toContain('Auschwitz Album')
        ->and(trans('site.camps.sources.body'))->toContain('are not reprinted here')
        ->and(trans('site.camps.sources.body'))->toContain('memorials, buildings, a document and a map');
});

it('renders the Witold Pilecki article as a filmstrip', function (): void {
    $this->get('/history/witold-pilecki')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryPilecki')
            ->has('translations.pilecki.frames', 9)
        );
});

it('credits every frame of the Witold Pilecki article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['pilecki']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/pilecki-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/pilecki-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/pilecki-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['pilecki']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.pilecki.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/pilecki-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/pilecki-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.pilecki.sources.body'))->toContain('Title picture')
        ->and(trans('site.pilecki.sources.body'))->toContain('Haydn Blackey')
        // the licences are carried over, attribution, share-alike and public domain alike
        ->and(collect(trans('site.pilecki.frames'))->keyBy('key')['before']['credit'])->toContain('public domain')
        ->and(collect(trans('site.pilecki.frames'))->keyBy('key')['inside']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.pilecki.frames'))->keyBy('key')['escape']['credit'])->toContain('CC BY-SA 3.0');
});

it('uses pictures on the Witold Pilecki article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/pilecki-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'pilecki-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames and the title picture
    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('names the camp registration photograph of Pilecki as an SS photograph', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $volunteer = collect(trans('site.pilecki.frames'))->keyBy('key')['volunteer'];

        // a perpetrator photograph of a prisoner is labelled as one, in both languages
        expect($volunteer['caption'])->toContain('4859')
            ->and($volunteer['credit'])->toContain('SS');
    }

    app()->setLocale('en');

    expect(collect(trans('site.pilecki.frames'))->keyBy('key')['volunteer']['caption'])
        ->toContain('made by the SS');
});

it('hangs the Witold Pilecki article off the second topic of the resistance chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the underground state, with no era band slot
    expect(strpos($order, '/history/witold-pilecki'))
        ->toBeGreaterThan(strpos($order, '/history/underground-state'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/witold-pilecki',\s+titleKey: 'pilecki.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'resistance.1',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.pilecki.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.pilecki.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/witold-pilecki')
        ->assertSee(e(trans('site.pilecki.meta.title')), escape: false)
        ->assertSee('/images/cards/pilecki.jpg', escape: false);
});

it('keeps every Witold Pilecki frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['pilecki']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Witold Pilecki article from a Russian birthplace to a cell on Rakowiecka', function (): void {
    $frames = collect(trans('site.pilecki.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['before', 'secret', 'volunteer', 'inside', 'escape', 'report', 'warsaw', 'trial', 'today'])
        // why a Pole was born in Russia at all is answered where it is first said
        ->and($byKey['before']['body'])->toContain('January Rising of 1863')
        // Wlodarkiewicz is introduced with a rank and a unit before the camp frame leans on him
        ->and($byKey['secret']['body'])->toContain('Major Jan Wlodarkiewicz')
        ->and($byKey['volunteer']['body'])->toContain('Wlodarkiewicz')
        // Serafinski is a borrowed name before he is a man met on the road
        ->and($byKey['volunteer']['body'])->toContain('Tomasz Serafinski')
        ->and($byKey['escape']['body'])->toContain('the real one')
        // the Union of Armed Struggle becomes the Home Army on the page, not off it
        ->and($byKey['secret']['body'])->toContain('Union of Armed Struggle')
        ->and($byKey['escape']['body'])->toContain('as the Union of Armed Struggle was called by then')
        // the articles on either side are handed on rather than retold
        ->and(trans('site.pilecki.hero.lede'))->toContain('another article here')
        ->and(trans('site.pilecki.hero.lede'))->toContain('Jan Karski');

    app()->setLocale('pl');

    $polish = collect(trans('site.pilecki.frames'))->keyBy('key');

    expect($polish['before']['body'])->toContain('powstanie styczniowe')
        ->and($polish['secret']['body'])->toContain('Tajna Armia Polska')
        ->and($polish['volunteer']['body'])->toContain('Tomasza Serafińskiego')
        ->and($polish['inside']['body'])->toContain('Związek Organizacji Wojskowej')
        ->and($polish['escape']['body'])->toContain('Armii Krajowej');

    app()->setLocale('en');
});

it('keeps the hard-won Witold Pilecki facts the way the sources give them', function (): void {
    $frames = collect(trans('site.pilecki.frames'))->keyBy('key');
    $sources = trans('site.pilecki.sources.body');

    // the life before the war, dated
    expect($frames['before']['body'])->toContain('13 May 1901')
        ->and($frames['before']['body'])->toContain('Olonets')
        ->and($frames['before']['body'])->toContain('Lucjan Zeligowski')
        ->and($frames['before']['body'])->toContain('Sukurcze')
        ->and($frames['before']['body'])->toContain('Maria Ostrowska')
        // September 1939 and the founding of the Secret Polish Army
        ->and($frames['secret']['body'])->toContain('19th Infantry Division')
        ->and($frames['secret']['body'])->toContain('Piotrkow Trybunalski')
        ->and($frames['secret']['body'])->toContain('9 November 1939')
        ->and($frames['secret']['body'])->toContain('Tajna Armia Polska')
        // the mission into the camp, with the dispute attributed rather than settled
        ->and($frames['volunteer']['body'])->toContain('Institute of National Remembrance')
        ->and($frames['volunteer']['body'])->toContain('19 September 1940')
        ->and($frames['volunteer']['body'])->toContain('Zoliborz')
        ->and($frames['volunteer']['body'])->toContain('4859')
        // inside: the organisation, the first report and the man who read it
        ->and($frames['inside']['body'])->toContain('cells of five')
        ->and($frames['inside']['body'])->toContain('October 1940')
        ->and($frames['inside']['body'])->toContain('Stefan Rowecki')
        ->and($frames['inside']['body'])->toContain('March 1941')
        ->and($frames['inside']['body'])->toContain('11 November 1941')
        // the escape, with both companions named
        ->and($frames['escape']['body'])->toContain('26 April 1943')
        ->and($frames['escape']['body'])->toContain('Jan Redzej')
        ->and($frames['escape']['body'])->toContain('Edward Ciesielski')
        ->and($frames['escape']['body'])->toContain('bakery')
        ->and($frames['escape']['body'])->toContain('Nowy Wisnicz')
        // the report: his own numbers, and the museum figure, side by side
        ->and($frames['report']['body'])->toContain('97,000')
        ->and($frames['report']['body'])->toContain('two million')
        ->and($frames['report']['body'])->toContain('1.1 million')
        ->and($frames['report']['body'])->toContain('counted far too high')
        // the refusal is attributed, not asserted
        ->and($frames['warsaw']['body'])->toContain('Institute of National Remembrance')
        ->and($frames['warsaw']['body'])->toContain('no chance')
        ->and($frames['warsaw']['body'])->toContain('1 August 1944')
        ->and($frames['warsaw']['body'])->toContain('August Emil Fieldorf')
        // the return and the trial, with the sentence on the right date
        ->and($frames['trial']['body'])->toContain('Murnau')
        ->and($frames['trial']['body'])->toContain('Wladyslaw Anders')
        ->and($frames['trial']['body'])->toContain('October 1945')
        ->and($frames['trial']['body'])->toContain('8 May 1947')
        ->and($frames['trial']['body'])->toContain('3 March 1948')
        ->and($frames['trial']['body'])->toContain('Jan Hryckowian')
        ->and($frames['trial']['body'])->toContain('15 March')
        ->and($frames['trial']['body'])->toContain('25 May 1948')
        ->and($frames['trial']['body'])->toContain('Piotr Smietanski')
        // the sentence fell in March and not in May, which is the trap on this subject
        ->and($frames['trial']['body'])->not->toContain('15 May')
        // today: the court that quashed it, the promotion, the missing grave
        ->and($frames['today']['body'])->toContain('1 October 1990')
        ->and($frames['today']['body'])->toContain('Supreme Military Court')
        ->and($frames['today']['body'])->toContain('5 September 2013')
        ->and($frames['today']['body'])->toContain('Rakowiecka')
        ->and($frames['today']['body'])->toContain('have not been identified')
        ->and($frames['today']['body'])->toContain('2012');

    // the camp is German, named as such where it first appears, and Birkenau is glossed
    expect($frames['volunteer']['body'])->toContain('The Germans had opened a camp')
        ->and($frames['report']['body'])->toContain('the huge extension the SS built')
        // the institute that carries his name today
        ->and($frames['today']['body'])->toContain('2017');

    // what was deliberately left out is written down, so it cannot creep back in
    expect($sources)->toContain('typhus')
        ->and($sources)->toContain('radio transmitter')
        ->and($sources)->toContain('Alwernia')
        ->and($sources)->toContain('Dangiel')
        // the court that quashed the verdict was the military one, and the date of the sentence is March
        ->and($sources)->toContain('not by the Supreme Court')
        ->and($sources)->toContain('15 March 1948, not in May')
        // no map is published, and the page says why
        ->and($sources)->toContain('No map is published on this page');
});

it('renders the Jan Karski article as a filmstrip', function (): void {
    $this->get('/history/jan-karski')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryKarski')
            ->has('translations.karski.frames', 9)
        );
});

it('credits every frame of the Jan Karski article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['karski']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/karski-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/karski-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/karski-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['karski']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.karski.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/karski-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/karski-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.karski.sources.body'))->toContain('Title picture')
        ->and(trans('site.karski.sources.body'))->toContain('Adrian Grycuk')
        // the licences are carried over, attribution, public domain and CC0 alike
        ->and(collect(trans('site.karski.frames'))->keyBy('key')['captivity']['credit'])->toContain('CC0')
        ->and(collect(trans('site.karski.frames'))->keyBy('key')['mission']['credit'])->toContain('public domain')
        ->and(collect(trans('site.karski.frames'))->keyBy('key')['slovakia']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the Jan Karski article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/karski-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'karski-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames and the title picture
    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('prints the Belzec and Izbica correction on the Jan Karski article in both languages', function (): void {
    // his own book names Belzec, the Jewish Historical Institute names Izbica, and the page says so
    $english = collect(trans('site.karski.frames'))->keyBy('key')['izbica'];

    expect($english['body'])->toContain('Story of a Secret State, the book he wrote in 1944, calls the place Belzec')
        ->and($english['body'])->toContain('Jewish Historical Institute')
        ->and($english['body'])->toContain('this is an error')
        ->and($english['body'])->toContain('Izbica')
        ->and($english['body'])->toContain('Zamosc')
        ->and($english['caption'])->toContain('Izbica')
        ->and(trans('site.karski.sources.body'))->toContain('the correction is the standard one')
        ->and(trans('site.karski.sources.body'))->toContain('still says Belzec');

    app()->setLocale('pl');

    $polish = collect(trans('site.karski.frames'))->keyBy('key')['izbica'];

    expect($polish['body'])->toContain('Bełżcem')
        ->and($polish['body'])->toContain('Żydowski Instytut Historyczny')
        ->and($polish['body'])->toContain('to błąd')
        ->and($polish['body'])->toContain('Izbica pod Zamościem')
        ->and($polish['caption'])->toContain('Izbicy')
        ->and(trans('site.karski.sources.body'))->toContain('sprostowanie jest standardowe');

    app()->setLocale('en');
});

it('hangs the Jan Karski article off the third topic of the resistance chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after Witold Pilecki, with no era band slot
    expect(strpos($order, '/history/jan-karski'))
        ->toBeGreaterThan(strpos($order, '/history/witold-pilecki'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/jan-karski',\s+titleKey: 'karski.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'resistance.2',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.karski.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.karski.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/jan-karski')
        ->assertSee(e(trans('site.karski.meta.title')), escape: false)
        ->assertSee('/images/cards/karski.jpg', escape: false);
});

it('keeps every Jan Karski frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['karski']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Jan Karski article from a saddler in Lodz to a bench in a park', function (): void {
    $frames = collect(trans('site.karski.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['before', 'captivity', 'courier', 'slovakia', 'mission', 'izbica', 'reports', 'after', 'today'])
        // the alias arrives on the page rather than being used without explanation
        ->and($byKey['before']['body'])->toContain('born Jan Kozielewski')
        ->and($byKey['courier']['body'])->toContain('took the alias Jan Karski')
        // Feiner is introduced with a party before the next frame leans on him
        ->and($byKey['mission']['body'])->toContain('Leon Feiner of the Bund')
        ->and($byKey['izbica']['body'])->toContain('Feiner had him smuggled')
        // Sikorski and his government are on stage before London is
        ->and($byKey['courier']['body'])->toContain('Wladyslaw Sikorski')
        ->and($byKey['reports']['body'])->toContain('Sikorski, by then the prime minister')
        // the two witnesses are told apart rather than blurred together
        ->and(trans('site.karski.hero.lede'))->toContain('Witold Pilecki')
        ->and(trans('site.karski.hero.lede'))->toContain('the opposite job');

    app()->setLocale('pl');

    $polish = collect(trans('site.karski.frames'))->keyBy('key');

    expect($polish['before']['body'])->toContain('Jan Kozielewski')
        ->and($polish['courier']['body'])->toContain('pseudonim Jan Karski')
        ->and($polish['mission']['body'])->toContain('Leon Feiner z Bundu')
        ->and($polish['captivity']['body'])->toContain('Armia Czerwona');

    app()->setLocale('en');
});

it('keeps the hard-won Jan Karski facts the way the sources give them', function (): void {
    $frames = collect(trans('site.karski.frames'))->keyBy('key');
    $sources = trans('site.karski.sources.body');

    // the life before the war, dated and placed
    expect($frames['before']['body'])->toContain('24 June 1914')
        ->and($frames['before']['body'])->toContain('Lodz')
        ->and($frames['before']['body'])->toContain('Stefan Kozielewski')
        ->and($frames['before']['body'])->toContain('Jan Kazimierz University in Lwow')
        ->and($frames['before']['body'])->toContain('23 August 1939')
        // the Soviet captivity, with the camp named and the officers accounted for
        ->and($frames['captivity']['body'])->toContain('17 September 1939')
        ->and($frames['captivity']['body'])->toContain('Kozielszczyzna')
        ->and($frames['captivity']['body'])->toContain('Starobelsk')
        ->and($frames['captivity']['body'])->toContain('spring of 1940')
        ->and($frames['captivity']['body'])->toContain('Oswiecim')
        // the courier years
        ->and($frames['courier']['body'])->toContain('November 1939')
        ->and($frames['courier']['body'])->toContain('Marian Kozielewski')
        ->and($frames['courier']['body'])->toContain('January 1940')
        ->and($frames['courier']['body'])->toContain('Angers')
        // Slovakia, and the price other people paid
        ->and($frames['slovakia']['body'])->toContain('14 June 1940')
        ->and($frames['slovakia']['body'])->toContain('Demjata')
        ->and($frames['slovakia']['body'])->toContain('Presov')
        ->and($frames['slovakia']['body'])->toContain('28 July 1940')
        ->and($frames['slovakia']['body'])->toContain('21 August 1941')
        ->and($frames['slovakia']['body'])->toContain('32 people')
        // the message, with the second man left unsettled on purpose
        ->and($frames['mission']['body'])->toContain('Adolf Berman')
        ->and($frames['mission']['body'])->toContain('Menachem Kirszenbaum')
        ->and($frames['mission']['body'])->toContain('Yad Vashem')
        // the ghetto dating is hedged rather than decided
        ->and($frames['izbica']['body'])->toContain('accounts differ')
        ->and($frames['izbica']['body'])->toContain('August or in October 1942')
        // London and Washington, with the December 1942 dates
        ->and($frames['reports']['body'])->toContain('November 1942')
        ->and($frames['reports']['body'])->toContain('Anthony Eden')
        ->and($frames['reports']['body'])->toContain('10 December 1942')
        ->and($frames['reports']['body'])->toContain('Edward Raczynski')
        ->and($frames['reports']['body'])->toContain('17 December')
        ->and($frames['reports']['body'])->toContain('July 1943')
        ->and($frames['reports']['body'])->toContain('Franklin Roosevelt')
        // the Frankfurter line is attributed to Karski, not printed as a transcript
        ->and($frames['reports']['body'])->toContain('Felix Frankfurter')
        ->and($frames['reports']['body'])->toContain('by his own account')
        ->and($frames['reports']['body'])->toContain('unable to believe him')
        // the book, the silence and the honours
        ->and($frames['after']['body'])->toContain('November 1944')
        ->and($frames['after']['body'])->toContain('Story of a Secret State')
        ->and($frames['after']['body'])->toContain('1952')
        ->and($frames['after']['body'])->toContain('1985')
        ->and($frames['after']['body'])->toContain('2 June 1982')
        ->and($frames['after']['body'])->toContain('1994')
        ->and($frames['after']['body'])->toContain('13 July 2000')
        // today
        ->and($frames['today']['body'])->toContain('Karol Badyna')
        ->and($frames['today']['body'])->toContain('11 June 2013')
        ->and($frames['today']['body'])->toContain('POLIN')
        ->and($frames['today']['body'])->toContain('2012')
        ->and($frames['today']['body'])->toContain('Pola Nirenska')
        ->and($frames['today']['body'])->toContain('not acted on');

    // the print run is a range between institutions, so the copy does not pick a number
    expect($frames['after']['body'])->toContain('hundreds of thousands of copies')
        ->and($sources)->toContain('360,000')
        ->and($sources)->toContain('400,000');

    // what is disputed or missing is written down, so it cannot creep back in
    expect($sources)->toContain('The birth date is a real disagreement')
        ->and($sources)->toContain('24 April')
        ->and($sources)->toContain('Wojenny Nowy Sacz')
        ->and($sources)->toContain('this page prints no day')
        // no map is published, and the page says why
        ->and($sources)->toContain('No map is published on this page')
        // what was declined for room is named rather than dropped in silence
        ->and($sources)->toContain('Szmul Zygielbojm')
        ->and($sources)->toContain('12 May 1943');
});

it('renders the Enigma article as a filmstrip', function (): void {
    $this->get('/history/enigma')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryEnigma')
            ->has('translations.enigma.frames', 9)
        );
});

it('credits every frame of the Enigma article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['enigma']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/enigma-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/enigma-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/enigma-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['enigma']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.enigma.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/enigma-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/enigma-hero-{$size}.jpg"))->toBeReadableFile();
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.enigma.sources.body'))->toContain('Title picture')
        ->and(trans('site.enigma.sources.body'))->toContain('Adrian Grycuk')
        // the licences are carried over, attribution, public domain and CC0 alike
        ->and(collect(trans('site.enigma.frames'))->keyBy('key')['machine']['credit'])->toContain('CC0')
        ->and(collect(trans('site.enigma.frames'))->keyBy('key')['break']['credit'])->toContain('public domain')
        ->and(collect(trans('site.enigma.frames'))->keyBy('key')['bureau']['credit'])->toContain('CC BY-SA 4.0');
});

it('uses pictures on the Enigma article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/enigma-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'enigma-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames and the title picture
    expect($ours)->toHaveCount(10)
        ->and($ours->unique())->toHaveCount(10)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Enigma article off the fourth topic of the resistance chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after Jan Karski, with no era band slot
    expect(strpos($order, '/history/enigma'))
        ->toBeGreaterThan(strpos($order, '/history/jan-karski'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/enigma',\\s+titleKey: 'enigma.meta.title',\\s+(//[^\n]*\n\\s+)*warTopic: 'resistance.3',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.enigma.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.enigma.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/enigma')
        ->assertSee(e(trans('site.enigma.meta.title')), escape: false)
        ->assertSee('/images/cards/enigma.jpg', escape: false);
});

it('keeps every Enigma frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['enigma']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Enigma article from a lecture room in Poznan to a monument in it', function (): void {
    $frames = collect(trans('site.enigma.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['machine', 'bureau', 'three', 'break', 'machines', 'harder', 'pyry', 'after', 'memory'])
        // the rotors and the plugboard are explained before anything is broken
        ->and($byKey['machine']['body'])->toContain('rotors')
        ->and($byKey['machine']['body'])->toContain('plugboard')
        // the Second Republic is handed on rather than assumed
        ->and($byKey['bureau']['body'])->toContain('The Second Republic')
        ->and($byKey['bureau']['body'])->toContain('its own article here')
        // the 1929 course brings the three on stage before they are named
        ->and($byKey['bureau']['body'])->toContain('cryptology course')
        ->and($byKey['three']['body'])->toContain('Three of the twenty-three')
        // the plugboard change lands on the machine the first frame described
        ->and($byKey['harder']['body'])->toContain('plugboard cables')
        // Karski is named as the article before this one
        ->and(trans('site.enigma.hero.lede'))->toContain('Jan Karski');

    app()->setLocale('pl');

    $polish = collect(trans('site.enigma.frames'))->keyBy('key');

    expect($polish['machine']['body'])->toContain('wirnikami')
        ->and($polish['bureau']['body'])->toContain('Druga Rzeczpospolita')
        ->and($polish['bureau']['body'])->toContain('Biuro Szyfrów')
        ->and($polish['bureau']['body'])->toContain('Uniwersytetu Poznańskiego')
        ->and($polish['three']['body'])->toContain('trzech z tych dwudziestu trzech');

    app()->setLocale('en');
});

it('credits both the Polish break and the work Bletchley Park did itself', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $frames = collect(trans('site.enigma.frames'))->keyBy('key');
        $sources = trans('site.enigma.sources.body');

        // the Polish side: the man, the date, the handover
        expect($frames['break']['body'])->toContain('Rejewski')
            ->and($frames['break']['body'])->toContain('1932')
            ->and($frames['pyry']['body'])->toContain('25')
            ->and($frames['pyry']['body'])->toContain('1939');

        // the British side gets its own weight on the page, not only in the sources
        expect($frames['pyry']['body'])->toContain('Bletchley Park')
            ->and($frames['pyry']['body'])->toContain('Alan Turing')
            ->and($frames['pyry']['body'])->toContain('8')
            ->and($sources)->toContain('Bombe Rebuild Trust')
            ->and($sources)->toContain('Welchman');
    }

    app()->setLocale('en');

    expect(trans('site.enigma.frames.6.body'))->toContain('naval Enigma was broken there in Hut 8')
        ->and(trans('site.enigma.sources.body'))->toContain('The Polish contribution is not the whole story')
        ->and(trans('site.enigma.sources.body'))->toContain('diagonal board')
        ->and(trans('site.enigma.sources.body'))->toContain('German submarines');

    app()->setLocale('pl');

    expect(trans('site.enigma.frames.6.body'))->toContain('Enigmę marynarki złamano w baraku numer 8')
        ->and(trans('site.enigma.sources.body'))->toContain('Polski wkład nie jest całą historią')
        ->and(trans('site.enigma.sources.body'))->toContain('tablicę przekątną');

    app()->setLocale('en');
});

it('keeps the hard-won Enigma facts the way the sources give them', function (): void {
    $frames = collect(trans('site.enigma.frames'))->keyBy('key');
    $sources = trans('site.enigma.sources.body');

    // the machine, patented and dated
    expect($frames['machine']['body'])->toContain('Arthur Scherbius')
        ->and($frames['machine']['body'])->toContain('23 February 1918')
        ->and($frames['machine']['body'])->toContain('1930')
        // the course that made the difference
        ->and($frames['bureau']['body'])->toContain('January 1929')
        ->and($frames['bureau']['body'])->toContain('twenty-three')
        ->and($frames['bureau']['body'])->toContain('Poznan University')
        // the three, born between 1905 and 1909, all Poznan graduates
        ->and($frames['three']['body'])->toContain('16 August 1905')
        ->and($frames['three']['body'])->toContain('24 July 1909')
        ->and($frames['three']['body'])->toContain('15 July 1908')
        ->and($frames['three']['body'])->toContain('Zdzislaw Krygowski')
        // the break, with the French material attributed to both men
        ->and($frames['break']['body'])->toContain('8 December 1932')
        ->and($frames['break']['body'])->toContain('Gustave Bertrand')
        ->and($frames['break']['body'])->toContain('Hans-Thilo Schmidt')
        // the phrase is attributed rather than floated
        ->and($frames['break']['body'])->toContain('Cipher A. Deavours')
        ->and($frames['break']['body'])->toContain('the theorem that won World War II')
        // the machines they built
        ->and($frames['machines']['body'])->toContain('cyclometer')
        ->and($frames['machines']['body'])->toContain('105,456')
        ->and($frames['machines']['body'])->toContain('Zygalski')
        ->and($frames['machines']['body'])->toContain('bomba kryptologiczna')
        ->and($frames['machines']['body'])->toContain('November 1938')
        ->and($frames['machines']['body'])->toContain('17,576')
        // the German changes, dated
        ->and($frames['harder']['body'])->toContain('15 December 1938')
        ->and($frames['harder']['body'])->toContain('five rotors')
        ->and($frames['harder']['body'])->toContain('sixty')
        ->and($frames['harder']['body'])->toContain('ten plugboard cables')
        // Pyry, with all four names
        ->and($frames['pyry']['body'])->toContain('25 July 1939')
        ->and($frames['pyry']['body'])->toContain('Pyry')
        ->and($frames['pyry']['body'])->toContain('Gwido Langer')
        ->and($frames['pyry']['body'])->toContain('Maksymilian Ciezki')
        ->and($frames['pyry']['body'])->toContain('Alastair Denniston')
        ->and($frames['pyry']['body'])->toContain('Alfred Dillwyn Knox')
        ->and($frames['pyry']['body'])->toContain('Britain could not read Enigma before that meeting')
        ->and($frames['pyry']['body'])->toContain('on the word of its own codebreakers')
        // the invasion is bridged rather than skipped over
        ->and($frames['after']['body'])->toContain('1 September 1939')
        ->and($frames['after']['body'])->toContain('the Soviet Union invaded too')
        // the machine is called unbreakable before it is broken
        ->and($frames['machine']['body'])->toContain('nobody could ever work through that many settings')
        // what became of them
        ->and($frames['after']['body'])->toContain('17 September')
        ->and($frames['after']['body'])->toContain('Cadix')
        ->and($frames['after']['body'])->toContain('9 January 1942')
        ->and($frames['after']['body'])->toContain('Lamoriciere')
        ->and($frames['after']['body'])->toContain('Boxmoor')
        ->and($frames['after']['body'])->toContain('1978')
        ->and($frames['after']['body'])->toContain('1946')
        // the memory fight
        ->and($frames['memory']['body'])->toContain('1974')
        ->and($frames['memory']['body'])->toContain('Frederick Winterbotham')
        ->and($frames['memory']['body'])->toContain('Gustave Bertrand')
        ->and($frames['memory']['body'])->toContain('2007')
        ->and($frames['memory']['body'])->toContain('25 September 2021')
        ->and($frames['memory']['body'])->toContain('Roll of Honour');

    // what is hedged or missing is written down, so it cannot creep back in
    expect($sources)->toContain('the conference ran on for two more days')
        ->and($sources)->toContain('26 and 27 July')
        ->and($sources)->toContain('what became of him is not printed here')
        ->and($sources)->toContain('without saying who coined it')
        // no map is published, and the page says why
        ->and($sources)->toContain('No map is published on this page')
        // what was declined for room is named rather than dropped in silence
        ->and($sources)->toContain('clock method')
        ->and($sources)->toContain('Sekret Enigmy')
        ->and($sources)->toContain('how much Ultra shortened the war');
});

it('renders the Polish forces abroad article as a filmstrip', function (): void {
    $this->get('/history/polish-forces-abroad')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryForces')
            ->has('translations.forces.frames', 9)
        );
});

it('credits every frame of the Polish forces article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['forces']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/forces-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/forces-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/forces-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['forces']['frames'])->pluck('photo')->unique())->toHaveCount(9);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.forces.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/forces-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['hero', 'map'] as $extra) {
        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/forces-{$extra}-{$size}.jpg"))->toBeReadableFile();
        }

        expect(getimagesize(public_path("images/forces-{$extra}-sm.jpg"))[0])->toBe(960)
            ->and(getimagesize(public_path("images/forces-{$extra}-lg.jpg"))[0])->toBe(1280)
            ->and(filesize(public_path("images/forces-{$extra}-lg.jpg")))->toBeLessThan(460_000);
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.forces.sources.body'))->toContain('Title picture')
        // the licences are carried over, attribution, public domain and CC0 alike
        ->and(collect(trans('site.forces.frames'))->keyBy('key')['britain']['credit'])->toContain('public domain')
        ->and(collect(trans('site.forces.frames'))->keyBy('key')['navy']['credit'])->toContain('CC BY-SA 4.0')
        ->and(collect(trans('site.forces.frames'))->keyBy('key')['arnhem']['credit'])->toContain('CC0');
});

it('uses pictures on the Polish forces article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/forces-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'forces-'))
        ->map(fn (string $file): string => md5_file($file));

    // nine frames, the title picture and the map
    expect($ours)->toHaveCount(11)
        ->and($ours->unique())->toHaveCount(11)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Polish forces article off the fifth topic of the resistance chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after Enigma, with no era band slot
    expect(strpos($order, '/history/polish-forces-abroad'))
        ->toBeGreaterThan(strpos($order, '/history/enigma'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/polish-forces-abroad',\\s+titleKey: 'forces.meta.title',\\s+(//[^\n]*\n\\s+)*warTopic: 'resistance.4',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.forces.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.forces.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/polish-forces-abroad')
        ->assertSee(e(trans('site.forces.meta.title')), escape: false)
        ->assertSee('/images/cards/forces.jpg', escape: false);
});

it('keeps every Polish forces frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['forces']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('bridges the Polish forces article from a border crossing to a war grave', function (): void {
    $frames = collect(trans('site.forces.frames'));
    $keys = $frames->pluck('key')->all();
    $byKey = $frames->keyBy('key');

    expect($keys)->toBe(['escape', 'britain', 'navy', 'anders', 'cassino', 'maczek', 'arnhem', 'berling', 'end'])
        // the army is got out of the country before anything is asked of it
        ->and($byKey['escape']['body'])->toContain('Romania or Hungary')
        ->and($byKey['escape']['body'])->toContain('France')
        // the pilots come from the men the first frame landed in Britain
        ->and($byKey['britain']['body'])->toContain('reached Britain in 1940')
        // the army in the east is handed on to the article that covers its release
        ->and($byKey['anders']['body'])->toContain('government in exile')
        // Monte Cassino is that same army, named as such
        ->and($byKey['cassino']['body'])->toContain('that army became the Polish Second Corps')
        // the western army is distinguished from it rather than assumed
        ->and($byKey['maczek']['body'])->toContain('A different Polish army')
        // Arnhem is placed in the same season as Maczek
        ->and($byKey['arnhem']['body'])->toContain('Maczek')
        // the third army is introduced from what Anders left behind
        ->and($byKey['berling']['body'])->toContain('Anders')
        // Enigma is named as the article before this one
        ->and(trans('site.forces.hero.lede'))->toContain('Enigma');

    app()->setLocale('pl');

    $polish = collect(trans('site.forces.frames'))->keyBy('key');

    expect($polish['escape']['body'])->toContain('Rumunii albo na Węgry')
        ->and($polish['anders']['body'])->toContain('rządzie na uchodźstwie')
        ->and($polish['cassino']['body'])->toContain('2 Korpusem Polskim')
        ->and($polish['berling']['body'])->toContain('Andersem');

    app()->setLocale('en');
});

it('prints both the wartime and the revised 303 Squadron figures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $britain = collect(trans('site.forces.frames'))->keyBy('key')['britain']['body'];
        $sources = trans('site.forces.sources.body');

        // the wartime claim and the post-war correction stand side by side
        expect($britain)->toContain('126')
            ->and($britain)->toContain('303')
            ->and($britain)->toContain('145')
            // both are attributed to the institution that publishes them
            ->and($britain)->toContain('Imperial War Museums')
            // the sources carry the other revised figure and refuse to pick one
            ->and($sources)->toContain('131');
    }

    app()->setLocale('en');

    expect(collect(trans('site.forces.frames'))->keyBy('key')['britain']['body'])->toContain('2,692')
        ->toContain('1,733');

    expect(trans('site.forces.sources.body'))->toContain('203.5')
        ->toContain('picks neither');

    app()->setLocale('pl');

    expect(collect(trans('site.forces.frames'))->keyBy('key')['britain']['body'])->toContain('2692')
        ->toContain('1733');

    expect(trans('site.forces.sources.body'))->toContain('203,5')
        ->toContain('nie wybiera żadnej');

    app()->setLocale('en');
});

it('covers the army raised in the east without taking a side', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $berling = collect(trans('site.forces.frames'))->keyBy('key')['berling']['body'];

        expect($berling)->toContain('Berling')
            ->toContain('1943')
            ->toContain('Lenino')
            ->toContain('510');

        // the frame reports what the men did, it does not hand out verdicts
        foreach (['traitor', 'puppet', 'zdrajc', 'marionet'] as $partisan) {
            expect(mb_strtolower($berling))->not->toContain($partisan);
        }
    }

    app()->setLocale('en');

    expect(trans('site.forces.sources.body'))->toContain('does not take a side')
        ->toContain('neither traitors nor liberators');

    app()->setLocale('pl');

    expect(trans('site.forces.sources.body'))->toContain('nie zajmuje stanowiska')
        ->toContain('ani zdrajcami, ani wyzwolicielami');

    app()->setLocale('en');
});

it('keeps the hard-won Polish forces facts the way the sources give them', function (): void {
    $frames = collect(trans('site.forces.frames'))->keyBy('key');
    $sources = trans('site.forces.sources.body');

    // the evacuation, dated and counted
    expect($frames['escape']['body'])->toContain('17 September 1939')
        ->and($frames['escape']['body'])->toContain('Edward Rydz-Smigly')
        ->and($frames['escape']['body'])->toContain('85,000')
        ->and($frames['escape']['body'])->toContain('82,261')
        ->and($frames['escape']['body'])->toContain('Zygmunt Bohusz-Szyszko')
        ->and($frames['escape']['body'])->toContain('Narvik')
        // the pilots
        ->and($frames['britain']['body'])->toContain('31 August 1940')
        ->and($frames['britain']['body'])->toContain('Ronald Kellett')
        ->and($frames['britain']['body'])->toContain('Kosciuszko')
        ->and($frames['britain']['body'])->toContain('Dowding')
        // the navy, with the documented order rather than the famous one
        ->and($frames['navy']['body'])->toContain('30 August 1939')
        ->and($frames['navy']['body'])->toContain('Jozef Unrug')
        ->and($frames['navy']['body'])->toContain('Blyskawica')
        ->and($frames['navy']['body'])->toContain('Orzel')
        ->and($frames['navy']['body'])->toContain('Jan Grudzinski')
        ->and($frames['navy']['body'])->toContain('26 May 1941')
        ->and($frames['navy']['body'])->toContain('Eugeniusz Plawski')
        ->and($frames['navy']['body'])->toContain('three salvoes for Poland')
        // the road out of the Soviet Union, with the children counted and placed
        ->and($frames['anders']['body'])->toContain('115,000')
        ->and($frames['anders']['body'])->toContain('78,500')
        ->and($frames['anders']['body'])->toContain('Isfahan')
        ->and($frames['anders']['body'])->toContain('Balachadi')
        ->and($frames['anders']['body'])->toContain('Digvijaysinhji')
        ->and($frames['anders']['body'])->toContain('Pahiatua')
        ->and($frames['anders']['body'])->toContain('Santa Rosa')
        ->and($frames['anders']['body'])->toContain('Wojtek')
        // Monte Cassino, with the cost attributed
        ->and($frames['cassino']['body'])->toContain('Wladyslaw Anders')
        ->and($frames['cassino']['body'])->toContain('11 May')
        ->and($frames['cassino']['body'])->toContain('18 May')
        ->and($frames['cassino']['body'])->toContain('923')
        ->and($frames['cassino']['body'])->toContain('2,931')
        ->and($frames['cassino']['body'])->toContain('1,072')
        ->and($frames['cassino']['body'])->toContain('Feliks Konarski')
        ->and($frames['cassino']['body'])->toContain('Alfred Schutz')
        // Maczek, Falaise, Breda and the bar
        ->and($frames['maczek']['body'])->toContain('Stanislaw Maczek')
        ->and($frames['maczek']['body'])->toContain('Falaise')
        ->and($frames['maczek']['body'])->toContain('29 October 1944')
        ->and($frames['maczek']['body'])->toContain('Breda')
        ->and($frames['maczek']['body'])->toContain('barman')
        ->and($frames['maczek']['body'])->toContain('102')
        // Arnhem, the blame and its withdrawal
        ->and($frames['arnhem']['body'])->toContain('Driel')
        ->and($frames['arnhem']['body'])->toContain('Bernard Montgomery')
        ->and($frames['arnhem']['body'])->toContain('Frederick Browning')
        ->and($frames['arnhem']['body'])->toContain('Stanislaw Sosabowski')
        ->and($frames['arnhem']['body'])->toContain('31 May 2006')
        ->and($frames['arnhem']['body'])->toContain('Military Order of William')
        ->and($frames['arnhem']['body'])->toContain('Bronze Lion')
        // the end, stated precisely
        ->and($frames['end']['body'])->toContain('May 1946')
        ->and($frames['end']['body'])->toContain('Polish Resettlement Corps')
        ->and($frames['end']['body'])->toContain('114,000')
        ->and($frames['end']['body'])->toContain('8 June 1946')
        ->and($frames['end']['body'])->toContain('no Polish unit marched')
        ->and($frames['end']['body'])->toContain('25 Polish pilots');

    // what is hedged or missing is written down, so it cannot creep back in
    expect($sources)->toContain('Wojtek')
        ->and($sources)->toContain('not printed here as a fact')
        ->and($sources)->toContain('No figure for civilian dead at Breda')
        ->and($sources)->toContain('The signal that Piorun is often said to have sent is not printed here')
        // one real map is published, and the page says why the others are not
        ->and($sources)->toContain('One map is published on this page')
        ->and($sources)->toContain('12th Geographic Company')
        // what was declined for room is named rather than dropped in silence
        ->and($sources)->toContain('Tobruk')
        ->and($sources)->toContain('Maczkow')
        ->and($sources)->toContain('Josef Frantisek');
});

it('publishes the Polish forces map with its maker, date and language', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $map = trans('site.forces.map');

        expect($map)->toHaveKeys(['alt', 'caption', 'credit'])
            ->and($map['caption'])->toContain('1944')
            ->and($map['credit'])->toContain('1945')
            ->and(mb_strlen($map['alt']))->toBeGreaterThan(120);
    }

    app()->setLocale('en');

    expect(trans('site.forces.map.caption'))->toContain('12th Geographic Company')
        ->toContain('in Polish')
        ->toContain('1 to 25,000');

    // the map is shown beside the Monte Cassino frame and opens full screen
    $component = file_get_contents(resource_path('js/pages/HistoryForces.vue'));

    expect($component)->toContain("key === 'cassino'")
        ->toContain('full="/images/forces-map-lg.jpg"');
});

it('tells the visitor what is left of the Polish forces abroad', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $visit = trans('site.forces.visit');

        expect($visit)->toHaveKeys(['label', 'body'])
            ->and($visit['body'])->toContain('Monte Cassino')
            ->and($visit['body'])->toContain('Bred')
            ->and($visit['body'])->toContain('Northolt')
            ->and($visit['body'])->toContain('Sikorski')
            ->and(mb_strlen($visit['body']))->toBeGreaterThan(200)
            ->and(mb_strlen($visit['body']))->toBeLessThan(700);
    }

    app()->setLocale('en');
});

it('renders the Warsaw Uprising article as a filmstrip', function (): void {
    $this->get('/history/warsaw-uprising-1944')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryRising')
            ->has('translations.rising.frames', 10)
        );
});

it('credits every frame of the Warsaw Uprising article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['rising']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/rising-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/rising-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/rising-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        expect(collect($copy['rising']['frames'])->pluck('photo')->unique())->toHaveCount(10);
    }

    // every large picture is the full-width file rather than a listing thumbnail
    foreach (collect(trans('site.rising.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/rising-{$photo}-lg.jpg"))[0])->toBe(1280);
    }

    foreach (['hero', 'map'] as $extra) {
        foreach (['sm', 'lg'] as $size) {
            expect(public_path("images/rising-{$extra}-{$size}.jpg"))->toBeReadableFile();
        }

        expect(getimagesize(public_path("images/rising-{$extra}-sm.jpg"))[0])->toBe(960)
            ->and(getimagesize(public_path("images/rising-{$extra}-lg.jpg"))[0])->toBe(1280)
            ->and(filesize(public_path("images/rising-{$extra}-lg.jpg")))->toBeLessThan(560_000);
    }

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.rising.sources.body'))->toContain('Title picture')
        ->toContain('Sylwester Braun')
        // the licences are carried over, attribution, public domain and CC0 alike
        ->and(collect(trans('site.rising.frames'))->keyBy('key')['whour']['credit'])->toContain('public domain')
        ->and(collect(trans('site.rising.frames'))->keyBy('key')['today']['credit'])->toContain('CC BY-SA 4.0')
        ->and(trans('site.rising.map.credit'))->toContain('CC0');
});

it('uses pictures on the Warsaw Uprising article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/rising-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'rising-'))
        ->map(fn (string $file): string => md5_file($file));

    // ten frames, the title picture and the map
    expect($ours)->toHaveCount(12)
        ->and($ours->unique())->toHaveCount(12)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Warsaw Uprising article off every topic of the uprising chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after the Polish forces abroad
    expect(strpos($order, '/history/warsaw-uprising-1944'))
        ->toBeGreaterThan(strpos($order, '/history/polish-forces-abroad'))
        ->toBeLessThan(strpos($order, '/history/occupation-and-holocaust'))
        ->and($order)->toMatch("#'/history/warsaw-uprising-1944',\\s+titleKey: 'rising.meta.title',\\s+(//[^\n]*\n\\s+)*warTopic: 'uprising.0',#");

    // the whole chapter is answered here, so every one of its five lines links
    foreach (['uprising.1', 'uprising.2', 'uprising.3', 'uprising.4'] as $topic) {
        expect($order)->toContain("'{$topic}'");
    }

    // the lookup honours the extra topics rather than only the first one
    expect($order)->toContain('alsoWarTopics?.includes(topic)')
        ->and($order)->toContain('alsoWarTopics?: string[]');

    foreach (['en', 'pl'] as $locale) {
        $chapter = (require lang_path("{$locale}/site.php"))['war']['chapters']['uprising'];

        expect($chapter['topics'])->toHaveCount(5);

        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.rising.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.rising.hero.kicker'), '·'))->toBe(2);
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/warsaw-uprising-1944')
        ->assertSee(e(trans('site.rising.meta.title')), escape: false)
        ->assertSee('/images/cards/rising.jpg', escape: false);
});

it('keeps every Warsaw Uprising frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['rising']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field} is too short")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field} is too long");
            }
        }
    }
});

it('keeps the two Warsaw risings apart on the Warsaw Uprising article', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the correction is made in the lede, before anyone can get it wrong
        expect(trans('site.rising.hero.lede'))->toContain('1943')
            ->toContain('1944')
            ->and(trans('site.rising.meta.description'))->toContain('1943');
    }

    app()->setLocale('en');

    expect(trans('site.rising.hero.lede'))->toContain('ghetto')
        ->and(trans('site.rising.sources.body'))->toContain('Warsaw Ghetto Uprising of April 1943');

    app()->setLocale('pl');

    expect(trans('site.rising.hero.lede'))->toContain('getcie')
        ->and(trans('site.rising.sources.body'))->toContain('getcie warszawskim');

    app()->setLocale('en');
});

it('bridges the Warsaw Uprising article from an order of 1943 to a siren today', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $frames = collect((require lang_path("{$locale}/site.php"))['rising']['frames'])->keyBy('key');

        expect($frames->keys()->all())->toBe([
            'why', 'whour', 'wola', 'fighting', 'civilians', 'help', 'end', 'razing', 'argument', 'today',
        ]);

        // nobody is introduced without being told who they are
        expect($frames['why']['body'])->toContain('Tadeusz Komorowski')
            ->toContain('Kazimierz Sosnkowski')
            ->and($frames['wola']['body'])->toContain('Himmler')
            ->and($frames['end']['body'])->toContain('von dem Bach')
            ->and($frames['argument']['body'])->toContain($locale === 'en' ? 'Nowak-Jezioranski' : 'Nowak-Jeziorański');
    }

    app()->setLocale('en');

    // the article takes over from the underground one and stops where rebuilding starts
    expect(trans('site.rising.frames.0.body'))->toContain('The article before this one')
        ->and(trans('site.rising.frames.7.body'))->toContain('84 per cent');
});

it('keeps the hard-won Warsaw Uprising facts the way the sources give them', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $frames = collect(trans('site.rising.frames'))->keyBy('key');
        $sources = trans('site.rising.sources.body');

        // Operation Tempest and the doubts inside the command
        expect($frames['why']['body'])->toContain($locale === 'en' ? '20 November 1943' : '20 listopada 1943')
            ->toContain($locale === 'en' ? '31 July' : '31 lipca');

        // hour W, and the arming of one soldier in ten
        expect($frames['whour']['body'])->toContain('25')
            ->toContain('55')
            ->toContain('12')
            ->and($frames['whour']['label'])->toContain('17.00');

        // Wola: the range is a range, and the units and commanders are named
        expect($frames['wola']['body'])->toContain('Dirlewanger')
            ->toContain('Reinefarth')
            ->toContain('30')
            ->toContain('65')
            ->and($frames['wola']['year'])->toContain('1944');

        // the machinery of a city under siege
        expect($frames['fighting']['body'])->toContain('Kubu')
            ->toContain('13')
            ->and($frames['civilians']['body'])->toContain('Haberbusch')
            ->and($frames['civilians']['body'])->toContain('Szczepa');

        // the help and the lack of it
        expect($frames['help']['body'])->toContain('Brindisi')
            ->toContain($locale === 'en' ? '1,300' : '1300')
            ->toContain('133')
            ->toContain('B-17')
            ->toContain('Berling');

        // the capitulation and its terms
        expect($frames['end']['body'])->toContain('Iranek-Osmecki')
            ->toContain('Dobrowolski')
            ->toContain('1929')
            ->toContain('16')
            ->toContain('150');

        // the razing, attributed the way the rebuilding article attributes it
        expect($frames['razing']['body'])->toContain('9')
            ->toContain('Brandkommando')
            ->toContain('Sprengkommando')
            ->toContain('Schmelcher')
            ->toContain('100')
            ->toContain('84');

        // the argument, with names and dates on each position
        expect($frames['argument']['body'])->toContain('Kirchmayer')
            ->toContain('1959')
            ->toContain('Ciechanowski')
            ->toContain('1971');

        // today
        expect($frames['today']['body'])->toContain('Gloria Victis')
            ->toContain($locale === 'en' ? 'Tchorek' : 'Tchorka')
            ->toContain($locale === 'en' ? 'Powazki' : 'Powązkach');

        // the arithmetic behind the three destruction figures is shown, not hidden
        expect($sources)->toContain('60')
            ->toContain('80')
            ->toContain('84')
            ->toContain('65');

        // an accusation and a number that could not be verified are declined in the open
        expect($sources)->toContain('RONA')
            ->toContain('110');
    }

    app()->setLocale('en');

    expect(trans('site.rising.frames.2.body'))->toContain('50,000 to 60,000')
        ->and(trans('site.rising.frames.6.body'))->toContain('400,000');
});

it('publishes the Warsaw Uprising map with its maker, date and language', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $map = trans('site.rising.map');

        expect($map)->toHaveKeys(['alt', 'caption', 'credit'])
            ->and($map['caption'])->toContain('1944')
            ->and($map['credit'])->toContain('1944')
            ->and(mb_strlen($map['alt']))->toBeGreaterThan(120);
    }

    app()->setLocale('en');

    expect(trans('site.rising.map.caption'))->toContain('Geographical Section')
        ->toContain('in English')
        ->toContain('1 to 50,000');

    // the map is shown beside the fighting frame and opens full screen
    $component = file_get_contents(resource_path('js/pages/HistoryRising.vue'));

    expect($component)->toContain("key === 'fighting'")
        ->toContain('full="/images/rising-map-lg.jpg"');
});

it('tells the visitor how to behave in Warsaw on the first of August', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $visit = trans('site.rising.visit');

        expect($visit)->toHaveKeys(['label', 'body'])
            ->and($visit['body'])->toContain('1')
            ->and(mb_strlen($visit['body']))->toBeGreaterThan(200)
            ->and(mb_strlen($visit['body']))->toBeLessThan(700);
    }

    app()->setLocale('en');

    expect(trans('site.rising.visit.body'))->toContain('stand still');

    app()->setLocale('pl');

    expect(trans('site.rising.visit.body'))->toContain('syreny');

    app()->setLocale('en');
});

it('names the perpetrator of the death camps as Nazi Germany in every language', function (): void {
    // the state that built and ran them, said in full where the page first names it
    $openings = [
        'en' => ['Nazi Germany built the killing', 'Nazi Germany had decided to murder the Jews of Europe', 'Through 1941 Nazi Germany murdered Jews by shooting'],
        'pl' => ['Nazistowskie Niemcy zabijały', 'Nazistowskie Niemcy postanowiły wymordować Żydów Europy', 'Przez cały 1941 rok nazistowskie Niemcy rozstrzeliwały Żydów'],
    ];

    foreach ($openings as $locale => $phrases) {
        app()->setLocale($locale);

        $copy = json_encode(trans('site.camps'), JSON_UNESCAPED_UNICODE);

        foreach ($phrases as $phrase) {
            expect($copy)->toContain($phrase);
        }
    }

    // the camps themselves carry the formula the UNESCO listing uses
    app()->setLocale('en');

    expect(trans('site.camps.meta.title'))->toContain('Nazi Germany')
        ->and(trans('site.camps.meta.description'))->toContain('German Nazi killing sites')
        ->and(collect(trans('site.camps.frames'))->keyBy('key')['today']['body'])
        ->toContain('These were German Nazi camps in occupied Poland');

    app()->setLocale('pl');

    expect(trans('site.camps.meta.title'))->toContain('nazistowskie Niemcy')
        ->and(trans('site.camps.meta.description'))->toContain('niemieckich nazistowskich miejsc zagłady')
        ->and(collect(trans('site.camps.frames'))->keyBy('key')['today']['body'])
        ->toContain('niemieckie nazistowskie obozy w okupowanej Polsce');

    app()->setLocale('en');
});

it('says Nazi Germany where the wartime copy first names the perpetrator state', function (): void {
    // a German reader has to be able to tell the Third Reich from the country they live in
    $firstMentions = [
        'en' => [
            'war.chapters.invasion.lede' => 'Nazi Germany attacks on 1 September 1939',
            'war.chapters.occupation.lede' => 'The west was annexed to Nazi Germany',
            'war.chapters.holocaust.lede' => 'Nazi Germany built its killing centres in occupied Poland',
            'sept1939.hero.lede' => 'Nazi Germany attacked on 1 September',
            'gg.hero.lede' => 'disappeared into Nazi Germany',
            'ghettos.hero.lede' => 'Nazi Germany had occupied Poland since 1939',
            'zamosc.hero.lede' => 'Nazi Germany had occupied this part of Poland',
            'occupation.hero.lede' => 'Nazi Germany built the machinery of the Holocaust',
            'katyn.hero.lede' => 'Nazi Germany and the Soviet Union invaded Poland',
        ],
        'pl' => [
            'war.chapters.invasion.lede' => 'Nazistowskie Niemcy atakują 1 września 1939',
            'war.chapters.occupation.lede' => 'Zachód wcielono do nazistowskich Niemiec',
            'war.chapters.holocaust.lede' => 'Nazistowskie Niemcy budowały ośrodki zagłady',
            'sept1939.hero.lede' => 'Nazistowskie Niemcy zaatakowały 1 września',
            'gg.meta.description' => 'nazistowskie Niemcy w 1939 roku nie wcieliły do Rzeszy',
            'ghettos.hero.lede' => 'Nazistowskie Niemcy okupowały Polskę od 1939',
            'zamosc.hero.lede' => 'Nazistowskie Niemcy okupowały tę część Polski',
            'occupation.hero.lede' => 'nazistowskie Niemcy zbudowały maszynerię Zagłady',
            'katyn.hero.lede' => 'nazistowskie Niemcy i Związek Sowiecki',
        ],
    ];

    foreach ($firstMentions as $locale => $expectations) {
        app()->setLocale($locale);

        foreach ($expectations as $key => $phrase) {
            expect(trans("site.{$key}"))->toContain($phrase);
        }

        // the article that opens on the invasion opens on the state that made it
        expect(collect(trans('site.exile.frames'))->keyBy('key')['romania']['body'])
            ->toStartWith($locale === 'en' ? 'Nazi Germany invaded Poland' : 'Nazistowskie Niemcy napadły na Polskę');
    }

    // and nothing outside 1933-1945 was swept along with it, because that would be false
    app()->setLocale('en');

    $danzig = collect(trans('site.danzig.frames'))->keyBy('key');
    $borders = collect(trans('site.borders.frames'))->keyBy('key');
    $republic = collect(trans('site.republic.frames'))->keyBy('key');

    // the Weimar Republic closing its market to Polish coal in 1925 was not Nazi
    expect($danzig['gdynia']['body'])->toContain('From June 1925 Germany shut its market')
        // nor was the German Empire that took Warsaw from Russia in 1915
        ->and($republic['paris']['body'])->toContain('In 1915 Germany took Warsaw from Russia')
        // and the states that recognised the border after the war are the modern ones
        ->and($borders['today']['body'])->toContain('West Germany in the Warsaw Treaty of 7 December 1970')
        ->and($borders['today']['body'])->toContain('a united Germany confirmed it on 14 November 1990');

    app()->setLocale('pl');

    expect(collect(trans('site.danzig.frames'))->keyBy('key')['gdynia']['body'])
        ->toContain('Od czerwca 1925 roku Niemcy zamknęły rynek')
        ->and(collect(trans('site.republic.frames'))->keyBy('key')['paris']['body'])
        ->toContain('W 1915 roku Niemcy zajęli rosyjską Warszawę')
        ->and(collect(trans('site.borders.frames'))->keyBy('key')['today']['body'])
        ->toContain('RFN w układzie warszawskim z 7 grudnia 1970 roku');

    app()->setLocale('en');
});

it('renders the Volhynia article as a filmstrip', function (): void {
    $this->get('/history/volhynia-1943')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('HistoryVolhynia')
            ->has('translations.volhynia.frames', 13)
        );
});

it('credits every frame of the Volhynia article and ships its pictures', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['volhynia']['frames'] as $frame) {
            expect($frame)->toHaveKeys(['key', 'photo', 'label', 'year', 'title', 'body', 'caption', 'credit'])
                ->and($frame['credit'])->not->toBeEmpty();

            foreach (['sm', 'lg'] as $size) {
                expect(public_path("images/volhynia-{$frame['photo']}-{$size}.jpg"))->toBeReadableFile();
            }

            // a large picture over the limit is a slow page on a phone abroad
            expect(filesize(public_path("images/volhynia-{$frame['photo']}-lg.jpg")))->toBeLessThan(460_000);

            // the small size is the small one, not a thumbnail standing in for both
            expect(getimagesize(public_path("images/volhynia-{$frame['photo']}-sm.jpg"))[0])->toBe(960);
        }

        // the closing frame reuses the title picture, so thirteen frames run on
        // thirteen photograph slots and fourteen files counting the map
        expect(collect($copy['volhynia']['frames'])->pluck('photo')->unique())->toHaveCount(13);
    }

    // every large picture is the full-width file, except the one press photograph
    // of a Volhynian town that exists nowhere wider
    foreach (collect(trans('site.volhynia.frames'))->pluck('photo') as $photo) {
        expect(getimagesize(public_path("images/volhynia-{$photo}-lg.jpg"))[0])
            ->toBe($photo === 'torczyn' ? 1234 : 1280);
    }

    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/volhynia-hero-{$size}.jpg"))->toBeReadableFile();
        expect(public_path("images/volhynia-map-{$size}.jpg"))->toBeReadableFile();
    }

    $frames = collect(trans('site.volhynia.frames'))->keyBy('key');

    // the title picture has no caption of its own, so the sources name it
    expect(trans('site.volhynia.sources.body'))->toContain('Title picture')
        ->and(trans('site.volhynia.sources.body'))->toContain('Skwer Wolynski')
        // the licences are carried over, attribution, share-alike and public domain alike
        ->and($frames['oun']['credit'])->toContain('public domain')
        ->and($frames['july']['credit'])->toContain('CC0')
        ->and($frames['bandera']['credit'])->toContain('CC BY-SA 4.0')
        ->and($frames['today']['credit'])->toContain('CC BY 3.0 PL');
});

it('uses pictures on the Volhynia article that no other page already shows', function (): void {
    $ours = collect(glob(public_path('images/volhynia-*-lg.jpg')))->map(fn (string $file): string => md5_file($file));
    $others = collect(glob(public_path('images/*-lg.jpg')))
        ->reject(fn (string $file): bool => str_starts_with(basename($file), 'volhynia-'))
        ->map(fn (string $file): string => md5_file($file));

    // thirteen photographs, one of which is also the title picture, and the map
    expect($ours)->toHaveCount(14)
        ->and($ours->unique())->toHaveCount(14)
        ->and($ours->intersect($others))->toBeEmpty();
});

it('hangs the Volhynia article off the fifth topic of the occupation chapter', function (): void {
    $order = file_get_contents(resource_path('js/history-articles.ts'));

    // it sits in the reading order right after Zamojszczyzna, with no era band slot
    expect(strpos($order, '/history/volhynia-1943'))
        ->toBeGreaterThan(strpos($order, '/history/zamosc-expulsions'))
        ->toBeLessThan(strpos($order, '/history/the-ghettos'))
        ->and($order)->toMatch("#'/history/volhynia-1943',\s+titleKey: 'volhynia.meta.title',\s+(//[^\n]*\n\s+)*warTopic: 'occupation.4',#");

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // the kicker says where the article sits, in the shape the whole series uses
        expect(trans('site.volhynia.hero.kicker'))->toContain('·')
            ->and(mb_substr_count(trans('site.volhynia.hero.kicker'), '·'))->toBe(2)
            // and the overview topic it answers exists in both languages
            ->and(trans('site.war.chapters.occupation.topics.4.title'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    // search engines and link previews read the title and the card from the server
    $this->get('/history/volhynia-1943')
        ->assertSee(e(trans('site.volhynia.meta.title')), escape: false)
        ->assertSee('/images/cards/volhynia.jpg', escape: false);
});

it('keeps every Volhynia frame inside the layout budgets in every language', function (): void {
    $budgets = ['label' => [10, 22], 'title' => [25, 56], 'body' => [300, 690], 'caption' => [34, 86]];

    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");

        foreach ($copy['volhynia']['frames'] as $frame) {
            foreach ($budgets as $field => [$min, $max]) {
                expect(mb_strlen($frame[$field]))
                    ->toBeGreaterThanOrEqual($min, "{$locale} {$frame['key']}.{$field}")
                    ->toBeLessThanOrEqual($max, "{$locale} {$frame['key']}.{$field}");
            }
        }
    }
});

it('keeps the Volhynia article precise about who ordered what', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);
        $frames = collect(trans('site.volhynia.frames'))->keyBy('key');
        $english = $locale === 'en';

        // Bandera was in German hands in 1943, and the page says so and still answers
        // the question the name raises, which is the trap this subject sets
        expect($frames['bandera']['body'])->toContain('1943')
            ->and($frames['bandera']['body'])->toContain($english ? 'Savur' : 'Sawur')
            ->and($frames['bandera']['body'])->toContain($english ? 'Shukhevych' : 'Szuchewycz')
            ->and($frames['bandera']['body'])->toContain('banderowcy')
            // the orders in Volhynia came from the UPA commander there, named in full
            ->and($frames['upa']['body'])->toContain($english ? 'Kliachkivskyi' : 'Klaczkiwski')
            // and the German custody is established earlier, with its date and its place
            ->and($frames['occupations']['body'])->toContain('1944')
            ->and($frames['occupations']['body'])->toContain('Sachsenhausen');

        // the first crime and the day the page is named for keep their figures
        expect($frames['upa']['body'])->toContain('155')
            ->and($frames['july']['body'])->toContain('97')
            ->and($frames['july']['body'])->toContain('99')
            ->and($frames['july']['body'])->toContain($english ? '2,020' : '2020')
            ->and($frames['july']['body'])->toContain('67');

        // the Ukrainians who saved their neighbours are not an afterthought
        expect($frames['defence']['body'])->toContain($english ? '1,300' : '1300')
            ->and($frames['defence']['body'])->toContain($english ? '2,500' : '2500')
            ->and($frames['defence']['body'])->toContain($english ? 'Niedzielko' : 'Niedziełki');

        // and the Polish reprisals are named, dated and counted three ways
        expect($frames['revenge']['body'])->toContain('Sahry')
            ->and($frames['revenge']['body'])->toContain('234')
            ->and($frames['revenge']['body'])->toContain('365')
            ->and($frames['revenge']['body'])->toContain($english ? 'Pawlokoma' : 'Pawłokomie');
    }

    app()->setLocale('en');
});

it('gives the Volhynia casualty figures as two sets and says whose they are', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);
        $numbers = collect(trans('site.volhynia.frames'))->keyBy('key')['numbers']['body'];
        $english = $locale === 'en';

        // the Polish estimate, broken down by territory, and the Ukrainian one,
        // which differs in both directions
        foreach (['40', '60', '30', '10', '12', '17', '24'] as $figure) {
            expect($numbers)->toContain($figure);
        }

        expect($numbers)->toContain($english ? 'Ukrainian historians' : 'Historiografia ukrai')
            ->and($numbers)->toContain($english ? 'estimate' : 'szacunk');
    }

    app()->setLocale('en');
});

it('tells the same scale of the Volhynia crime everywhere on the site', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);
        $english = $locale === 'en';

        $vistula = collect(trans('site.borders.frames'))->keyBy('key')['vistula']['body'];
        $tile = collect(trans('site.everyday.careful.items'))
            ->first(fn (array $item): bool => str_contains($item['v'], $english ? 'Ukraine' : 'Ukrainy'))['v'];

        // one event, one scale, one territory, and the estimate attributed to somebody
        foreach ([$vistula, $tile] as $passage) {
            expect($passage)->toContain($english ? 'Eastern Galicia' : 'Galicji Wschodniej')
                ->and($passage)->toContain($english ? 'hundred thousand' : 'stu tysi')
                ->and($passage)->toContain($english ? 'Polish estimates' : 'polskich szacunk');
        }

        // and the parliament is never made to carry a number it did not publish
        $sentence = collect(preg_split('/(?<=\.)\s+/u', trans('site.everyday.sources.body')))
            ->first(fn (string $line): bool => str_contains($line, '2016'));

        expect($sentence)->not->toBeNull()
            ->and($sentence)->not->toContain($english ? 'hundred thousand' : 'stu tysi');
    }

    app()->setLocale('en');
});

it('sends the reader from the Ukraine tile to the Volhynia article', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");
        $tile = collect($copy['everyday']['careful']['items'])
            ->first(fn (array $item): bool => isset($item['link']));

        // the label is copy and lives in the translation file, the address does not
        expect($tile)->toHaveKey('link')
            ->and($tile['link'])->not->toBeEmpty()
            ->and($tile['link'])->not->toContain('<');
    }

    $page = file_get_contents(resource_path('js/pages/Everyday.vue'));

    expect($page)->toContain('v-if="warning.link"')
        ->toContain('href="/history/volhynia-1943"')
        ->toContain('{{ warning.link }}');
});

it('carries a real map on the Volhynia article, captioned like a source', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        // a published map made by a cartographer, with its maker, its date and its scale
        expect(trans('site.volhynia.map.caption'))->toContain('1928')
            ->and(trans('site.volhynia.map.caption'))->toContain('1:300')
            ->and(trans('site.volhynia.map.credit'))->toContain('Wojskowy Instytut Geograficzny')
            ->and(trans('site.volhynia.map.alt'))->not->toBeEmpty();
    }

    app()->setLocale('en');

    // and the panel opens full screen, because nobody can read a map at panel size
    $source = file_get_contents(resource_path('js/pages/HistoryVolhynia.vue'));

    expect($source)->toContain("hasMap = (key: string): boolean => key === 'july'")
        ->toContain('full="/images/volhynia-map-lg.jpg"');
});

it('says what the Volhynia killings were, from an institutional source and no further', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);
        $frames = collect(trans('site.volhynia.frames'))->keyBy('key');
        $english = $locale === 'en';

        // the frame between the massacre and the defence explains why this is
        // remembered differently from an execution, which is the question a
        // foreign reader cannot answer on their own
        expect($frames)->toHaveKey('neighbours');

        $body = $frames['neighbours']['body'];

        // what was done, attributed to the institute and to the inventory it works from
        expect($body)->toContain('Siemaszko')
            ->and($body)->toContain($english ? 'Institute of National Remembrance' : 'Instytut Pamięci Narodowej')
            ->and($body)->toContain($english ? 'farm tools' : 'narzędzi gospodarskich')
            // and who did it, which is the half that makes the memory last
            ->and($body)->toContain($english ? 'next village' : 'sąsiedniej wsi')
            ->and($body)->toContain('580');

        // it sits between the day itself and the self-defence, so the page moves
        // from what happened to what it was to what people did about it
        $keys = collect(trans('site.volhynia.frames'))->pluck('key')->values()->all();

        expect(array_search('neighbours', $keys, true))->toBe(array_search('july', $keys, true) + 1)
            ->and(array_search('defence', $keys, true))->toBe(array_search('neighbours', $keys, true) + 1);

        // and nothing on the page is a witness account nobody can check
        foreach (trans('site.volhynia.frames') as $frame) {
            expect($frame['body'])->not->toContain('"')
                ->and($frame['body'])->not->toContain('„');
        }
    }

    app()->setLocale('en');
});

it('prints a fetched figure as live and falls back to the dated one', function (): void {
    Cache::forget('poland.statistics');

    Http::fake([
        'bdl.stat.gov.pl/*' => Http::response([
            'results' => [['values' => [['year' => '2024', 'val' => 5.1], ['year' => '2025', 'val' => 5.7]]]],
        ]),
        'api.nbp.pl/*' => Http::response([], 500),
    ]);

    $live = collect(app(Statistics::class)->all());

    expect($live)->toHaveKey('unemployment')
        ->and($live['unemployment']['value'])->toBe('5,7%')
        ->and($live['unemployment']['year'])->toBe('2025');

    // the reader is told which half of the board was fetched and which was typed
    $this->get('/poland-today')->assertOk()->assertInertia(
        fn (AssertableInertia $page) => $page->where('figures', function ($figures): bool {
            $keyed = collect($figures)->map(fn ($row) => (array) $row)->keyBy('key');

            return ($keyed['unemployment']['live'] ?? false) === true
                && ($keyed['unemployment']['year'] ?? null) === '2025'
                && ! array_key_exists('live', $keyed['petrol'])
                && $keyed['petrol']['value'] === config('poland.figures.3.value');
        })
    );

    Cache::forget('poland.statistics');
});

it('keeps the dated value when the statistics office cannot be reached', function (): void {
    Cache::forget('poland.statistics');

    Http::fake(['bdl.stat.gov.pl/*' => Http::response([], 503)]);

    expect(app(Statistics::class)->all())->toBe([]);

    Cache::forget('poland.statistics');
});

it('renders the page for people tracing a Polish family', function (): void {
    $this->get('/polish-roots')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Roots')
            ->where('locale', 'en')
            ->has('translations.roots.records.items', 9)
            ->has('translations.roots.words.items', 14)
        );

    $this->get('/polish-roots?lang=pl')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('locale', 'pl'));
});

it('sends every card that closes the home page to a page that exists', function (): void {
    $source = file_get_contents(resource_path('js/pages/Welcome.vue'));

    // all three reading paths now land somewhere, and the row still falls back
    // to a plain anchor for any card added before its page is written
    expect($source)->toContain("{ key: 'practical', href: '/polish-roots' }")
        ->toContain("{ key: 'timeline', href: '/start-here' }")
        ->toContain("{ key: 'everyday', href: '/everyday-life' }")
        ->toContain(':is="card.href ? Link : \'a\'"');

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.home.closing.cards.practical.title'))->not->toBeEmpty();
    }

    app()->setLocale('en');
});

it('kills the Ellis Island name change myth instead of repeating it', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);
        $english = $locale === 'en';

        $myth = trans('site.roots.name.myth_body_1').' '.trans('site.roots.name.myth_body_2');

        // the myth is named, the mechanism is given, and both historians are named
        expect(trans('site.roots.name.myth_title'))->toContain('Ellis Island')
            ->and($myth)->toContain('Marian L. Smith')
            ->and($myth)->toContain('Philip Sutton')
            ->and($myth)->toContain($english ? 'drawn up in Europe' : 'powstawała w Europie')
            ->and($myth)->toContain('1906');

        // and the page says where the name really changed
        expect($myth)->toContain($english ? 'in America' : 'w Ameryce');
    }

    app()->setLocale('en');
});

it('gives the language of a record by partition, with the dates the sources give', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $partitions = collect(trans('site.roots.language.items'))->pluck('v')->implode(' ');

        // the four regimes and the year each one starts
        foreach (['1808', '1825', '1868', '1784', '1789', '1874', '1876', '1918', '1946'] as $year) {
            expect($partitions)->toContain($year);
        }

        // the reader is told which empire is which rather than left with a label
        expect($partitions)->toContain('Standesamt')
            ->and($partitions)->toContain($locale === 'en' ? 'Joseph II' : 'Józefa II');
    }

    app()->setLocale('en');
});

it('prints the record vocabulary in all three clerical languages', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        foreach (trans('site.roots.words.items') as $word) {
            expect($word)->toHaveKeys(['en', 'la', 'de', 'ru']);

            foreach (['en', 'la', 'de', 'ru'] as $column) {
                expect($word[$column])->not->toBeEmpty();
            }
        }

        // the Cyrillic is real Cyrillic, and it is glossed for a reader who
        // cannot sound the letters out
        $son = collect(trans('site.roots.words.items'))->firstWhere('la', 'filius');

        expect($son['ru'])->toContain('сын')
            ->and($son['ru'])->toContain('syn');
    }

    app()->setLocale('en');
});

it('names every archive it sends the reader to and writes the address as text', function (): void {
    foreach (['en', 'pl'] as $locale) {
        $copy = require lang_path("{$locale}/site.php");
        $places = $copy['roots']['records']['items'];

        $addresses = [];

        foreach ($places as $place) {
            expect($place)->toHaveKeys(['k', 'v', 'web'])
                ->and($place['k'])->not->toBeEmpty()
                ->and($place['v'])->not->toBeEmpty();

            if ($place['web'] === null) {
                continue;
            }

            // an address in the copy is a bare host, never markup and never a scheme
            expect($place['web'])->not->toContain('<')
                ->and($place['web'])->not->toContain('http');

            $addresses[] = $place['web'];
        }

        foreach (['szukajwarchiwach.gov.pl', 'geneteka.genealodzy.pl', 'jri-poland.org'] as $expected) {
            expect($addresses)->toContain($expected);
        }
    }

    // the scheme is added by the component, so no copy ever holds a link
    expect(file_get_contents(resource_path('js/pages/Roots.vue')))
        ->toContain('`https://${place.web}`');
});

it('refuses to promise a passport and says it is not legal advice', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);
        $english = $locale === 'en';

        $citizenship = trans('site.roots.citizenship.body_1').' '
            .trans('site.roots.citizenship.body_2').' '
            .collect(trans('site.roots.citizenship.items'))
                ->map(fn (array $item): string => $item['k'].' '.$item['v'])
                ->implode(' ');

        // the dates a case actually turns on
        foreach (['1920', '1951', '1962', '2007', '2019'] as $year) {
            expect($citizenship)->toContain($year);
        }

        expect($citizenship)->toContain('Karta Polaka')
            ->and($citizenship)->toContain($english ? 'is not citizenship' : 'nie jest obywatelstwem');

        // and the warning is unmissable
        expect(trans('site.roots.citizenship.disclaimer'))
            ->toContain($english ? 'not legal advice' : 'nie jest poradą prawną');
    }

    app()->setLocale('en');
});

it('links the roots page to the article about the borders instead of retelling it', function (): void {
    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.roots.borders.link'))->not->toBeEmpty()
            ->and(trans('site.roots.borders.link'))->not->toContain('<');
    }

    app()->setLocale('en');

    expect(file_get_contents(resource_path('js/pages/Roots.vue')))
        ->toContain('href="/history/the-borders-moved"');
});

it('ships and credits the picture the roots page shows', function (): void {
    foreach (['sm', 'lg'] as $size) {
        expect(public_path("images/roots-hero-{$size}.jpg"))->toBeReadableFile();
    }

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        expect(trans('site.roots.hero.photo_alt'))->not->toBeEmpty()
            ->and(trans('site.roots.hero.photo_credit'))->toContain('Kapitel')
            ->and(trans('site.roots.hero.photo_credit'))->toContain('CC BY-SA 4.0')
            ->and(trans('site.roots.sources.body'))->toContain('Kapitel');
    }

    app()->setLocale('en');

    $entry = PageSeo::entry('Roots');

    expect($entry['group'])->toBe('roots')
        ->and($entry['image'])->toBe('/images/roots-hero-lg.jpg');
});

it('prints a working address beside every app the site names', function (): void {
    $counts = [];

    foreach (['en', 'pl'] as $locale) {
        app()->setLocale($locale);

        $groups = trans('site.useful.groups');
        $addresses = [];

        expect(trans('site.useful.title'))->not->toBeEmpty()
            ->and($groups)->toBeArray()
            ->and($groups)->not->toBeEmpty();

        foreach ($groups as $block) {
            expect($block['title'])->not->toBeEmpty();

            foreach ($block['items'] as $item) {
                expect($item)->toHaveKeys(['k', 'v', 'web'])
                    ->and($item['k'])->not->toBeEmpty()
                    ->and($item['v'])->not->toBeEmpty()
                    // the component builds the href, so the copy carries a bare domain
                    ->and($item['web'])->toMatch('/^[a-z0-9.\-]+\.[a-z]{2,}(\/[a-z0-9.\-\/]*)?$/');

                $addresses[] = $item['web'];
            }
        }

        // the reader gets each address once, not the same app in two blocks
        expect($addresses)->toBe(array_values(array_unique($addresses)));

        $counts[$locale] = $addresses;
    }

    app()->setLocale('en');

    // the list is the same list in both languages, only the words around it change
    expect($counts['pl'])->toBe($counts['en']);

    foreach (['Everyday', 'LifeNow'] as $page) {
        expect(file_get_contents(resource_path("js/pages/{$page}.vue")))
            ->toContain('<UsefulLinks');
    }
});
