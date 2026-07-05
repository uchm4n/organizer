<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function loginToken(string $email = 'taylor@example.com', string $password = 'secret-password'): string
{
    User::factory()->create([
        'email' => $email,
        'password' => Hash::make($password),
    ]);

    return test()->postJson(route('api.v1.auth.login'), [
        'email' => $email,
        'password' => $password,
    ])->json('access_token');
}

test('v2 clients receive a paginated list of users', function () {
    User::factory()->count(20)->create();
    loginToken(); // creates one extra user

    $token = loginToken('list-user@example.com');

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-API-Version' => '2',
    ])
        ->getJson(route('api.v2.user.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'email']],
            'links' => [['url', 'label', 'active']],
            'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
        ]);
})->skip('After we add v2 endpoints. This is for reference');

test('requests without a version header default to v2 and can list users', function () {
    User::factory()->count(5)->create();
    $token = loginToken();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson(route('api.v2.user.index'))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 15);
})->skip('After we add v2 endpoints. This is for reference');

test('v1 clients requesting the users endpoint receive a 426 upgrade required problem document', function () {
    $token = loginToken();

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-API-Version' => '1',
    ])
        ->getJson(route('api.v2.user.index'))
        ->assertStatus(426)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('type', 'https://httpstatuses.com/426')
        ->assertJsonPath('title', 'Upgrade Required')
        ->assertJsonPath('status', 426)
        ->assertJsonPath('required_version', 2)
        ->assertJsonPath('requested_version', 1);
})->skip('After we add v2 endpoints. This is for reference');

test('unauthenticated requests to the users endpoint receive a 401 problem document', function () {
    $this->withHeader('X-API-Version', '2')
        ->getJson(route('api.v2.user.index'))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonPath('status', 401);
})->skip('After we add v2 endpoints. This is for reference');

test('pagination respects per_page and navigates across pages', function () {
    User::factory()->count(30)->create();
    $token = loginToken('pager@example.com');

    $first = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-API-Version' => '2',
    ])
        ->getJson(route('api.v2.user.index').'?per_page=10')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 4)
        ->assertJsonPath('meta.total', 31) // 30 + the pager user
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.next_page_url', fn ($v) => str_contains((string) $v, 'page=2'));

    $second = $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-API-Version' => '2',
    ])
        ->getJson(route('api.v2.user.index').'?per_page=10&page=2')
        ->assertOk()
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.from', 11)
        ->assertJsonPath('meta.to', 20)
        ->assertJsonCount(10, 'data');
})->skip('After we add v2 endpoints. This is for reference');

test('user data in the paginated response matches the userdata shape', function () {
    User::factory()->create([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);
    $token = loginToken('viewer@example.com');

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
        'X-API-Version' => '2',
    ])
        ->getJson(route('api.v2.user.index'))
        ->assertOk()
        ->assertJsonPath('data.0.email', 'ada@example.com')
        ->assertJsonPath('data.0.name', 'Ada Lovelace');
})->skip('After we add v2 endpoints. This is for reference');
