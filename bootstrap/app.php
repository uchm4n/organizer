<?php

use App\Http\Middleware\HttpSunset;
use App\Support\Problem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'sunset' => HttpSunset::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // registration order matters; Laravel uses the first matching callback.

        $exceptions->render(function (ValidationException $e) {
            return Problem::response(
                status: 422,
                title: 'Unprocessable Entity',
                detail: 'The request data did not pass validation.',
                extra: ['errors' => $e->errors()],
            );
        });

        $exceptions->render(function (AuthenticationException $e) {
            return Problem::response(
                status: 401,
                title: 'Unauthorized',
                detail: 'Authentication is required to access this resource.',
            );
        });

        $exceptions->render(function (AuthorizationException $e) {
            return Problem::response(
                status: 403,
                title: 'Forbidden',
                detail: $e->getMessage() ?: 'You do not have permission to access this resource.',
            );
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return Problem::response(
                status: 404,
                title: 'Not Found',
                detail: 'The requested resource does not exist.',
            );
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return Problem::response(
                status: 404,
                title: 'Not Found',
                detail: 'The requested endpoint does not exist.',
            );
        });

        $exceptions->render(function (HttpException $e) {
            $status = $e->getStatusCode();
            $title = Problem::titleForStatus($status);

            return Problem::response(
                status: $status,
                title: $title,
                detail: $e->getMessage() ?: $title,
            );
        });

        $exceptions->render(function (Throwable $e) {
            $status = $e instanceof HttpException ? $e->getStatusCode() : 500;
            $title = Problem::titleForStatus($status);

            // Never return HTML for errors. In local/dev, expose diagnostic
            // extension members so debugging remains useful; in production
            // emit only a generic message so internal details never leak.
            if (! app()->environment('production')) {
                return Problem::response(
                    status: $status,
                    title: $title,
                    detail: $e->getMessage() ?: $title,
                    extra: [
                        'exception' => $e::class,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                );
            }

            return Problem::response(
                status: $status,
                title: $title,
                detail: $status === 500
                    ? 'An unexpected error occurred. Please try again later.'
                    : $title,
            );
        });
    })->create();
