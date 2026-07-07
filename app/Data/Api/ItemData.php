<?php

namespace App\Data\Api;

use App\Enums\ItemType;
use App\Models\Item;
use DateTimeInterface;
use Spatie\LaravelData\Data;

final class ItemData extends Data
{
    public function __construct(
        public int $id,
        public int $workspace_id,
        public ?int $parent_id,
        public ItemType $type,
        public string $title,
        public ?array $data,
        public int $sort_order,
        public ?DateTimeInterface $created_at,
        public ?DateTimeInterface $updated_at,
        public ?DateTimeInterface $deleted_at,
    ) {}

    public static function fromModel(Item $item): self
    {
        return new self(
            id: $item->getKey(),
            workspace_id: $item->workspace_id,
            parent_id: $item->parent_id,
            type: $item->type,
            title: $item->title,
            data: $item->data,
            sort_order: $item->sort_order,
            created_at: $item->created_at,
            updated_at: $item->updated_at,
            deleted_at: $item->deleted_at,
        );
    }
}
