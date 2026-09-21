<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One address per page on the live site: https, and the host from APP_URL.
 *
 * Search engines treat http://, https://, www. and the bare domain as four
 * different sites, and every page here is already published twice, once per
 * language. So in production any other form of an address is sent once, with
 * a permanent redirect, to the https address on the canonical host, and every
 * secure answer tells the browser to use https from then on.
 *
 * It lives in the application rather than only in the web server so that it
 * holds on Apache, nginx or anything else the site ends up on. Outside
 * production it does nothing, so a local server keeps working on plain http.
 */
class CanonicalHost
{
    /** A year, which is what browsers and the HSTS preload list expect. */
    private const HSTS_SECONDS = 31536000;

    /** Paths a load balancer checks over plain http, which must not bounce. */
    private const EXEMPT = ['up'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() || $request->is(...self::EXEMPT)) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'), PHP_URL_HOST);

        $wrongHost = is_string($canonical) && strcasecmp($request->getHost(), $canonical) !== 0;

        if (! $request->secure() || $wrongHost) {
            $host = is_string($canonical) ? $canonical : $request->getHost();

            return redirect()->to('https://'.$host.$request->getRequestUri(), 301);
        }

        $response = $next($request);

        // only over https, and without includeSubDomains, so that it pins this
        // one domain and nothing a later subdomain might need
        $response->headers->set('Strict-Transport-Security', 'max-age='.self::HSTS_SECONDS);

        return $response;
    }
}
