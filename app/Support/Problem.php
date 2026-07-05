<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;

final class Problem
{
    /**
     * Build an RFC 9457 (Problem Details for HTTP APIs) JSON response.
     *
     * @param  array<string, mixed>  $extra  Extension members merged into the body (e.g. per-field `errors`).
     */
    public static function response(
        int $status,
        string $title,
        string $detail,
        array $extra = [],
    ): JsonResponse {
        return response()->json(
            array_merge([
                // 'type' => 'https://httpstatuses.com/'.$status,
                'title' => $title,
                'status' => $status,
                'detail' => $detail,
            ], $extra),
            $status,
            [
                'Content-Type' => 'application/problem+json',
            ],
        );
    }

    /**
     * Map an HTTP status code to a stable, human-readable problem title.
     */
    public static function titleForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            413 => 'Content Too Large',
            415 => 'Unsupported Media Type',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => 'Error',
        };
    }
}
