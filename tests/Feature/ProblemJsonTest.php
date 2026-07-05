<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    Route::get('/api/_test/throw', fn () => throw new RuntimeException('boom from test route'))
        ->middleware('api');
});

test('validation failures are returned as problem+json', function () {
    $this->postJson(route('api.v1.auth.login'))
        ->assertUnprocessable()
        ->assertHeader('Content-Type', 'application/problem+json')
        // ->assertJsonPath('type', 'https://httpstatuses.com/422')
        ->assertJsonPath('title', 'Unprocessable Entity')
        ->assertJsonPath('status', 422)
        ->assertJsonPath('detail', 'The request data did not pass validation.')
        ->assertJsonPath('errors.email.0', fn (string $message) => str_contains($message, 'email'))
        ->assertJsonPath('errors.password.0', fn (string $message) => str_contains($message, 'password'));
});

test('unauthenticated requests receive a 401 problem document', function () {
    $this->getJson(route('api.v1.user.show'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json')
        // ->assertJsonPath('type', 'https://httpstatuses.com/401')
        ->assertJsonPath('title', 'Unauthorized')
        ->assertJsonPath('status', 401);
});

test('unknown api endpoints produce a 404 problem document', function () {
    $this->getJson('/api/no-such-endpoint')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/problem+json')
        // ->assertJsonPath('type', 'https://httpstatuses.com/404')
        ->assertJsonPath('title', 'Not Found')
        ->assertJsonPath('status', 404)
        ->assertJsonPath('detail', 'The requested endpoint does not exist.');
});

test('expired tokens are rejected with a 401 problem document', function () {
    $user = User::factory()->create([
        'email' => 'taylor@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $plain = $user->createToken('api-token', ['*'], now()->subDay())->plainTextToken;

    expect(PersonalAccessToken::findToken($plain))->not->toBeNull();

    $this->withHeader('Authorization', 'Bearer '.$plain)
        ->getJson(route('api.v1.user.show'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 401)
        ->assertJsonPath('title', 'Unauthorized');
});

test('generic exceptions become a 500 problem document with diagnostics when not in production', function () {
    $this->getJson('/api/_test/throw')
        ->assertInternalServerError()
        ->assertHeader('Content-Type', 'application/problem+json')
        // ->assertJsonPath('type', 'https://httpstatuses.com/500')
        ->assertJsonPath('title', 'Internal Server Error')
        ->assertJsonPath('status', 500)
        ->assertJsonPath('detail', 'boom from test route')
        ->assertJsonPath('exception', RuntimeException::class)
        ->assertJsonStructure(['exception', 'file', 'line']);
});

test('generic exceptions become a 500 problem document with a generic message in production', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->getJson('/api/_test/throw')
        ->assertInternalServerError()
        ->assertHeader('Content-Type', 'application/problem+json')
        // ->assertJsonPath('type', 'https://httpstatuses.com/500')
        ->assertJsonPath('title', 'Internal Server Error')
        ->assertJsonPath('status', 500)
        ->assertJsonPath('detail', 'An unexpected error occurred. Please try again later.')
        ->assertJsonMissingPath('exception');
});

test('requests without an api version header default to the current version', function () {
    User::factory()->create([
        'email' => 'taylor@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $token = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'taylor@example.com',
        'password' => 'secret-password',
    ])->json('access_token');

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.v1.user.show'))
        ->assertOk()
        ->assertJsonPath('data.email', 'taylor@example.com');
});

test('requests with a supported api version header are accepted', function () {
    User::factory()->create([
        'email' => 'taylor@example.com',
        'password' => Hash::make('secret-password'),
    ]);

    $token = $this->withHeader('X-API-Version', '1')
        ->postJson(route('api.v1.auth.login'), [
            'email' => 'taylor@example.com',
            'password' => 'secret-password',
        ])->json('access_token');

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-API-Version' => '1',
    ])
        ->getJson(route('api.v1.user.show'))
        ->assertOk();
});

test('requests with an unsupported api version are rejected with a 400 problem document', function () {
    $this->withHeader('X-API-Version', '99')
        ->getJson(route('api.v1.user.show'))
        ->assertBadRequest()
        ->assertHeader('Content-Type', 'application/problem+json')
        // ->assertJsonPath('type', 'https://httpstatuses.com/400')
        ->assertJsonPath('title', 'Bad Request')
        ->assertJsonPath('status', 400)
        ->assertJsonPath('supported.0', 1);
});

test('requests with a non-numeric api version are rejected', function () {
    $this->withHeader('X-API-Version', 'banana')
        ->getJson(route('api.v1.user.show'))
        ->assertBadRequest()
        ->assertJsonPath('status', 400)
        ->assertJsonPath('title', 'Bad Request');
});
