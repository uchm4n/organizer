<?php

namespace App\Http\Middleware;

use App\Support\Problem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiVersion
{
    /**
     * Versions this application is willing to serve.
     *
     * Add new entries here as they are released; existing clients requesting
     * an unsupported version receive a 400 Problem+Json response.
     */
    private const array SUPPORTED = [1];

    /**
     * Resolve the requested API version from the `X-API-Version` header,
     * defaulting to the most recent major version when the header is absent.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requested = $request->header('X-API-Version');

        if ($requested === null) {
            $version = $this->defaultVersion();
        } else {
            $version = filter_var($requested, FILTER_VALIDATE_INT);
            if ($version === false || ! in_array($version, self::SUPPORTED, true)) {
                return Problem::response(
                    status: 400,
                    title: 'Bad Request',
                    detail: sprintf(
                        'Unsupported API version "%s". Supported versions: %s.',
                        $requested,
                        implode(', ', self::SUPPORTED),
                    ),
                    extra: ['supported' => self::SUPPORTED],
                );
            }
        }

        $request->attributes->set('api.version', $version);

        return $next($request);
    }

    private function defaultVersion(): int
    {
        return self::SUPPORTED[array_key_last(self::SUPPORTED)];
    }
}
