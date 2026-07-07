<?php

namespace App\Data\Api\Workspace;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Request payload for `PATCH /workspaces/{workspace}` and `PATCH /workspace`.
 */
final class WorkspaceUpdateData extends Data
{
    public function __construct(
        #[Sometimes]
        #[StringType]
        #[Max(120)]
        public ?string $name,

        #[Nullable]
        public ?array $settings = null,
    ) {}
}
