<?php

namespace App\Data\Api;

use App\Models\Workspace;
use DateTimeInterface;
use Spatie\LaravelData\Data;

final class WorkspaceData extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $name,
        public ?array $settings,
        public ?DateTimeInterface $created_at,
        public ?DateTimeInterface $updated_at,
    ) {}

    public static function fromModel(Workspace $workspace): self
    {
        return new self(
            id: $workspace->getKey(),
            user_id: $workspace->user_id,
            name: $workspace->name,
            settings: $workspace->settings,
            created_at: $workspace->created_at,
            updated_at: $workspace->updated_at,
        );
    }
}
