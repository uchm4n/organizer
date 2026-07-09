<?php

namespace App\Models;

use App\Traits\ClearsResponseCache;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Item> $items
 */
#[Fillable(['name', 'settings'])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use ClearsResponseCache, HasFactory;

    /**
     * Get the user that owns this workspace (1:1).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all items in this workspace.
     *
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    /**
     * Workspace mutations also invalidate item responses: items live under
     * /workspaces/{workspace}/items and a deleted workspace cascades to its
     * items in the DB, so cached item responses must die together with the
     * workspace's own cached responses.
     *
     * @return list<string>
     */
    protected function responseCacheTagsFor(string $event): array
    {
        return ['workspaces', 'items'];
    }
}
