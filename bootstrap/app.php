<?php

use App\Http\Controllers\ErrorPageController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /**
         * A wrong address is answered by the site, not by the framework.
         *
         * Readers arrive from search engines, which hold on to addresses long
         * after they change, so a dead link is a normal event here rather than
         * an exception. It gets a page in the reader's own language, with the
         * way back on it, and it keeps its real status code so a crawler still
         * learns that the address is gone.
         */
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();

            if ($request->expectsJson() || ! in_array($status, [404, 419, 500], true)) {
                return $response;
            }

            return ErrorPageController::render($request, $status);
        });
    })->create();
