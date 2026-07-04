<?php

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

test('an authenticated user can fetch their current api user payload', function () {
    $user = User::factory()->create([
        'name' => 'Taylor Otwell',
        'email' => 'taylor@example.com',
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson(route('api.v1.user.show'))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('id', $user->getKey())
            ->where('name', 'Taylor Otwell')
            ->where('email', 'taylor@example.com')
            ->where('email_verified_at', $user->email_verified_at?->format(DATE_ATOM))
            ->where('created_at', $user->created_at?->format(DATE_ATOM))
            ->where('updated_at', $user->updated_at?->format(DATE_ATOM))
            ->missing('password')
            ->missing('remember_token')
        );
});

test('current api user endpoint requires authentication', function () {
    $this->getJson(route('api.v1.user.show'))
        ->assertUnauthorized();
});
