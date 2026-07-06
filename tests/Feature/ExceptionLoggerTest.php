<?php

use App\Models\User;
use App\Support\ExceptionLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Route::get('/api/_test/throw', fn () => throw new RuntimeException('boom from test route'))
        ->middleware(['api', 'auth:sanctum']);
});

function loginTokenForLogging(string $email = 'logger@example.com'): string
{
    User::factory()->create([
        'email' => $email,
        'password' => Hash::make('secret-password'),
    ]);

    return test()->postJson(route('api.v1.auth.login'), [
        'email' => $email,
        'password' => 'secret-password',
    ])->json('access_token');
}

function withLogSpy(): Log
{
    Log::spy();

    return app('log');
}

test('server errors are logged once at error level with slim one-line format', function () {
    Log::spy();

    $token = loginTokenForLogging();

    $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-API-Version' => '1',
        ])
        ->getJson('/api/_test/throw');

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, '500 Internal Server Error')
                && str_contains($message, 'RuntimeException')
                && str_contains($message, 'boom from test route')
                && str_contains($message, 'at ')
                && ! str_contains($message, "#0 \n")
                && ($context['status'] ?? null) === 500
                && isset($context['trace_id'])
                && isset($context['route'])
                && isset($context['method'])
                && isset($context['url'])
                && isset($context['ip']);
        });
});

test('404 client noise exceptions are not logged', function () {
    Log::spy();

    $this->getJson('/api/no-such-endpoint');

    Log::shouldNotHaveReceived('error');
    Log::shouldNotHaveReceived('warning');
});

test('401 authentication exceptions are not logged', function () {
    Log::spy();

    $this->getJson(route('api.v1.user.index'));

    Log::shouldNotHaveReceived('error');
    Log::shouldNotHaveReceived('warning');
});

test('403 authorization exceptions are logged at warning level as api misuse signals', function () {
    Log::spy();

    // Force a 403 by throwing AuthorizationException from a route.
    Route::get('/api/_test/forbidden', fn () => throw new AuthorizationException('not allowed'))
        ->middleware(['api', 'auth:sanctum']);

    $token = loginTokenForLogging();

    $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-API-Version' => '1',
        ])
        ->getJson('/api/_test/forbidden');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, '403 Forbidden')
                && str_contains($message, 'AuthorizationException')
                && ($context['status'] ?? null) === 403;
        });
});

test('422 validation exceptions are logged at warning level as api misuse signals', function () {
    Log::spy();

    // Login route requires email + password; omitting them triggers 422.
    $this->postJson(route('api.v1.auth.login'));

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, '422 Unprocessable Entity')
                && str_contains($message, 'ValidationException')
                && ($context['status'] ?? null) === 422;
        });
});

test('429 throttle exceptions are logged at warning level as api misuse signals', function () {
    Log::spy();

    RateLimiter::shouldReceive('limiter')
        ->with('api')
        ->andReturn(fn () => Limit::perMinute(1000)->by('test'));
    RateLimiter::shouldReceive('tooManyAttempts')->andReturn(true);
    RateLimiter::shouldReceive('availableIn')->andReturn(47);

    $this->actingAs(User::factory()->create())
        ->withHeader('X-API-Version', '1')
        ->getJson(route('api.v1.user.show'));

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return str_contains($message, '429 Too Many Requests')
                && str_contains($message, 'ThrottleRequestsException')
                && ($context['status'] ?? null) === 429;
        });
});

test('logged context includes the requesting user id when authenticated', function () {
    Log::spy();

    $token = loginTokenForLogging();

    $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-API-Version' => '1',
        ])
        ->getJson('/api/_test/throw');

    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(function (string $message, array $context): bool {
            return array_key_exists('user', $context)
                && is_int($context['user']);
        });
});

test('exception logger classifies client noise correctly', function () {
    expect(ExceptionLogger::isClientNoise(new AuthenticationException))
        ->toBeTrue()
        ->and(ExceptionLogger::isClientNoise(new ModelNotFoundException))->toBeTrue()
        ->and(ExceptionLogger::isClientNoise(new NotFoundHttpException))->toBeTrue()
        ->and(ExceptionLogger::isClientNoise(new RuntimeException))->toBeFalse()
        ->and(ExceptionLogger::isClientNoise(new AuthorizationException))->toBeFalse()
        ->and(ExceptionLogger::isClientNoise(ValidationException::withMessages(['x' => ['y']])))->toBeFalse()
        ->and(ExceptionLogger::isClientNoise(new ThrottleRequestsException))->toBeFalse();
});

test('exception logger maps throttle exceptions to 429 status', function () {
    expect(ExceptionLogger::httpStatus(new ThrottleRequestsException))->toBe(429)
        ->and(ExceptionLogger::httpStatus(ValidationException::withMessages(['x' => ['y']])))->toBe(422)
        ->and(ExceptionLogger::httpStatus(new AuthorizationException))->toBe(403);
});

test('trace_id is generated per request and exposed via response header', function () {
    $token = loginTokenForLogging();

    $r1 = $this
        ->withHeaders(['Authorization' => 'Bearer '.$token, 'X-API-Version' => '1'])
        ->getJson(route('api.v1.user.index'));
    $r2 = $this
        ->withHeaders(['Authorization' => 'Bearer '.$token, 'X-API-Version' => '1'])
        ->getJson(route('api.v1.user.index'));

    $id1 = $r1->headers->get('X-Trace-Id');
    $id2 = $r2->headers->get('X-Trace-Id');

    expect($id1)->not
        ->toBeNull()
        ->and($id2)->not
        ->toBeNull()
        ->and($id1)->not
        ->toBe($id2)
        ->and(strlen((string) $id1))->toBe(8);
});

test('user supplied trace id is preserved across the request', function () {
    $token = loginTokenForLogging();

    $r = $this
        ->withHeaders([
            'Authorization' => 'Bearer '.$token,
            'X-API-Version' => '1',
            'X-Trace-Id' => 'custom123',
        ])
        ->getJson(route('api.v1.user.index'));

    expect($r->headers->get('X-Trace-Id'))->toBe('custom123');
});

test('log file is named organizer not laravel', function () {
    expect(config('logging.channels.single.path'))
        ->toContain('organizer.log')
        ->and(config('logging.channels.daily.path'))->toContain('organizer.log')
        ->and(config('logging.channels.emergency.path'))->toContain('organizer.log');
});
