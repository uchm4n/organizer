<?php

namespace App\Http\Middleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class HttpSunset
{
    public function handle(
        Request $request,
        Closure $next,
        string $deprecationDate,
        string $sunsetDate,
    ): Response {
        $deprecationAt = CarbonImmutable::createFromFormat('!Y-m-d', trim($deprecationDate), 'UTC');
        $sunsetAt = CarbonImmutable::createFromFormat('!Y-m-d', trim($sunsetDate), 'UTC')->endOfDay();
        $now = CarbonImmutable::now('UTC');

        if ($now->greaterThan($sunsetAt)) {
            return response()->noContent(); // or abort(410)
        }

        $response = $next($request);

        if ($now->greaterThanOrEqualTo($deprecationAt)) {
            $response->headers->set('Deprecation', $deprecationAt->toRfc7231String());
        }

        $response->headers->set('Sunset', $sunsetAt->toRfc7231String());
        // $response->headers->set('Link', '</docs/migration/v1-to-v2>; rel="successor-version"');

        return $response;
    }
}
