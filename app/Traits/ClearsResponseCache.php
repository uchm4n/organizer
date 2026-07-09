<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Flush tagged response-cache entries on Eloquent lifecycle events.
 *
 * Each model that uses this trait declares which response-cache tags describe
 * its responses by overriding {@see self::responseCacheTagsFor()}. The trait
 * then wires those tags to be cleared on the standard lifecycle events
 * (created / updated / deleted / restored / forceDeleted) via Spatie's
 * ResponseCache facade — keeping invalidation surgical instead of nuking
 * the whole cache (which would defeat the tag-based strategy).
 *
 * @see https://spatie.be/docs/laravel-responsecache/v8/basic-usage/clearing-the-cache
 */
trait ClearsResponseCache
{
    /**
     * Boot the trait lifecycle hooks. Calls the protected overrideable
     * {@see responseCacheTagsFor()} so each model can declare its own tag set.
     */
    protected static function bootClearsResponseCache(): void
    {
        $flush = static function (self $model, string $event): void {
            $tags = $model->responseCacheTagsFor($event);

            if ($tags !== []) {
                ResponseCache::clear($tags);
            }
        };

        // These three static registration helpers exist unconditionally on
        // the base Model via the HasEvents concern.
        static::created(fn (self $model) => $flush($model, 'created'));
        static::updated(fn (self $model) => $flush($model, 'updated'));
        static::deleted(fn (self $model) => $flush($model, 'deleted'));

        // `restored` and `forceDeleted` static helpers live ONLY on the
        // SoftDeletes trait. Calling them on a bare model falls through to
        // Model::__callStatic, which does `(new static)->$method(...)` — and
        // running the constructor mid-boot triggers `bootIfNotBooted()` while
        // the model is still being booted (LogicException). Register them
        // only when the consuming model actually pulls in SoftDeletes.
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn (self $model) => $flush($model, 'restored'));
            static::forceDeleted(fn (self $model) => $flush($model, 'forceDeleted'));
        }
    }

    /**
     * Resolve the response-cache tags to flush for the given Eloquent event.
     *
     * Override in each consuming model. Returning an empty array disables
     * clearing for that event. Returning multiple tags is supported (e.g.
     * deleting a Workspace must also clear its items).
     *
     * @return list<string>
     */
    protected function responseCacheTagsFor(string $event): array
    {
        return [];
    }
}
