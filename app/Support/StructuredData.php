<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The JSON-LD graph a page publishes.
 *
 * Search engines read this to work out what the site is, what a page is about
 * and where it sits. Nothing in here is ever invented: every field repeats
 * something the page itself says, because structured data that contradicts the
 * page is worse than no structured data at all. There are no publication dates
 * and no ratings, because the site does not have them.
 */
final class StructuredData
{
    /**
     * The whole graph for one page, ready to be encoded.
     *
     * @param  array<string, mixed>  $head  the array PageSeo::head() returned
     * @return array<string, mixed>
     */
    public static function graph(array $head): array
    {
        $site = url('/');
        $canonical = (string) $head['canonical'];
        $locale = (string) $head['locale'];
        $name = (string) config('app.name');

        $organisation = $site.'#organisation';
        $website = $site.'#website';
        $page = $canonical.'#webpage';
        $picture = $canonical.'#primaryimage';

        $nodes = [
            [
                '@type' => 'Organization',
                '@id' => $organisation,
                'name' => $name,
                'url' => $site,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/apple-touch-icon.png'),
                    'width' => 180,
                    'height' => 180,
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $website,
                'url' => $site,
                'name' => $name,
                'inLanguage' => $locale,
                'publisher' => ['@id' => $organisation],
            ],
            [
                '@type' => 'ImageObject',
                '@id' => $picture,
                'url' => (string) $head['image'],
                'contentUrl' => (string) $head['image'],
                'width' => (int) $head['imageWidth'],
                'height' => (int) $head['imageHeight'],
            ],
            array_filter([
                '@type' => self::pageType((string) $head['kind']),
                '@id' => $page,
                'url' => $canonical,
                'name' => (string) $head['title'],
                'description' => (string) $head['description'],
                'inLanguage' => $locale,
                'isPartOf' => ['@id' => $website],
                'primaryImageOfPage' => ['@id' => $picture],
                'breadcrumb' => count($head['breadcrumbs']) > 1
                    ? ['@id' => $canonical.'#breadcrumb']
                    : null,
            ]),
        ];

        if (count($head['breadcrumbs']) > 1) {
            $nodes[] = self::breadcrumbs($head['breadcrumbs'], $canonical.'#breadcrumb');
        }

        if ($head['kind'] === 'article') {
            $nodes[] = [
                '@type' => 'Article',
                '@id' => $canonical.'#article',
                'headline' => (string) $head['title'],
                'description' => (string) $head['description'],
                'image' => ['@id' => $picture],
                'inLanguage' => $locale,
                'isPartOf' => ['@id' => $page],
                'mainEntityOfPage' => ['@id' => $page],
                'author' => ['@id' => $organisation],
                'publisher' => ['@id' => $organisation],
            ];
        }

        if ($head['kind'] === 'place') {
            $nodes[] = array_filter([
                '@type' => 'TouristDestination',
                '@id' => $canonical.'#destination',
                'name' => (string) $head['title'],
                'description' => (string) $head['description'],
                'image' => ['@id' => $picture],
                'url' => $canonical,
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressCountry' => 'PL',
                ],
                'geo' => $head['geo'] === null ? null : [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $head['geo'][0],
                    'longitude' => $head['geo'][1],
                ],
            ]);
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $nodes,
        ];
    }

    /**
     * The graph as a string safe to drop inside a script tag.
     *
     * @param  array<string, mixed>  $head  the array PageSeo::head() returned
     */
    public static function json(array $head): string
    {
        return (string) json_encode(
            self::graph($head),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );
    }

    /**
     * The trail, numbered from the home page down.
     *
     * @param  array<int, array{name: string, url: string}>  $trail
     * @return array<string, mixed>
     */
    private static function breadcrumbs(array $trail, string $id): array
    {
        $items = [];

        foreach (array_values($trail) as $position => $step) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $step['name'],
                'item' => $step['url'],
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $id,
            'itemListElement' => $items,
        ];
    }

    /** What kind of page schema.org would call this one. */
    private static function pageType(string $kind): string
    {
        return match ($kind) {
            'hub' => 'CollectionPage',
            'article' => 'WebPage',
            'place' => 'WebPage',
            default => 'WebPage',
        };
    }
}
