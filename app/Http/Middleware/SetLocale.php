<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the request locale from the URL, the session or the browser.
 *
 * A new language is enabled purely by adding lang/<locale>/, so neither this
 * middleware nor any frontend code has to change to support it.
 */
class SetLocale
{
    /**
     * The query string parameter used to switch language.
     */
    private const QUERY_KEY = 'lang';

    /**
     * The session key that remembers the visitor's choice.
     */
    private const SESSION_KEY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $available = self::availableLocales();

        $locale = $this->requestedLocale($request, $available)
            ?? $this->sessionLocale($request, $available)
            ?? $this->browserLocale($request, $available)
            ?? config('app.locale');

        app()->setLocale($locale);

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $locale);
        }

        return $next($request);
    }

    /**
     * Every locale that has a translation directory.
     *
     * @return list<string>
     */
    public static function availableLocales(): array
    {
        $locales = collect(File::directories(lang_path()))
            ->map(fn (string $path): string => basename($path))
            ->values()
            ->all();

        return $locales === [] ? [config('app.locale')] : $locales;
    }

    /**
     * @param  list<string>  $available
     */
    private function requestedLocale(Request $request, array $available): ?string
    {
        $locale = $request->query(self::QUERY_KEY);

        return is_string($locale) && in_array($locale, $available, true) ? $locale : null;
    }

    /**
     * @param  list<string>  $available
     */
    private function sessionLocale(Request $request, array $available): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        $locale = $request->session()->get(self::SESSION_KEY);

        return is_string($locale) && in_array($locale, $available, true) ? $locale : null;
    }

    /**
     * @param  list<string>  $available
     */
    private function browserLocale(Request $request, array $available): ?string
    {
        return $request->getPreferredLanguage($available);
    }
}
