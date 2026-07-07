# Implementation Plan: Workspace & Item API Endpoints

**Spec:** `docs/superpowers/specs/2026-07-08-workspace-item-endpoints-design.md`

## Phase 1: Foundation (DTOs + Actions + Policies)

### 1.1 Response DTOs
- [ ] `app/Data/Api/WorkspaceData.php` (`final extends Data`, `fromModel`)
- [ ] `app/Data/Api/ItemData.php` (`final extends Data`, `fromModel`)

### 1.2 Request payload DTOs (per-domain subfolders)
- [ ] `app/Data/Api/Workspace/WorkspaceStoreData.php`
- [ ] `app/Data/Api/Workspace/WorkspaceUpdateData.php`
- [ ] `app/Data/Api/Workspace/WorkspaceUpsertData.php`
- [ ] `app/Data/Api/Item/ItemStoreData.php`
- [ ] `app/Data/Api/Item/ItemUpdateData.php`

### 1.3 Actions
- [ ] `CreateWorkspaceAction`, `UpsertWorkspaceAction`, `UpdateWorkspaceAction`, `DeleteWorkspaceAction`
- [ ] `CreateItemAction`, `UpdateItemAction`, `DeleteItemAction`, `RestoreItemAction`

### 1.4 Policies
- [ ] `app/Policies/WorkspacePolicy.php`
- [ ] `app/Policies/ItemPolicy.php`

### 1.5 Model tweak
- [ ] Add `workspace(): HasOne` to `User` model

## Phase 2: Controllers + Routes

### 2.1 Own workspace controllers
- [ ] `WorkspaceShowController`, `WorkspaceStoreController`, `WorkspaceUpdateController`

### 2.2 Admin workspace controllers
- [ ] `WorkspaceIndexController`, `WorkspaceGeneralShowController`, `WorkspaceGeneralStoreController`, `WorkspaceGeneralUpdateController`, `WorkspaceGeneralDestroyController`

### 2.3 Nested workspace items controller
- [ ] `WorkspaceItemsIndexController`

### 2.4 Item controllers
- [ ] `ItemIndexController`, `ItemShowController`, `ItemStoreController`, `ItemUpdateController`, `ItemDestroyController`, `ItemRestoreController`

### 2.5 Routes
- [ ] Append workspace + item routes to `routes/api.php`

## Phase 3: Tests

- [ ] `tests/Feature/WorkspaceManagementTest.php`
- [ ] `tests/Feature/ItemManagementTest.php`

## Phase 4: Verification

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --compact`