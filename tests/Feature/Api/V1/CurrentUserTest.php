<?php

use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;

test('an authenticated user can fetch their current api user payload', function () {
    $user = User::factory()->create([
        'name'  => 'Taylor Otwell',
        'email' => 'taylor@example.com',
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson(route('api.v1.user.show'))
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('data.id', $user->getKey())
            ->where('data.name', 'Taylor Otwell')
            ->where('data.email', 'taylor@example.com')
            ->where('data.email_verified_at', $user->email_verified_at?->format(DATE_ATOM))
            ->where('data.created_at', $user->created_at?->format(DATE_ATOM))
            ->where('data.updated_at', $user->updated_at?->format(DATE_ATOM))
            ->missing('password')
            ->missing('remember_token')
        );
});

test('current api user endpoint requires authentication', function () {
    $this->getJson(route('api.v1.user.show'))
        ->assertUnauthorized();
});
