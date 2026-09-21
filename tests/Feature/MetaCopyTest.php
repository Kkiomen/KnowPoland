<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use App\Support\PageSeo;

/**
 * The copy a search result is made of.
 *
 * A title that is too long is cut off in the middle of a word, a description
 * outside the range is either padded by Google with page text or truncated,
 * and a missing key renders as the key itself. None of that is visible while
 * reading the site, so it is held here instead.
 */
const TITLE_LIMIT = 50;

const DESCRIPTION_MINIMUM = 110;

const DESCRIPTION_LIMIT = 158;

/** @return array<int, array{string, string}> Every group, in every locale. */
function metaCases(): array
{
    $cases = [];

    foreach (SetLocale::availableLocales() as $locale) {
        foreach (PageSeo::pages() as $entry) {
            $cases[] = [$locale, $entry['group']];
        }
    }

    return $cases;
}

/** The copy of one group in one locale. */
function metaCopy(string $locale, string $group, string $key): string
{
    $line = trans("site.{$group}.meta.{$key}", [], $locale);

    return is_string($line) ? $line : '';
}

it('gives every page a title and a description in every language', function (): void {
    foreach (metaCases() as [$locale, $group]) {
        foreach (['title', 'description'] as $key) {
            $line = metaCopy($locale, $group, $key);

            expect($line)
                ->not->toBe("site.{$group}.meta.{$key}", "{$locale}: {$group}.meta.{$key} is missing")
                ->not->toBe('', "{$locale}: {$group}.meta.{$key} is empty");
        }
    }
});

it('keeps every title short enough to survive a search result', function (): void {
    foreach (metaCases() as [$locale, $group]) {
        $title = metaCopy($locale, $group, 'title');

        expect(mb_strlen($title))->toBeLessThanOrEqual(
            TITLE_LIMIT,
            "{$locale}: {$group} title is ".mb_strlen($title)." characters: {$title}",
        );
    }
});

it('keeps every description inside the range a search result shows', function (): void {
    foreach (metaCases() as [$locale, $group]) {
        $description = metaCopy($locale, $group, 'description');
        $length = mb_strlen($description);

        expect($length)
            ->toBeGreaterThanOrEqual(DESCRIPTION_MINIMUM, "{$locale}: {$group} description is only {$length} characters")
            ->toBeLessThanOrEqual(DESCRIPTION_LIMIT, "{$locale}: {$group} description is {$length} characters");
    }
});

it('never repeats a title or a description across pages', function (): void {
    foreach (SetLocale::availableLocales() as $locale) {
        foreach (['title', 'description'] as $key) {
            $seen = [];

            foreach (PageSeo::pages() as $entry) {
                $line = metaCopy($locale, $entry['group'], $key);

                expect($seen)->not->toHaveKey($line, "{$locale}: two pages share the {$key} \"{$line}\"");

                $seen[$line] = $entry['group'];
            }
        }
    }
});

it('keeps the punctuation the house style allows', function (): void {
    foreach (metaCases() as [$locale, $group]) {
        foreach (['title', 'description'] as $key) {
            $line = metaCopy($locale, $group, $key);

            expect($line)
                ->not->toContain("\u{2013}", "{$locale}: {$group}.{$key} uses an en dash")
                ->not->toContain("\u{2014}", "{$locale}: {$group}.{$key} uses an em dash")
                ->not->toContain(';', "{$locale}: {$group}.{$key} uses a semicolon");
        }
    }
});
