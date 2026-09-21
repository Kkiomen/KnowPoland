<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Response;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * The sitemap builds itself from the router, so a new page is listed the
 * moment its route exists. Every address is published once per language,
 * with the alternates search engines need for a site that switches language
 * on a query parameter.
 */
final class SitemapController
{
    /** Routes that exist but are not pages a reader should land on. */
    private const EXCLUDED = ['sitemap.xml', 'up'];

    public function __invoke(): Response
    {
        $default = config('app.fallback_locale', 'en');
        $locales = SetLocale::availableLocales();

        $xml = $this->paths()
            ->map(fn (string $path): string => $this->entriesFor($path, $locales, $default))
            ->implode('');

        return response(
            '<?xml version="1.0" encoding="UTF-8"?>'."\n".
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '.
            'xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n".$xml.'</urlset>'."\n",
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    /**
     * Every public page, as a path.
     *
     * @return Collection<int, string>
     */
    private function paths(): Collection
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => in_array('GET', $route->methods(), true))
            // only pages this application defines: named, static, not internal
            ->filter(fn (RoutingRoute $route): bool => $route->getName() !== null)
            ->reject(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), '_'))
            ->reject(fn (RoutingRoute $route): bool => str_contains($route->uri(), '{'))
            ->reject(fn (RoutingRoute $route): bool => in_array($route->uri(), self::EXCLUDED, true))
            ->map(fn (RoutingRoute $route): string => '/'.ltrim($route->uri(), '/'))
            ->unique()
            ->values();
    }

    /**
     * One entry per language, each listing all the others as alternates.
     *
     * @param  array<int, string>  $locales
     */
    private function entriesFor(string $path, array $locales, string $default): string
    {
        $address = fn (string $locale): string => url($path).($locale === $default ? '' : '?lang='.$locale);

        $alternates = collect($locales)
            ->map(fn (string $locale): string => sprintf(
                '        <xhtml:link rel="alternate" hreflang="%s" href="%s"/>'."\n",
                $locale,
                e($address($locale)),
            ))
            ->implode('');

        return collect($locales)
            ->map(fn (string $locale): string => sprintf(
                "    <url>\n        <loc>%s</loc>\n%s    </url>\n",
                e($address($locale)),
                $alternates,
            ))
            ->implode('');
    }
}
