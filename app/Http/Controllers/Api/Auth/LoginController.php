<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\LoginUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

/**
 * @group Auth
 *
 * Authentication endpoints for issuing Sanctum access tokens.
 */
class LoginController extends Controller
{
    public function __construct(private readonly LoginUserAction $loginUser) {}

    /**
     * Issue an access token.
     *
     * Exchange credentials for a Sanctum bearer token. The returned token
     * must be sent as `Authorization: Bearer <token>` on subsequent requests.
     * Prior tokens for the user are revoked on each login.
     *
     * @unauthenticated
     *
     * @bodyParam email string required Valid email address. Example: uchm4n@gmail.com
     * @bodyParam password string required The user's password. Example: password
     *
     * @response 200 {
     *   "access_token": "1|abcdef1234567890",
     *   "token_type": "Bearer"
     * }
     * @response 422 {"message":"The request data did not pass validation.","errors":{"email":["The email field is required."]}}
     * @response 429 {"message":"Too Many Requests"}
     */
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
