<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The part of the translation file one page actually needs.
 *
 * Every page used to receive the whole of lang/<locale>/site.php, close to a
 * megabyte of text for seventy-odd pages, written into the HTML of each one.
 * A reader opening one article on a phone downloaded every other article too.
 * A page now gets its own group, the groups the shared components read from
 * (the masthead, the footer, the picture viewer, the address list, the error
 * page), and the title and description of every page, which is what a "read
 * next" link or a list of articles shows.
 */
final class PageCopy
{
    /**
     * Groups read by components that appear on many pages.
     *
     * home: the masthead title and tagline in the header. history: the labels
     * of the picture viewer and the article navigation. places: the city list
     * and the way back on every place page. The rest are small.
     */
    public const SHARED = ['a11y', 'brand', 'nav', 'footer', 'support', 'home', 'history', 'places', 'useful', 'notfound'];

    /**
     * @return array<string, mixed>
     */
    public static function for(Request $request): array
    {
        $site = trans('site');

        if (! is_array($site)) {
            return [];
        }

        $copy = [];

        foreach ($site as $group => $value) {
            if (is_array($value) && isset($value['meta'])) {
                $copy[$group] = ['meta' => $value['meta']];
            }
        }

        foreach ([...self::SHARED, self::pageGroup($request)] as $group) {
            if ($group !== null && array_key_exists($group, $site)) {
                $copy[$group] = $site[$group];
            }
        }

        return $copy;
    }

    /**
     * The translation group of the page being answered, from its route.
     *
     * An address with no route of its own is the error page.
     */
    public static function pageGroup(Request $request): ?string
    {
        $name = $request->route()?->getName();

        if ($name === null) {
            return PageSeo::entry(PageSeo::ERROR_COMPONENT)['group'];
        }

        return PageSeo::entryForRoute($name)['group'] ?? null;
    }
}
