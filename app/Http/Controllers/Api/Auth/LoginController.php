<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(private readonly LoginUserAction $loginUser) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $token = $this->loginUser->handle(
            $request->email(),
            $request->password(),
        );

        return response()->json([
            'access_token' => $token->plainTextToken,
            'token_type'   => 'Bearer',
        ]);
    }
}
