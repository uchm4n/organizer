<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Support\Problem;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict a route to users holding one of the supplied roles.
 *
 * Attached via the `role` alias, e.g. `->middleware('role:admin')` or
 * `->middleware('role:admin,user')`. Laravel forwards the alias arguments
 * as variadic parameters to `handle()`.
 *
 * Unauthenticated requests short-circuit with a 401 Problem+Json (the
 * downstream `auth:sanctum` middleware usually catches this first, but
 * `EnsureRole` is defensive when attached beside or ahead of other auth
 * stacks). Authenticated users whose role is not allowed receive 403.
 */
final class EnsureRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$allowed  Role values permitted to pass through.
     */
    public function handle(Request $request, Closure $next, string ...$allowed): Response
    {
        $user = $request->user();

        if ($user === null) {
            return Problem::response(
                status: 401,
                title: 'Unauthorized',
                detail: 'Authentication is required to access this resource.',
            );
        }

        $permitted = array_filter(array_map(
            static fn (string $value): ?Role => Role::tryFrom(trim($value)),
            $allowed,
        ));

        if (! in_array($user->role, $permitted, true)) {
            return Problem::response(
                status: 403,
                title: 'Forbidden',
                detail: 'You do not have permission to access this resource.',
            );
        }

        return $next($request);
    }
}
