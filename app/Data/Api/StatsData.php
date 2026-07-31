<?php

namespace App\Data\Api;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

final class StatsData extends Data
{
    public function __construct(
        public string $filename,
        public int $download_count,
        public int $unique_ips,
        public ?DateTimeInterface $first_downloaded_at,
        public ?DateTimeInterface $last_downloaded_at,
        public ?string $last_referer,
    ) {}

    /**
     * Create from a DB::table('download_stats') row (stdClass).
     */
    public static function fromRow(object $row): self
    {
        return new self(
            filename: $row->filename,
            download_count: (int) $row->download_count,
            unique_ips: (int) $row->unique_ips,
            first_downloaded_at: $row->first_downloaded_at ? Carbon::parse($row->first_downloaded_at) : null,
            last_downloaded_at: $row->last_downloaded_at ? Carbon::parse($row->last_downloaded_at) : null,
            last_referer: $row->last_referer,
        );
    }
}
