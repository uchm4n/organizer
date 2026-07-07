<?php

namespace App\Data\Api\Item;

use App\Enums\ItemType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Request payload for `POST /items`.
 */
final class ItemStoreData extends Data
{
    public function __construct(
        #[Required]
        #[IntegerType]
        #[Exists('workspaces', 'id')]
        public int $workspace_id,

        #[Nullable]
        #[IntegerType]
        #[Exists('items', 'id')]
        public ?int $parent_id,

        #[Required]
        #[Enum(ItemType::class)]
        public ItemType $type,

        #[Required]
        #[StringType]
        #[Max(255)]
        public string $title,

        #[Nullable]
        public ?array $data = null,

        #[IntegerType]
        public int $sort_order = 0,
    ) {}
}
