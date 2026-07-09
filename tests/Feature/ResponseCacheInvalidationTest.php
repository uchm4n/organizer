<?php

use App\Enums\Role;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Spatie\ResponseCache\Facades\ResponseCache;

/*
|--------------------------------------------------------------------------
| Tag-based invalidation contract
|--------------------------------------------------------------------------
|
| These tests verify that Eloquent lifecycle events on each model trigger the
| correct `ResponseCache::clear([...])` call with the expected tag set.
| A Mockery facade spy is replaced before the action under test so the
| assertion window covers only the triggering event (factory setup has
| already happened and its `clear()` calls are out of scope).
|
*/

function rearmResponseCacheSpy(): void
{
    // Re-spy() swaps in a fresh spy, clearing all prior recorded calls so
    // assertions observe only the model event fired after this line.
    ResponseCache::spy();
}

// Item ---------------------------------------------------------------------

test('creating an item clears the items tag', function (): void {
    $workspace = Workspace::factory()->forUser(User::factory()->create())->create();
    rearmResponseCacheSpy();

    Item::factory()->forWorkspace($workspace)->note()->create();

    ResponseCache::shouldHaveReceived('clear')->with(['items'])->once();
});

test('updating an item clears the items tag', function (): void {
    $workspace = Workspace::factory()->forUser(User::factory()->create())->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    rearmResponseCacheSpy();

    $item->update(['title' => 'Renamed']);

    ResponseCache::shouldHaveReceived('clear')->with(['items'])->once();
});

test('soft-deleting an item clears the items tag', function (): void {
    $workspace = Workspace::factory()->forUser(User::factory()->create())->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    rearmResponseCacheSpy();

    $item->delete();

    ResponseCache::shouldHaveReceived('clear')->with(['items'])->once();
});

test('force-deleting an item clears the items tag', function (): void {
    $workspace = Workspace::factory()->forUser(User::factory()->create())->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    $item->delete();
    rearmResponseCacheSpy();

    $item->forceDelete();

    // forceDelete fires both `deleted` and `forceDeleted` lifecycle events;
    // each flushes `['items']`, so require at least one matching call.
    ResponseCache::shouldHaveReceived('clear')->with(['items'])->atLeast()->once();
});

test('restoring a soft-deleted item clears the items tag', function (): void {
    $workspace = Workspace::factory()->forUser(User::factory()->create())->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    $item->delete();
    rearmResponseCacheSpy();

    $item->restore();

    // restore() also writes the model (nulls deleted_at), firing `updated`
    // alongside `restored`; both flush `['items']`.
    ResponseCache::shouldHaveReceived('clear')->with(['items'])->atLeast()->once();
});

// Workspace ----------------------------------------------------------------

test('creating a workspace clears both workspaces and items tags', function (): void {
    rearmResponseCacheSpy();

    Workspace::factory()->forUser(User::factory()->create())->create();

    ResponseCache::shouldHaveReceived('clear')->with(['workspaces', 'items'])->once();
});

test('updating a workspace clears both workspaces and items tags', function (): void {
    $workspace = Workspace::factory()->forUser(User::factory()->create())->create();
    rearmResponseCacheSpy();

    $workspace->update(['name' => 'Renamed']);

    ResponseCache::shouldHaveReceived('clear')->with(['workspaces', 'items'])->once();
});

test('deleting a workspace clears both workspaces and items tags', function (): void {
    $workspace = Workspace::factory()->forUser(User::factory()->create())->create();
    rearmResponseCacheSpy();

    $workspace->delete();

    ResponseCache::shouldHaveReceived('clear')->with(['workspaces', 'items'])->once();
});

// User ---------------------------------------------------------------------

test('creating a user clears the users tag', function (): void {
    rearmResponseCacheSpy();

    User::factory()->create();

    ResponseCache::shouldHaveReceived('clear')->with(['users'])->once();
});

test('updating a user without changing the role clears only the users tag', function (): void {
    $user = User::factory()->create();
    rearmResponseCacheSpy();

    $user->update(['name' => 'Renamed']);

    ResponseCache::shouldHaveReceived('clear')->with(['users'])->once();
});

test('changing a users role clears users, items, and workspaces tags', function (): void {
    $user = User::factory()->create();
    rearmResponseCacheSpy();

    $user->update(['role' => Role::Admin]);

    ResponseCache::shouldHaveReceived('clear')->with(['users', 'items', 'workspaces'])->once();
});

test('deleting a user clears the users tag', function (): void {
    $user = User::factory()->create();
    rearmResponseCacheSpy();

    $user->delete();

    ResponseCache::shouldHaveReceived('clear')->with(['users'])->once();
});
