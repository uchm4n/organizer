<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;

/**
 * Taps a Monolog logger to render one slim, single-line entry per record.
 *
 * Default Laravel logging emits multi-line stack traces that are noisy to scan
 * and painful to pipe into structured log aggregators. This formatter drops
 * the trace and uses a fixed, predictable shape so log lines stay scannable:
 *
 *   [2026-07-06 12:34:56] api.ERROR: 500 RuntimeException "boom" at app/X.php:42 | route=... user=42 method=GET url=/x ip=127.0.0.1 trace_id=4f3a2b
 *
 * Stack traces remain available at dev-time via Pail / Telescope — they are
 * never written to disk by this formatter.
 */
final class SlimLineFormatter
{
    private const string FORMAT = "[%datetime%] %channel%.%level_name%: %message% %context%\n";

    public function __invoke(Logger $logger): void
    {
        $formatter = new LineFormatter(self::FORMAT, 'Y-m-d H:i:s')
            ->setBasePath(base_path())
            ->includeStacktraces(false)
            ->ignoreEmptyContextAndExtra(true);

        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }
}
