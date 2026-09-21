<?php

declare(strict_types=1);

use App\Support\PageCopy;
use App\Support\PageSeo;
use Inertia\Testing\AssertableInertia;

/**
 * Each page receives only the copy it reads.
 *
 * Sharing the whole translation file wrote close to a megabyte of text into
 * every page. Sharing less means a page that reads a group it was not given
 * shows raw keys instead of words, so the reading is checked here against
 * the source of the page and of every component it imports.
 */

/**
 * The translation groups a Vue file reads by name, and those of the components
 * it imports, followed all the way down.
 *
 * @param  array<string, true>  $seen
 * @return list<string>
 */
function groupsReadBy(string $file, array &$seen = []): array
{
    if (isset($seen[$file]) || ! is_file($file)) {
        return [];
    }

    $seen[$file] = true;
    $source = (string) file_get_contents($file);

    preg_match_all('/(?:\bt|\$t|group(?:<[^>]*>)?)\(\s*[\'"`]([a-z_0-9]+)\./', $source, $literal);

    $groups = $literal[1];

    preg_match_all("#from '@/components/([A-Za-z]+)\\.vue'#", $source, $imports);

    foreach ($imports[1] as $component) {
        $groups = [...$groups, ...groupsReadBy(resource_path("js/components/{$component}.vue"), $seen)];
    }

    return array_values(array_unique($groups));
}

it('gives every page each group its source and its components read', function (): void {
    $missing = [];

    foreach (PageSeo::pages() as $component => $entry) {
        $given = [...PageCopy::SHARED, $entry['group']];

        foreach (groupsReadBy(resource_path("js/pages/{$component}.vue")) as $group) {
            if (! in_array($group, $given, true)) {
                $missing[] = "{$component} reads {$group}";
            }
        }
    }

    expect($missing)->toBe([]);
});

it('sends an article its own copy in full and only the titles of the others', function (): void {
    $this->get('/history/katyn')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('translations.katyn.frames')
            // the next article link and the article lists show titles only
            ->has('translations.pact.meta.title')
            ->missing('translations.pact.frames')
            ->has('translations.history.enlarge')
            ->has('translations.home.masthead.title')
        );
});

it('keeps the copy on a page to a fraction of the whole file', function (): void {
    $whole = strlen((string) json_encode(trans('site')));

    $response = $this->get('/food');

    $response->assertOk();

    $shared = strlen((string) json_encode($response->viewData('page')['props']['translations']));

    // the largest page group on the site, and still under a tenth of the file
    expect($shared)->toBeLessThan($whole / 10);
});

it('gives the error page its copy on an address with no route', function (): void {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('translations.notfound.doors', 4)
            ->has('translations.footer')
        );
});
