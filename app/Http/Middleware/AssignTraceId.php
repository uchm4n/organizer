<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assigns each request a stable, per-request correlation ID and exposes it:
 *  - to loggers via the `app.trace_id` container binding (consumed by ExceptionLogger)
 *  - to clients via the `X-Trace-Id` response header so support can correlate
 *    a reported client error with a single server-side log line.
 *
 * The ID is short (8 hex chars) for human-friendliness; collision risk on a
 * single server is negligible. If you fan out across many workers you may
 * want a longer ID, but the value is opaque and decoupled from logging shape.
 */
final class AssignTraceId
{
    private const string HEADER = 'X-Trace-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->header(self::HEADER) ?: bin2hex(random_bytes(4));

        app()->instance('app.trace_id', $traceId);

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set(self::HEADER, $traceId);

        return $response;
    }
}
