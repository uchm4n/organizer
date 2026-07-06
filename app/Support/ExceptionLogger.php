<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Centralized exception logging.
 *
 * Slim one-line-per-error goal: avoids the default multi-line stack trace
 * noise. Stack traces remain available at dev-time via Pail / Telescope; they
 * are never written to disk from here.
 *
 * Routing by exception tier is in bootstrap/app.php; this class is the
 * single writer so the format / channel / context stays consistent.
 */
final class ExceptionLogger
{
    private const array CLIENT_NOISE = [
        AuthenticationException::class,
        ModelNotFoundException::class,
        NotFoundHttpException::class,
        HttpResponseException::class,
    ];

    public static function serverError(Throwable $e): void
    {
        self::write($e, 'error', self::httpStatus($e));
    }

    public static function clientWarning(Throwable $e, int $status): void
    {
        self::write($e, 'warning', $status);
    }

    public static function clientInfo(Throwable $e, int $status): void
    {
        self::write($e, 'info', $status);
    }

    public static function isClientNoise(Throwable $e): bool
    {
        if ($e instanceof ThrottleRequestsException) {
            return false;
        }

        return self::matchesAny($e, self::CLIENT_NOISE);
    }

    public static function httpStatus(Throwable $e): int
    {
        if ($e instanceof ValidationException) {
            return 422;
        }

        if ($e instanceof AuthenticationException) {
            return 401;
        }

        if ($e instanceof AuthorizationException) {
            return 403;
        }

        if ($e instanceof ModelNotFoundException) {
            return 404;
        }

        if ($e instanceof ThrottleRequestsException) {
            return 429;
        }

        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        return 500;
    }

    private static function write(Throwable $e, string $level, int $status): void
    {
        $context = self::context($e, $status);

        $message = sprintf(
            '%d %s %s "%s" at %s:%d',
            $status,
            self::title($status),
            $e::class,
            self::cleanMessage($e->getMessage()),
            self::relativePath($e->getFile()),
            $e->getLine(),
        );

        Log::$level($message, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private static function context(Throwable $e, int $status): array
    {
        $context = [
            'status' => $status,
            'route' => optional(request()->route())->getName() ?? request()->path(),
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'ip' => request()->ip(),
            'trace_id' => self::traceId(),
        ];

        $user = Auth::user();
        if ($user) {
            $context['user'] = $user->getAuthIdentifier();
        }

        return $context;
    }

    private static function traceId(): ?string
    {
        return app()->bound('app.trace_id') ? (string) app('app.trace_id') : null;
    }

    private static function cleanMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return 'no message';
        }

        return preg_replace('/\s+/', ' ', $message) ?? $message;
    }

    private static function relativePath(string $path): string
    {
        $base = base_path();

        return str_starts_with($path, $base) ? substr($path, strlen($base) + 1) : $path;
    }

    private static function title(int $status): string
    {
        return Problem::titleForStatus($status);
    }

    private static function matchesAny(Throwable $e, array $classes): bool
    {
        return array_any($classes, fn ($class) => $e instanceof $class);
    }
}
