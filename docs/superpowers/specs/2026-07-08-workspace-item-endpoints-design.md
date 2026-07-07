# Workspace & Item API Endpoints — Design Spec

**Date:** 2026-07-08
**Status:** Approved (with item restore addition)
**Scope:** API endpoints for the existing `Workspace` and `Item` models, using Spatie laravel-data DTOs and Action classes for state mutations.

## 1. Context

The application already has `Workspace` (1:1 with `User`, `unique user_id`) and `Item` (`belongsTo Workspace`, self-referential adjacency list via `parent_id`, soft deletes, `ItemType` int-backed enum) models with factories and migrations. There are no endpoints, controllers, actions, DTOs, or policies for these domains yet.

Established conventions (to follow exactly):
- Domain-sliced layout under `app/Actions/{Domain}/`, `app/Data/Api/`, `app/Http/Controllers/Api/{Domain}/`.
- Invokable `final` controllers, one action per class, named `{Entity}{Action}Controller`.
- Spatie `Data` classes for both responses (`::fromModel()`) and request payloads (validated via `#[Required]`, `#[Enum(...)]` attributes). Payload DTOs are injected as controller method parameters.
- Actions are HTTP-agnostic, single `handle(...)` method, accept models/enums/primitives, return domain objects. Only created for state-mutating or non-HTTP use cases.
- Policies are auto-discovered (`app/Policies/{Model}Policy.php`), throw `AuthorizationException` → 403 Problem+JSON (wired in `bootstrap/app.php`).
- All errors render as RFC 9457 Problem+JSON via `App\Support\Problem`.
- Routes named `api.v1.{domain}.{action}`, no URL versioning (header-based `X-API-Version`).
- Scribe annotations: `@group`, `@authenticated`, `@bodyParam`, `@urlParam`, `@queryParam`, `@response` on every controller.
- Pest tests using `Sanctum::actingAs`, named routes, Problem+JSON assertions.

## 2. Authorization Model

Two layers of access control:

- **Regular user** sees/mutates only their own workspace and the items within it.
- **Admin** (`Role::Admin`, gated by `role:admin` middleware alias) sees/mutates every user's workspaces and items.

Enforcement:
1. **Route-level**: `role:admin` middleware on the admin-only `/workspaces` collection route (`GET /workspaces`, `POST /workspaces`).
2. **Policy-level**: instance authorization (`view`, `update`, `delete`, `restore`) — `Role::Admin` or owner. `AuthorizationException` → 403 Problem+JSON.
3. **Query-level tenancy**: `ItemIndexController` builds the base query scoped to the actor's workspace for regular users, all items for admins.

## 3. Route Surface

All under the existing `api.v1.` group with `throttle:api` + `auth:sanctum`. `apiPrefix` is empty (URLs at domain root).

### Workspace — own (regular users)

| Method | URI | Route name | Policy |
|---|---|---|---|
| `GET` | `/workspace` | `api.v1.workspace.show` | (enforces own via auth user) |
| `POST` | `/workspace` | `api.v1.workspace.store` | upsert own |
| `PATCH` | `/workspace` | `api.v1.workspace.update` | update own |

### Workspace — admin (any user)

| Method | URI | Route name | Middleware |
|---|---|---|---|
| `GET` | `/workspaces` | `api.v1.workspace.index` | `role:admin` |
| `GET` | `/workspaces/{workspace}` | `api.v1.workspace.general.show` | policy `view` |
| `POST` | `/workspaces` | `api.v1.workspace.general.store` | `role:admin` |
| `PATCH` | `/workspaces/{workspace}` | `api.v1.workspace.general.update` | policy `update` |
| `DELETE` | `/workspaces/{workspace}` | `api.v1.workspace.general.destroy` | policy `delete` |

### Items — flat (regular + admin)

| Method | URI | Route name | Policy |
|---|---|---|---|
| `GET` | `/items` | `api.v1.item.index` | query-level tenancy |
| `GET` | `/items/{item}` | `api.v1.item.show` | `view` |
| `POST` | `/items` | `api.v1.item.store` | `create` (workspace tenancy) |
| `PATCH` | `/items/{item}` | `api.v1.item.update` | `update` |
| `DELETE` | `/items/{item}` | `api.v1.item.destroy` | `delete` |
| `POST` | `/items/{id}/restore` | `api.v1.item.restore` | `restore` (custom binding) |

### Items — nested optional

| Method | URI | Route name | Policy |
|---|---|---|---|
| `GET` | `/workspaces/{workspace}/items` | `api.v1.workspace.items.index` | workspace `view` |

## 4. Data Objects (DTOs)

### Response DTOs (top-level, shared)

- `app/Data/Api/WorkspaceData.php` — `final class extends Data`.
  Props: `int $id`, `int $user_id`, `string $name`, `?array $settings`, `?Carbon $created_at`, `?Carbon $updated_at`.
  `fromModel(Workspace): self`.

- `app/Data/Api/ItemData.php` — `final class extends Data`.
  Props: `int $id`, `int $workspace_id`, `?int $parent_id`, `ItemType $type`, `string $title`, `?array $data`, `int $sort_order`, `?Carbon $created_at`, `?Carbon $updated_at`, `?Carbon $deleted_at`.
  `fromModel(Item): self`.

### Request payloads (per-domain subfolders)

- `app/Data/Api/Workspace/WorkspaceStoreData.php` — admin create. `user_id` (`#[Required] int, exists:users,id`), `name` (string|max:120), `settings` (array|null, optional).
- `app/Data/Api/Workspace/WorkspaceUpdateData.php` — own + admin. `name` (optional|string|max:120), `settings` (array|null, optional).
- `app/Data/Api/Workspace/WorkspaceUpsertData.php` — own upsert. Same shape as `WorkspaceUpdateData` (both optional).

- `app/Data/Api/Item/ItemStoreData.php` —
  `workspace_id` (`#[Required] int, exists:workspaces,id`),
  `parent_id` (nullable int, exists:items,id),
  `type` (`#[Required] #[Enum(ItemType::class)]`),
  `title` (`#[Required] string|max:255`),
  `data` (nullable array),
  `sort_order` (int, default 0).
- `app/Data/Api/Item/ItemUpdateData.php` — all optional: `parent_id`, `type`, `title`, `data`, `sort_order`.

Validation for `exists:` rules uses Spatie's `#[Exists('table', 'column')]` attribute where available; fall back to `#[Rule(...)]` if needed.

## 5. Actions

Each is a plain `class` (not `final`), single `handle(...)` method, HTTP-agnostic.

- `app/Actions/Workspace/CreateWorkspaceAction.php`
  `handle(int $userId, WorkspaceStoreData $data): Workspace` — creates a new workspace for the given user. Throws `ValidationException` if a workspace already exists for that user (unique constraint).

- `app/Actions/Workspace/UpsertWorkspaceAction.php`
  `handle(User $user, WorkspaceUpsertData $data): Workspace` — `firstOrCreate(['user_id' => $user->getKey()])`, then fill + save + return `fresh()`.

- `app/Actions/Workspace/UpdateWorkspaceAction.php`
  `handle(Workspace $workspace, WorkspaceUpdateData $data): Workspace` — fill present fields, save, `fresh()`.

- `app/Actions/Workspace/DeleteWorkspaceAction.php`
  `handle(Workspace $workspace): void` — `$workspace->delete()` (cascade handles items).

- `app/Actions/Item/CreateItemAction.php`
  `handle(ItemStoreData $data, User $actor): Item` — wraps in `DB::transaction`. Domain rule: if `parent_id` set, the parent item must belong to the same `workspace_id`; else throw `ValidationException::withMessages(['parent_id' => ...])`. Returns the created item with `fresh()`.

- `app/Actions/Item/UpdateItemAction.php`
  `handle(Item $item, ItemUpdateData $data): Item` — transaction; same parent-workspace-consistency rule on `parent_id` change; fill + save + `fresh()`.

- `app/Actions/Item/DeleteItemAction.php`
  `handle(Item $item): void` — `$item->delete()` (soft).

- `app/Actions/Item/RestoreItemAction.php`
  `handle(Item $item): void` — `$item->restore()`. Idempotent (no-op if not trashed — controller returns the model either way).

Show/index endpoints have no Action (one-liner in controller, per the AGENTS.md decision rule).

## 6. Controllers

All `final class extends Controller`, invokable `__invoke(...)`, Scribe-annotated.

### Workspace — own

- `app/Http/Controllers/Api/Workspace/WorkspaceShowController.php`
  `__invoke(Request $request): WorkspaceData` — `UpsertWorkspaceAction->handle($request->user(), new WorkspaceUpsertData())` (returns upserted own workspace for convenience on first access).
- `WorkspaceStoreController` — `POST /workspace` upsert own. Same upsert, returns `201` on create / `200` on update (use `Response::HTTP_CREATED` or `HTTP_OK` conditionally). For simplicity: always 200 (upsert semantics).
- `WorkspaceUpdateController` — `PATCH /workspace` update own. Loads `$request->user()->workspace` (404 via `firstOrFail` if absent), `UpdateWorkspaceAction->handle(...)`, returns `WorkspaceData`.

### Workspace — admin

- `WorkspaceIndexController` — `__invoke(Request $request): PaginatedDataCollection`. `per_page` default 10; optional `user_id` filter. `WorkspaceData::collect(Workspace::query()->paginate(...), PaginatedDataCollection::class)`.
- `WorkspaceGeneralShowController` — `__invoke(Workspace $workspace): WorkspaceData`. Policy `view` invoked by route-model binding.
- `WorkspaceGeneralStoreController` — `__invoke(WorkspaceStoreData $data): WorkspaceData`. `CreateWorkspaceAction->handle($data->user_id, $data)`. Returns 201.
- `WorkspaceGeneralUpdateController` — `__invoke(WorkspaceUpdateData $data, Workspace $workspace): WorkspaceData`. `UpdateWorkspaceAction->handle(...)`.
- `WorkspaceGeneralDestroyController` — `__invoke(Workspace $workspace): JsonResponse` 204. `DeleteWorkspaceAction->handle(...)`.

### Workspace — nested items

- `WorkspaceItemsIndexController` — `__invoke(Request $request, Workspace $workspace): PaginatedDataCollection`. Policy `view` on workspace gates. Honors `?type=`, `?parent_id=`, `?with_trashed=`, `?per_page=`.

### Items

- `ItemIndexController` — `__invoke(Request $request): PaginatedDataCollection`. Base query: admin = all items; regular = `where('workspace_id', $user->workspace->getKey())` (auto-create the workspace via `firstOrCreate` to avoid null). Filters: `type`, `parent_id`, `with_trashed` (admin only), `per_page` (default 10).
- `ItemShowController` — `__invoke(Item $item): ItemData`. Policy `view`.
- `ItemStoreController` — `__invoke(ItemStoreData $data, Request $request): ItemData`. Policy `create` (workspace tenancy — gate invoked with `$request->user()` and `$data->workspace_id`). `CreateItemAction->handle($data, $request->user())`. Returns 201.
- `ItemUpdateController` — `__invoke(ItemUpdateData $data, Item $item): ItemData`. Policy `update`. `UpdateItemAction->handle($item, $data)`.
- `ItemDestroyController` — `__invoke(Item $item): JsonResponse` 204. Policy `delete`. `DeleteItemAction->handle(...)`.
- `ItemRestoreController` — `__invoke(int $id, Request $request): ItemData`. Loads `Item::withTrashed()->findOrFail($id)` (manual; route-model binding excludes trashed). Policy `restore`. `RestoreItemAction->handle($item)`. Returns restored `ItemData`.

## 7. Policies

Auto-discovered (`app/Policies/{Model}Policy.php`).

### `WorkspacePolicy`

- `viewAny(User $user): bool` — `Role::Admin` only (gate for admin index).
- `view(User $user, Workspace $workspace): bool` — admin or `$user->is($workspace->user)`.
- `create(User $user): bool` — `Role::Admin` (admin `POST /workspaces` is wrapped in `role:admin` middleware, but the policy also guards).
- `update(User $user, Workspace $workspace): bool` — admin or owner.
- `delete(User $user, Workspace $workspace): bool` — admin or owner.

### `ItemPolicy`

- `viewAny(User $user): bool` — `true` (index query enforces tenancy).
- `view(User $user, Item $item): bool` — admin or `$user->is($item->workspace->user)`.
- `create(User $user, int $workspaceId): bool` — admin or `$user->is(User::find($workspaceId)?->workspace?->user)` — actually simpler: load `Workspace::find($workspaceId)`, then `$user->is($workspace->user)`. Controller invokes gate manually before action.
- `update(User $user, Item $item): bool` — admin or owner.
- `delete(User $user, Item $item): bool` — admin or owner.
- `restore(User $user, Item $item): bool` — admin or owner.

For `ItemPolicy::create`, since there's no model instance bound, the controller invokes the gate manually:
```php
Gate::authorize('create', [Item::class, $data->workspace_id]);
```
And the policy:
```php
public function create(User $user, int $workspaceId): Response {
    $workspace = Workspace::find($workspaceId);
    return ($user->hasRole(Role::Admin) || ($workspace && $user->is($workspace->user)))
        ? Response::allow() : Response::deny('You do not own this workspace.');
}
```

## 8. Error Handling

| Status | Mechanism |
|---|---|
| 401 | unauthenticated → already wired |
| 403 | policy `deny` → `AuthorizationException` → already wired |
| 404 | implicit route-model binding `ModelNotFoundException` → already wired; restore uses `withTrashed()->findOrFail` |
| 422 | Spatie Data validation attribute failures; `ValidationException` from action domain rules (parent cross-workspace) → already wired, `errors` extension member populated |
| 500 | catch-all → already wired |

No manual `Problem::response()` calls in controllers required.

## 9. Scribe Documentation

Every controller gets:
- `@group Workspace Management` or `@group Item Management`
- `@authenticated`
- `@urlParam`, `@bodyParam`, `@queryParam` as relevant
- `@response` success example (200/201) and `@response` 4xx Problem+JSON examples

## 10. Tests (Pest)

`tests/Feature/WorkspaceManagementTest.php`:
- Regular user: `GET /workspace` upserts own (creates on first call, returns existing on second).
- Regular user: `PATCH /workspace` updates own name/settings.
- Regular user: `GET /workspaces` (admin index) → 403 Problem+JSON.
- Regular user: `GET /workspaces/{workspace}` where not owner → 403.
- Admin: `GET /workspaces` paginated, filter by `user_id`.
- Admin: `GET /workspaces/{workspace}` of any user → 200.
- Admin: `POST /workspaces` creates for any user → 201.
- Admin: `PATCH /workspaces/{workspace}` of any user.
- Admin: `DELETE /workspaces/{workspace}` cascades items.
- 422 for store/update validation failures (missing `user_id`, bad `name`).
- 422 if admin `POST /workspaces` for a user that already has a workspace (unique violation).

`tests/Feature/ItemManagementTest.php`:
- Regular user: `GET /items` returns only own items.
- Admin: `GET /items` returns all items.
- `GET /items?type=note` filters by type.
- `GET /items?parent_id={id}` filters children.
- `GET /items?with_trashed=true` — admin only (regular user ignored or 403).
- Regular user: `GET /items/{item}` of another user → 403.
- Regular user: `POST /items` with another user's `workspace_id` → 403 (policy `create`).
- Admin: `POST /items` for any workspace → 201.
- Create with `parent_id` from another workspace → 422 (domain rule).
- `PATCH /items/{item}` own, admin, and 403 non-owner.
- `DELETE /items/{item}` soft-deletes; subsequent `GET /items/{item}` → 404.
- `POST /items/{id}/restore` admin/owner → restores, returns 200 with `ItemData`.
- `POST /items/{id}/restore` non-owner → 403.
- Idempotent restore on non-trashed item.
- `GET /workspaces/{workspace}/items` — owner OK, non-owner 403, admin OK.

## 11. Out of Scope

- No dedicated reorder/move routes (covered via `sort_order`/`parent_id` in `ItemUpdateData`).
- No separate workspace settings routes.
- No bulk endpoints.
- No webhook/event emission on mutations.
- No API versioning changes (still v1).

## 12. Files to Create/Modify

### Create
- `app/Data/Api/WorkspaceData.php`
- `app/Data/Api/ItemData.php`
- `app/Data/Api/Workspace/WorkspaceStoreData.php`
- `app/Data/Api/Workspace/WorkspaceUpdateData.php`
- `app/Data/Api/Workspace/WorkspaceUpsertData.php`
- `app/Data/Api/Item/ItemStoreData.php`
- `app/Data/Api/Item/ItemUpdateData.php`
- `app/Actions/Workspace/CreateWorkspaceAction.php`
- `app/Actions/Workspace/UpsertWorkspaceAction.php`
- `app/Actions/Workspace/UpdateWorkspaceAction.php`
- `app/Actions/Workspace/DeleteWorkspaceAction.php`
- `app/Actions/Item/CreateItemAction.php`
- `app/Actions/Item/UpdateItemAction.php`
- `app/Actions/Item/DeleteItemAction.php`
- `app/Actions/Item/RestoreItemAction.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceShowController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceStoreController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceUpdateController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceIndexController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceGeneralShowController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceGeneralStoreController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceGeneralUpdateController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceGeneralDestroyController.php`
- `app/Http/Controllers/Api/Workspace/WorkspaceItemsIndexController.php`
- `app/Http/Controllers/Api/Item/ItemIndexController.php`
- `app/Http/Controllers/Api/Item/ItemShowController.php`
- `app/Http/Controllers/Api/Item/ItemStoreController.php`
- `app/Http/Controllers/Api/Item/ItemUpdateController.php`
- `app/Http/Controllers/Api/Item/ItemDestroyController.php`
- `app/Http/Controllers/Api/Item/ItemRestoreController.php`
- `app/Policies/WorkspacePolicy.php`
- `app/Policies/ItemPolicy.php`
- `tests/Feature/WorkspaceManagementTest.php`
- `tests/Feature/ItemManagementTest.php`

### Modify
- `app/Models/User.php` — add `workspace(): HasOne` relation.
- `routes/api.php` — append workspace/item routes to the existing `auth:sanctum` group.