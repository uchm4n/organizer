<?php

namespace App\Http\Middleware;

use App\Support\Problem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireApiVersion
{
    /**
     * Reject requests whose resolved API version does not match the version
     * this route was declared for. Used to gate version-specific endpoints
     * (e.g. `/users` only exists in v2).
     *
     * @param  Closure(Request): (Response)  $next
     * @param  int|string  $version  The version this route requires.
     */
    public function handle(Request $request, Closure $next, mixed $version): Response
    {
        $resolved = $request->attributes->get('api.version');

        if ((int) $version !== $resolved) {
            return Problem::response(
                status: 426,
                title: 'Upgrade Required',
                detail: sprintf(
                    'This endpoint requires API version %s. You requested version %s.',
                    $version,
                    $resolved,
                ),
                extra: ['required_version' => (int) $version, 'requested_version' => $resolved],
            );
        }

        return $next($request);
    }
}
