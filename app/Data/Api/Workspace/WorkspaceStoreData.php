<?php

namespace App\Data\Api\Workspace;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Request payload for `POST /workspaces` (admin-only).
 */
final class WorkspaceStoreData extends Data
{
    public function __construct(
        #[Required]
        #[IntegerType]
        #[Exists('users', 'id')]
        public int $user_id,

        #[Required]
        #[StringType]
        #[Max(120)]
        public string $name,

        #[Nullable]
        public ?array $settings = null,
    ) {}
}
