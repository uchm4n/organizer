<?php

use App\Enums\Role;

test('the Role enum exposes admin and user cases only', function () {
    expect(array_column(Role::cases(), 'value'))
        ->toBe(['admin', 'user']);
});

test('each role case has a human-readable label', function (Role $role, string $label) {
    expect($role->label())->toBe($label);
})->with([
    'admin' => [Role::Admin, 'Administrator'],
    'user'  => [Role::User, 'User'],
]);

test('only the Admin role reports as admin', function () {
    expect(Role::Admin->isAdmin())->toBeTrue()
        ->and(Role::User->isAdmin())->toBeFalse();
});
