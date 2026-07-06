<?php

use App\Enums\Role;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('an admin can promote a regular user to admin via PATCH /users/{user}/role', function () {
    $target = User::factory()->create(['role' => Role::User]);
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->patchJson(route('api.v1.user.role.update', $target), [
            'role' => Role::Admin->value,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.id', $target->getKey())
        ->assertJsonPath('data.role', Role::Admin->value);

    expect($target->fresh()->role)->toBe(Role::Admin);
});

test('an admin can demote an admin back to user', function () {
    $target = User::factory()->admin()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this->patchJson(route('api.v1.user.role.update', $target), [
        'role' => Role::User->value,
    ])->assertSuccessful();

    expect($target->fresh()->role)->toBe(Role::User);
});

test('a regular user cannot change another users role', function () {
    $target = User::factory()->create();
    Sanctum::actingAs(User::factory()->create(), ['*']);

    $this
        ->patchJson(route('api.v1.user.role.update', $target), [
            'role' => Role::Admin->value,
        ])
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/problem+json');
});

test('role update rejects an unknown role value', function () {
    $target = User::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->patchJson(route('api.v1.user.role.update', $target), [
            'role' => 'superadmin',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');
});

test('role update requires the role field', function () {
    $target = User::factory()->create();
    Sanctum::actingAs(User::factory()->admin()->create(), ['*']);

    $this
        ->patchJson(route('api.v1.user.role.update', $target), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('role');
});

test('the user show endpoint still includes the role in the payload', function () {
    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user, ['*']);

    $this
        ->getJson(route('api.v1.user.show'))
        ->assertSuccessful()
        ->assertJsonPath('data.role', Role::Admin->value);
});
