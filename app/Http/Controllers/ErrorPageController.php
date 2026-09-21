<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Middleware\HandleInertiaRequests;
use App\Support\PageSeo;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The page a reader gets instead of a framework error.
 *
 * Most people arrive on this site from a search engine, and a search engine
 * keeps an address long after the page behind it moves, so a dead link is an
 * ordinary event here rather than an exception. It is answered in the reader's
 * own language, with the way back on the page, and with the real status code
 * so that a crawler still learns what happened.
 */
final class ErrorPageController extends Controller
{
    /** The fallback route: an address this application has no page for. */
    public function __invoke(Request $request): Response
    {
        return self::render($request, 404);
    }

    /**
     * The same page for a status raised anywhere else.
     *
     * An exception can be thrown before the middleware has shared anything,
     * so the shared props are built here rather than assumed. Without that the
     * page renders its translation keys instead of its copy.
     */
    public static function render(Request $request, int $status): Response
    {
        $inertia = app(HandleInertiaRequests::class);
        $inertia->share($request);

        return inertia(PageSeo::ERROR_COMPONENT, ['status' => $status])
            ->with($inertia->share($request))
            ->toResponse($request)
            ->setStatusCode($status);
    }
}
