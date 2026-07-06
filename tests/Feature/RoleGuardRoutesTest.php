<?php

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('an admin can list all users', function () {
    User::factory()->count(3)->create();
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin, ['*']);

    $this
        ->getJson(route('api.v1.user.index'))
        ->assertSuccessful()
        ->assertJsonCount(4, 'data'); // 3 created + the admin
});

test('a regular user receives 403 from the users index', function () {
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->getJson(route('api.v1.user.index'))
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 403)
        ->assertJsonPath('title', 'Forbidden');
});

test('an unauthenticated request receives 401 from the users index', function () {
    $this
        ->getJson(route('api.v1.user.index'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('an admin can fetch any users role', function () {
    $target = User::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->getJson(route('api.v1.user.role.show', $target))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $target->getKey())
        ->assertJsonPath('data.role', Role::User->value);
});

test('a regular user is forbidden from fetching others roles', function () {
    $target = User::factory()->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->getJson(route('api.v1.user.role.show', $target))
        ->assertForbidden();
});

test('an unauthenticated request is rejected with 401', function () {
    $target = User::factory()->create();

    $this->patchJson(route('api.v1.user.role.update', $target), [
        'role' => Role::Admin->value,
    ])->assertUnauthorized();
});
