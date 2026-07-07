<?php

namespace App\Data\Api\Item;

use App\Enums\ItemType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

/**
 * Request payload for `PATCH /items/{item}`.
 */
final class ItemUpdateData extends Data
{
    public function __construct(
        #[Sometimes]
        #[Nullable]
        #[IntegerType]
        #[Exists('items', 'id')]
        public ?int $parent_id = null,

        #[Sometimes]
        #[Enum(ItemType::class)]
        public ?ItemType $type = null,

        #[Sometimes]
        #[StringType]
        #[Max(255)]
        public ?string $title = null,

        #[Nullable]
        public ?array $data = null,

        #[Sometimes]
        #[IntegerType]
        public ?int $sort_order = null,
    ) {}
}
