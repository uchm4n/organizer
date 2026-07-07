<?php

namespace App\Models;

use App\Enums\ItemType;
use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int|null $parent_id
 * @property ItemType $type
 * @property string $title
 * @property array<string, mixed>|null $data
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Workspace $workspace
 * @property-read Item|null $parent
 * @property-read Collection<int, Item> $children
 */
#[Fillable(['workspace_id', 'parent_id', 'type', 'title', 'data', 'sort_order'])]
class Item extends Model
{
    /** @use HasFactory<ItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the workspace that owns this item.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the parent item (NULL for roots).
     *
     * @return BelongsTo<Item, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'parent_id');
    }

    /**
     * Get the children of this item.
     *
     * @return HasMany<Item, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Item::class, 'parent_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ItemType::class,
            'data' => 'array',
        ];
    }
}
