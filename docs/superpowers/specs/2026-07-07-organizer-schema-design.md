# Organizer — Database Schema Design

- **Date:** 2026-07-07
- **Status:** Approved by user
- **Scope:** DB schema design + migration files only. No migration execution, no models, no controllers in this phase.

---

## 1. Goal

Introduce a flexible, single-tree schema that holds every Organizer domain
(notes, todos, spreadsheets, tax filings, calendar/events, document vault, plus
future custom types) using two new tables:

- `workspaces` — the 1:1 root container for the single authenticated user.
- `items` — a recursive (adjacency-list) tree holding every typed entry.

Discrimination between domains happens at the application layer via an
int-backed PHP enum (`App\Enums\ItemType`), **never via a DB enum column**
(per project rule: enum columns cannot evolve).

## 2. Non-goals

- **Executing** the migrations — files are created only.
- Models, controllers, routes, Data DTOs, FormRequests — out of scope.
- Tags, polymorphic attachments, multi-workspace support, side tables for
  per-domain payloads. All explicitly deferred per Q&A.
- Per-domain typed columns surfaced on `items` — everything domain-specific
  lives inside the `data` JSON column.

## 3. Decisions captured (from brainstorming)

| # | Question | Decision |
|---|---|---|
| 1 | Modeling style | **Hybrid** — generic tree for notes/todos/spreadsheets/tax/custom; events and documents ride the same tree with their payload in JSON `data` (no separate tables for them in this phase). |
| 2 | Tree depth | **Ad-hoc tree, unlimited depth** — self-referential `items.parent_id`. |
| 3 | Type discriminator in DB | `tinyInteger unsigned` + PHP `int`-backed enum cast (`App\Enums\ItemType`). **No DB enums.** |
| 4 | Payload storage | Single `items.data JSON` column. |
| 5 | Surfaced typed columns | Pure: only tree + metadata columns; no `due_at`/`occurs_at`/etc. surfaced. |
| 6 | Workspace cardinality | Strict 1:1 with user (`workspaces.user_id` UNIQUE + NOT NULL). |
| 7 | Auth scoping column | `items.workspace_id` only (no redundant `items.user_id`). |
| 8 | Soft deletes / tags / attachments | Soft deletes only on `items`. No tags table, no generic attachments table in this phase. |
| 9 | Enum file location | `app/Enums/ItemType.php` (not `app/Support/Enums/`). |

## 4. Schema

### 4.1 `workspaces`

The one-per-user root container.

| Column | Type | Nullable | Default | Index | Notes |
|---|---|---|---|---|---|
| `id` | `bigIncrements` | no | auto | PK | Standard. |
| `user_id` | `unsignedBigInteger` | no | — | `UNIQUE` FK → `users.id` `ON DELETE CASCADE` | Enforces 1:1 with the user. Cascade so deleting a user wipes the workspace. |
| `name` | `string(120)` | no | `'Personal'` | — | Human label. Seeded `'Personal'` so a user never sees a nameless container. |
| `settings` | `json` | yes | NULL | — | Reserved for per-workspace prefs (default view, sort, theme). Nullable so it is optional today; future features use it without needing a new migration. Left empty by default. |
| `created_at` | `timestamp` | no | now | — | |
| `updated_at` | `timestamp` | no | now | — | |

Indexes:
- `PRIMARY KEY (id)`
- `UNIQUE (user_id)` — enforces the 1:1 rule.
- FK `workspaces_user_id_foreign → users.id ON DELETE CASCADE`

No soft deletes on `workspaces`: a user deleting their account cascades the whole workspace.

### 4.2 `items`

The recursive tree holding every typed entry.

| Column | Type | Nullable | Default | Index | Notes |
|---|---|---|---|---|---|
| `id` | `bigIncrements` | no | auto | PK | |
| `workspace_id` | `unsignedBigInteger` | no | — | FK → `workspaces.id` `ON DELETE CASCADE`, indexed | Scoping column. Auth check: `workspace.user_id === auth()->id()`. |
| `parent_id` | `unsignedBigInteger` | yes | NULL | FK → `items.id` `ON DELETE CASCADE`, indexed | Self-referential adjacency list. NULL = root. CASCADE removes entire subtree on parent delete. |
| `type` | `tinyInteger unsigned` | no | — | indexed (composite with `workspace_id`) | Discriminator. **Not a DB enum.** Maps 1:1 to `App\Enums\ItemType` int-backed PHP enum. New types = enum case added, zero migration. |
| `title` | `string(255)` | no | — | — | Display label. |
| `data` | `json` | yes | NULL | — | Type-specific structured payload. Validated by per-type FormRequests. Examples below. |
| `sort_order` | `integer` | no | `0` | indexed (composite with `parent_id`) | Sibling ordering under a parent. Clients renumber on reorder. |
| `created_at` | `timestamp` | no | now | — | |
| `updated_at` | `timestamp` | no | now | — | |
| `deleted_at` | `timestamp` | yes | NULL | indexed | Soft delete for trash/restore. |

Indexes:
- `PRIMARY KEY (id)`
- FK `items_workspace_id_foreign → workspaces.id ON DELETE CASCADE`
- FK `items_parent_id_foreign → items.id ON DELETE CASCADE`
- INDEX `items_workspace_id_type_index (workspace_id, type)` — typical "list my todos in this workspace" query.
- INDEX `items_parent_id_index (parent_id)` — fetch children of a node.
- INDEX `items_sort_order_index (sort_order)` (or composite with `parent_id`).
- INDEX `items_deleted_at_index (deleted_at)` — Laravel soft-delete filter.

### 4.3 `data` JSON payload — illustrative shapes (no DB enforcement)

These are application-side contracts only; the migration does not validate them.

- `Note`        → `{ "body": "...", "format": "markdown" }`
- `Todo`        → `{ "due_at": "2026-08-01T00:00:00Z", "completed": false, "priority": "high" }`
- `Spreadsheet` → `{ "columns": ["A","B","C"], "rows": [["1","2","3"]] }`
- `TaxFiling`   → `{ "year": 2025, "jurisdiction": "DE", "lines": [...] }`
- `Event`       → `{ "starts_at": "...", "ends_at": "...", "rrule": "FREQ=WEEKLY", "location": "..." }`
- `Document`    → `{ "file_path": "...", "mime": "...", "size": 12345, "checksum": "..." }`
- `Custom`      → arbitrary user-shaped JSON.

### 4.4 `App\Enums\ItemType`

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemType: int
{
    case Note = 1;
    case Todo = 2;
    case Spreadsheet = 3;
    case TaxFiling = 4;
    case Event = 5;
    case Document = 6;
    case Custom = 99;
}
```

Pure code — adding a case is the only step to register a new type. Models will
declare `$casts = ['type' => ItemType::class]` so Laravel auto-marshals between
the tinyInt column and the enum.

## 5. Files to create

1. `app/Enums/ItemType.php` — the int-backed PHP enum.
2. `database/migrations/2026_07_07_120000_create_workspaces_table.php`
3. `database/migrations/2026_07_07_120001_create_items_table.php`

No seeders, factories, or models in this phase — those are tracked as
follow-ups for the implementation step.

## 6. Out-of-scope follow-ups (tracked, not built now)

- `Workspace` and `Item` Eloquent models with casts + relations.
- `WorkspaceFactory`, `ItemFactory` (with per-type states).
- Auto-creating a `Workspace` row on user registration ( listener on
  `Registered` event or in a `CreateUserAction`).
- FormRequest + Data + Controller for `items.*` and `workspaces.*` endpoints.
- Decision about whether `data` JSON should use SQLite's `JSON` column type or
  `TEXT` — both supported; migration uses `$table->json('data')` which is
  portable across SQLite/MySQL/Postgres.

## 7. Risks / trade-offs acknowledged

- **No DB-level constraints on JSON `data`.** Validation moves entirely to PHP.
  Acceptable for a single-user app; would matter at scale.
- **Self-referential FK on `items.parent_id` with `ON DELETE CASCADE`** — deleting
  a root removes the whole subtree. Intentional; matched to "trash/restore" via
  soft deletes at the API layer (deleting an item sets `deleted_at` and only
  a hard purge follows the FK cascade).
- **Composite index `(workspace_id, type)`** assumes the dominant query shape
  is "list items of type T in this workspace." If usage shifts to "fetch any
  item by id with eager-loaded children," the indexes change. Cheap migration
  to add/drop indexes later.
- **`settings` JSON on `workspaces`** is reserved/nullable — not populated in
  this phase, included only to avoid a migration later if prefs land.