<?php

use App\Http\Middleware\CustomerAuthenticate;
use App\Http\Middleware\NoIndexAdmin;
use App\Http\Middleware\RequestId;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'noindex.admin' => NoIndexAdmin::class,
            'customer.auth' => CustomerAuthenticate::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        $middleware->web(append: [
            SecurityHeaders::class,
            RequestId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->renderable(function (Throwable $e, Request $request) {
            if (! app()->hasBeenBootstrapped()) {
                return null;
            }

            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            if (in_array($statusCode, [404, 419, 429, 500, 503])) {
                if (config('app.debug')) {
                    return null;
                }

                return response()->view("errors.{$statusCode}", [], $statusCode);
            }

            return null;
        });
    })->create();
