<?php

namespace App\Http\Middleware;

use App\Models\DownloadLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class LogDownload
{
    /**
     * Pass-through — zero overhead on the request path.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * After the file response is sent to the client, record the download
     * in the background via defer() so the user is never blocked by the DB write.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($response->getStatusCode() !== 200) {
            return;
        }

        /** @var string $filename */
        $filename = $request->route('filename');

        defer(fn () => DownloadLog::create([
            'filename'   => $filename,
            'ip'         => $request->ip(),
            'user_agent' => Str::limit($request->userAgent(), 509),
            'referer'    => Str::limit($request->headers->get('referer'), 509),
        ]));
    }
}
