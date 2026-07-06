<?php

use App\Http\Middleware\ApiVersion;
use App\Http\Middleware\AssignTraceId;
use App\Http\Middleware\HttpSunset;
use App\Http\Middleware\RequireApiVersion;
use App\Support\ExceptionLogger;
use App\Support\Problem;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
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
            'version' => RequireApiVersion::class,
        ]);

        // Every API request gets a trace_id and declares its version via the
        // X-API-Version header (defaulting to the current major). Doing this
        // in the `api` group rather than per-route ensures both run before
        // any authentication middleware: a bad version is a 400 not a 401,
        // and the trace_id is available even to error log entries produced
        // by auth failures.
        $middleware->api(prepend: [
            ApiVersion::class,
            AssignTraceId::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Suppress duplicate log entries when the same exception is reported
        // more than once (e.g. via `report()` from a listener or job).
        $exceptions->dontReportDuplicates();

        // Laravel's exception handler silently skips a list of "internal"
        // exception types from ever being reported (ValidationException,
        // AuthorizationException, AuthenticationException,
        // ModelNotFoundException, etc.). We want our tiered logger to see
        // the abuse-signal types (403, 422), so we opt them back in. The
        // tiers we keep silent (401, 404) are silently dropped in our
        // report callback before any log line is written.
        $exceptions->stopIgnoring([
            AuthorizationException::class,
            // ValidationException::class,
        ]);

        // ---------------- Exception logging ----------------
        // Routing by exception tier. The render callbacks below still handle
        // the HTTP response shape — these report callbacks decide whether
        // the error is logged, and at what level, via ExceptionLogger which
        // writes a single slim line per error (no stack trace dumped to disk).
        //
        // Logging tiers:
        //   - server errors (5xx + non-HTTP Throwables) -> error, always
        //   - client abuse signals (403, 422 validation) -> warning, always
        //   - client noise (401, 404) -> silent by default (Laravel already
        //     ignores NotFoundHttpException; we extend the same silence to
        //     AuthenticationException / ModelNotFoundException).
        $exceptions->report(function (Throwable $e) {
            if (ExceptionLogger::isClientNoise($e)) {
                return false; // silent — never logged
            }

            $status = ExceptionLogger::httpStatus($e);

            if ($status >= 500) {
                ExceptionLogger::serverError($e);

                return false; // we've already written; don't double-log
            }

            // 403 Forbidden, 429 and 422 Unprocessable Entity are potential API
            // misuse / brute-force / probing signals — log at warning.
            if (in_array($status, [403, 422, 429], true)) {
                ExceptionLogger::clientWarning($e, $status);

                return false;
            }

            return null; // fall through to Laravel's default logging
        });

        // ---------------- Exception rendering ----------------
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

        $exceptions->render(function (ThrottleRequestsException $e) {
            $headers = $e->getHeaders();
            $retryAfter = $headers['Retry-After'] ?? null;

            $response = Problem::response(
                status: 429,
                title: 'Too Many Requests',
                detail: 'You have exceeded the rate limit for this endpoint. Please wait before retrying.',
                extra: [
                    'retry_after' => $retryAfter !== null ? (int) $retryAfter : null,
                    'trace_id' => app()->bound('app.trace_id') ? app('app.trace_id') : null,
                ],
            );

            // Re-attach the rate-limit headers (X-RateLimit-Limit/Remaining/Reset and Retry-After).
            // They live on the exception; returning a fresh JsonResponse would otherwise drop them,
            // and consumers need them on the 429 just as much as on success responses for backoff logic.
            foreach ($headers as $key => $value) {
                $response->headers->set($key, (string) $value);
            }

            return $response;
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
                        'trace_id' => app()->bound('app.trace_id') ? app('app.trace_id') : null,
                    ],
                );
            }

            return Problem::response(
                status: $status,
                title: $title,
                detail: $status === 500
                    ? 'An unexpected error occurred. Please try again later.'
                    : $title,
                extra: app()->bound('app.trace_id') ? ['trace_id' => app('app.trace_id')] : [],
            );
        });
    })->create();
