<?php

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

it('returns a problem json 429 with rate-limit headers when rate limited', function () {
    RateLimiter::shouldReceive('limiter')
        ->with('api')
        ->andReturn(fn () => Limit::perMinute(1000)->by('test'));

    RateLimiter::shouldReceive('tooManyAttempts')->andReturn(true);
    RateLimiter::shouldReceive('availableIn')->andReturn(47);

    $response = $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.user.show'));

    $response
        ->assertStatus(429)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertHeader('Retry-After', '47')
        ->assertHeader('X-RateLimit-Limit')
        ->assertHeader('X-RateLimit-Remaining')
        ->assertHeader('X-RateLimit-Reset')
        ->assertJsonPath('status', 429)
        ->assertJsonPath('title', 'Too Many Requests')
        ->assertJsonPath('retry_after', 47)
        ->assertJsonStructure(['trace_id'])
        ->assertJsonMissingPath('type');
});

it('does not throttle successful authenticated requests under the limit', function () {
    $this->actingAs(User::factory()->create())
        ->getJson(route('api.v1.user.show'))
        ->assertOk();
});
