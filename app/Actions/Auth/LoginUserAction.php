<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class LoginUserAction
{
    private const string TokenName = 'api-token';

    public function handle(string $email, string $password): NewAccessToken
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Delete old tokens if any, prevent failure login token buildup
        $user->tokens()->delete();

        return $user->createToken(self::TokenName, expiresAt: now()->addMinutes(config('sanctum.expiration')));
    }
}
