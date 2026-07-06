<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

test('a user can login and receive an api token', function () {
    $user = User::factory()->create([
        'email'    => 'taylor@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email'    => 'taylor@example.com',
        'password' => 'secret-password',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonStructure([
            'access_token',
            'token_type',
        ]);

    $token = PersonalAccessToken::findToken($response->json('access_token'));

    expect($token)->not->toBeNull()
        ->and($token->getAttribute('tokenable_type'))->toBe(User::class)
        ->and($token->getAttribute('tokenable_id'))->toBe($user->getKey())
        ->and($token->getAttribute('name'))->toBe('api-token');
});

test('a user can authenticate api requests with the issued token', function () {
    User::factory()->create([
        'email'    => 'taylor@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $token = $this->postJson(route('api.v1.auth.login'), [
        'email'    => 'taylor@example.com',
        'password' => 'secret-password',
    ])->json('access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.v1.user.show'))
        ->assertOk()
        ->assertJsonPath('data.email', 'taylor@example.com');
});

test('login rejects invalid credentials', function () {
    User::factory()->create([
        'email'    => 'taylor@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $this->postJson(route('api.v1.auth.login'), [
        'email'    => 'taylor@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect(PersonalAccessToken::query()->count())->toBe(0);
});

test('login requires credentials', function () {
    $this->postJson(route('api.v1.auth.login'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});
