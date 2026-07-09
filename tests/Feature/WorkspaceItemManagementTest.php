<?php

use App\Enums\ItemType;
use App\Models\Item;
use App\Models\User;
use App\Models\Workspace;
use Laravel\Sanctum\Sanctum;

test('a regular user can upsert their own workspace via GET /workspace', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->getJson(route('api.v1.workspace.show'))
        ->assertSuccessful()
        ->assertJsonPath('data.user_id', $user->getKey())
        ->assertJsonPath('data.name', 'Workspace');

    expect(Workspace::query()->where('user_id', $user->getKey())->count())->toBeOne();
});

test('a regular user can upsert their own workspace via POST /workspace', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->postJson(route('api.v1.workspace.store'), [
            'name'     => 'My Workspace',
            'settings' => ['theme' => 'dark'],
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.user_id', $user->getKey())
        ->assertJsonPath('data.name', 'My Workspace')
        ->assertJsonPath('data.settings.theme', 'dark');

    // Re-posting upserts (no second row).
    $this
        ->postJson(route('api.v1.workspace.store'), ['name' => 'Renamed'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Renamed');

    expect(Workspace::query()->where('user_id', $user->getKey())->count())->toBeOne();
});

test('a regular user can update their own workspace via PATCH /workspace', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create(['name' => 'Old']);
    Sanctum::actingAs($user, ['*']);

    $this
        ->patchJson(route('api.v1.workspace.update'), ['name' => 'New'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'New');

    expect($workspace->fresh()->name)->toBe('New');
});

test('PATCH /workspace returns 404 when the user has no workspace yet', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->patchJson(route('api.v1.workspace.update'), ['name' => 'New'])
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('a regular user cannot access the admin workspace index', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->getJson(route('api.v1.workspace.index'))
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('a regular user cannot view another users workspace', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->getJson(route('api.v1.workspace.general.show', $workspace))
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('an admin can list all workspaces via GET /workspaces', function () {
    [$a, $b] = [User::factory()->create(), User::factory()->create()];
    Workspace::factory()->forUser($a)->create();
    Workspace::factory()->forUser($b)->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->getJson(route('api.v1.workspace.index'))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 2);
});

test('an admin can filter workspaces by user_id', function () {
    [$a, $b] = [User::factory()->create(), User::factory()->create()];
    Workspace::factory()->forUser($a)->create();
    Workspace::factory()->forUser($b)->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->getJson(route('api.v1.workspace.index', ['user_id' => $a->getKey()]))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.user_id', $a->getKey());
});

test('an admin can view any users workspace', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->getJson(route('api.v1.workspace.general.show', $workspace))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $workspace->getKey());
});

test('an admin can create a workspace for any user via POST /workspaces', function () {
    $target = User::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->postJson(route('api.v1.workspace.general.store'), [
            'user_id'  => $target->getKey(),
            'name'     => 'Acme',
            'settings' => null,
        ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $target->getKey())
        ->assertJsonPath('data.name', 'Acme');

    expect(Workspace::query()->where('user_id', $target->getKey())->exists())->toBeTrue();
});

test('POST /workspaces rejects creating a workspace when one already exists for the user', function () {
    $target = User::factory()->create();
    Workspace::factory()->forUser($target)->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->postJson(route('api.v1.workspace.general.store'), [
            'user_id' => $target->getKey(),
            'name'    => 'Acme',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

test('an admin can update any users workspace via PATCH /workspaces/{workspace}', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->patchJson(route('api.v1.workspace.general.update', $workspace), ['name' => 'Admin Renamed'])
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Admin Renamed');
});

test('an admin can delete a workspace via DELETE /workspaces/{workspace}', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->deleteJson(route('api.v1.workspace.general.destroy', $workspace))
        ->assertNoContent();

    expect(Workspace::query()->whereKey($workspace->getKey())->exists())->toBeFalse();
});

test('deleting a workspace cascades to its items', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Item::factory()->forWorkspace($workspace)->note()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->deleteJson(route('api.v1.workspace.general.destroy', $workspace))->assertNoContent();

    expect(Item::query()->where('workspace_id', $workspace->getKey())->count())->toBe(0);
});

test('POST /workspaces requires user_id and name', function () {
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->postJson(route('api.v1.workspace.general.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user_id', 'name']);
});

test('a regular user can list items within their own workspace via GET /items', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    Item::factory()->forWorkspace($workspace)->note()->create();
    Item::factory()->forWorkspace($workspace)->todo()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->getJson(route('api.v1.item.index'))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 2);
});

test('a regular user cannot see items in another users workspace via GET /items', function () {
    $owner          = User::factory()->create();
    $ownerWorkspace = Workspace::factory()->forUser($owner)->create();
    Item::factory()->forWorkspace($ownerWorkspace)->note()->create();

    $user          = User::factory()->create();
    $userWorkspace = Workspace::factory()->forUser($user)->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->getJson(route('api.v1.item.index'))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 0);
});

test('an admin can see all items via GET /items', function () {
    $owner          = User::factory()->create();
    $ownerWorkspace = Workspace::factory()->forUser($owner)->create();
    Item::factory()->forWorkspace($ownerWorkspace)->note()->create();

    $adminWorkspace = Workspace::factory()->forUser(User::factory()->admin()->create())->create();
    Item::factory()->forWorkspace($adminWorkspace)->todo()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->getJson(route('api.v1.item.index'))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 2);
});

test('GET /items filters by type', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    Item::factory()->forWorkspace($workspace)->note()->create();
    Item::factory()->forWorkspace($workspace)->todo()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->getJson(route('api.v1.item.index', ['type' => ItemType::Note->value]))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

test('GET /items filters by parent_id and root items via parent_id=0', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $parent    = Item::factory()->forWorkspace($workspace)->note()->create();
    Item::factory()->forWorkspace($workspace)->childOf($parent)->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->getJson(route('api.v1.item.index', ['parent_id' => 0]))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);

    $this
        ->getJson(route('api.v1.item.index', ['parent_id' => $parent->getKey()]))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

test('a regular user cannot view another users item via GET /items/{item}', function () {
    $owner          = User::factory()->create();
    $ownerWorkspace = Workspace::factory()->forUser($owner)->create();
    $item           = Item::factory()->forWorkspace($ownerWorkspace)->note()->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->getJson(route('api.v1.item.show', $item))
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('a regular user cannot create an item in another users workspace', function () {
    $owner          = User::factory()->create();
    $ownerWorkspace = Workspace::factory()->forUser($owner)->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->postJson(route('api.v1.item.store'), [
            'workspace_id' => $ownerWorkspace->getKey(),
            'type'         => ItemType::Note->value,
            'title'        => 'Stolen',
        ])
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('a regular user can create an item in their own workspace', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->postJson(route('api.v1.item.store'), [
            'workspace_id' => $workspace->getKey(),
            'type'         => ItemType::Note->value,
            'title'        => 'My Note',
            'data'         => ['body' => 'hello'],
        ])
        ->assertCreated()
        ->assertJsonPath('data.workspace_id', $workspace->getKey())
        ->assertJsonPath('data.title', 'My Note');
});

test('an admin can create an item in any workspace', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->postJson(route('api.v1.item.store'), [
            'workspace_id' => $workspace->getKey(),
            'type'         => ItemType::Todo->value,
            'title'        => 'Admin todo',
        ])
        ->assertCreated();
});

test('creating an item with a parent from a different workspace is rejected', function () {
    $user           = User::factory()->create();
    $userWorkspace  = Workspace::factory()->forUser($user)->create();
    $otherWorkspace = Workspace::factory()->forUser(User::factory()->create())->create();
    $otherParent    = Item::factory()->forWorkspace($otherWorkspace)->note()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->postJson(route('api.v1.item.store'), [
            'workspace_id' => $userWorkspace->getKey(),
            'parent_id'    => $otherParent->getKey(),
            'type'         => ItemType::Note->value,
            'title'        => 'Bad child',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');
});

test('a regular user can update their own item via PATCH /items/{item}', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->patchJson(route('api.v1.item.update', $item), ['title' => 'Renamed'])
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'Renamed');
});

test('a regular user cannot update another users item', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->patchJson(route('api.v1.item.update', $item), ['title' => 'Hacked'])
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('a regular user can soft-delete their own item via DELETE /items/{item}', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->deleteJson(route('api.v1.item.destroy', $item))
        ->assertNoContent();

    expect($item->fresh()->deleted_at)->not->toBeNull();
});

test('a soft-deleted item 404s on GET /items/{item}', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    Sanctum::actingAs($user, ['*']);

    $this->deleteJson(route('api.v1.item.destroy', $item))->assertNoContent();

    $this
        ->getJson(route('api.v1.item.show', $item->getKey()))
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('an owner or admin can restore a soft-deleted item', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    $item->delete();
    Sanctum::actingAs($user, ['*']);

    $this
        ->postJson(route('api.v1.item.restore', $item->getKey()))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $item->getKey())
        ->assertJsonPath('data.deleted_at', null);

    expect($item->fresh()->deleted_at)->toBeNull();
});

test('restoring a non-trashed item is idempotent', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->postJson(route('api.v1.item.restore', $item->getKey()))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $item->getKey());

    expect($item->fresh()->deleted_at)->toBeNull();
});

test('a regular user cannot restore another users item', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    $item->delete();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->postJson(route('api.v1.item.restore', $item->getKey()))
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('an admin can list items of a specific workspace via GET /workspaces/{workspace}/items', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Item::factory()->forWorkspace($workspace)->note()->create();
    Item::factory()->forWorkspace($workspace)->todo()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->getJson(route('api.v1.workspace.items.index', $workspace))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 2);
});

test('a regular user can list items of their own workspace via the nested endpoint', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    Item::factory()->forWorkspace($workspace)->note()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->getJson(route('api.v1.workspace.items.index', $workspace))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

test('a regular user cannot list items of another users workspace', function () {
    $owner     = User::factory()->create();
    $workspace = Workspace::factory()->forUser($owner)->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->getJson(route('api.v1.workspace.items.index', $workspace))
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('GET /items?with_trashed includes trashed items only for admins', function () {
    $user      = User::factory()->create();
    $workspace = Workspace::factory()->forUser($user)->create();
    $item      = Item::factory()->forWorkspace($workspace)->note()->create();
    $item->delete();
    Sanctum::actingAs($user, ['*']);

    // Regular user cannot see trashed items even with the flag.
    $this
        ->getJson(route('api.v1.item.index', ['with_trashed' => true]))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 0);

    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->getJson(route('api.v1.item.index', ['with_trashed' => true]))
        ->assertSuccessful()
        ->assertJsonPath('meta.total', 1);
});

test('POST /items validates the payload', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->postJson(route('api.v1.item.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['workspace_id', 'type', 'title']);
});
