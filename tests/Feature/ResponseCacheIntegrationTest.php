<?php

use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Live Redis integration smoke test
|--------------------------------------------------------------------------
|
| Only runs when `REDIS_INTEGRATION=1` is set (Redis must be reachable and
| `RESPONSE_CACHE_ENABLED=true`). The array store used in regular CI can't
| tag-cache, so this stays dormant. Locally:
|
|   REDIS_INTEGRATION=1 RESPONSE_CACHE_ENABLED=true \
|     php artisan test --compact tests/Feature/ResponseCacheIntegrationTest.php
|
| Verifies end-to-end SWR + tag invalidation:
|   1. first GET writes a fresh response (MISS)
|   2. second GET serves the cached entry (HIT)
|   3. mutating the underlying model flushes the tag
|   4. third GET rebuilds the response (MISS again)
|
*/

$enabled = (bool) env('REDIS_INTEGRATION', false);

test('GET /items misses, hits, then misses again after an item is created', function (): void {
    Cache::store('redis')->flush();

    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    Sanctum::actingAs($user, ['*']);

    $this->getJson(route('api.v1.item.index'))
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'MISS');

    $this->getJson(route('api.v1.item.index'))
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'HIT');

    Item::factory()->forWorkspace($workspace)->note()->create();

    $this->getJson(route('api.v1.item.index'))
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'MISS')
        ->assertJsonPath('meta.total', 1);
})->skip(! $enabled, 'Set REDIS_INTEGRATION=1, RESPONSE_CACHE_ENABLED=true, and run Redis to enable');

test('a role change on a user invalidates their cached items response', function (): void {
    Cache::store('redis')->flush();

    $admin = User::factory()->admin()->create();
    $owner = User::factory()->create();
    $item  = Item::factory()->forWorkspace(Workspace::factory()->forUser($owner)->create())->note()->create();

    Sanctum::actingAs($admin, ['*']);
    $this->getJson(route('api.v1.item.index'))
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'MISS');
    $this->getJson(route('api.v1.item.index'))
        ->assertHeader('X-Cache-Status', 'HIT');

    // Downgrade the admin — role-change edge case must flush 'items'.
    $admin->update(['role' => Role::User]);

    Sanctum::actingAs($admin, ['*']);
    $this->getJson(route('api.v1.item.index'))
        ->assertOk()
        ->assertHeader('X-Cache-Status', 'MISS');
})->skip(! $enabled, 'Set REDIS_INTEGRATION=1, RESPONSE_CACHE_ENABLED=true, and run Redis to enable');
