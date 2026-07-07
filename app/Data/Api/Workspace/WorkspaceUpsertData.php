<?php

namespace App\Data\Api\Workspace;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Request payload for own-workspace upsert (`GET`/`POST`/`PATCH /workspace`).
 */
final class WorkspaceUpsertData extends Data
{
    public function __construct(
        #[Sometimes]
        #[StringType]
        #[Max(120)]
        public ?string $name = null,

        #[Nullable]
        public ?array $settings = null,
    ) {}
}
