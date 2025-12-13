<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * @OA\Post(
     *      path="/api/register",
     *      operationId="registerUser",
     *      tags={"Authentication"},
     *      summary="Register a new user",
     *      description="Creates a new user account and returns the user data.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/RegisterRequest")
     *      ),
     *
     *      @OA\Response(
     *          response=201,
     *          description="User registered successfully",
     *
     *          @OA\JsonContent(properties={
     *              @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *          })
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *
     *          @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *      )
     * )
     */
    public function register(RegisterRequest $req): UserResource
    {
        $user = $this->authService->register($req->validated());

        return new UserResource($user->load('assets'));
    }

    /**
     * @OA\Post(
     *      path="/api/login",
     *      operationId="loginUser",
     *      tags={"Authentication"},
     *      summary="Log in a user",
     *      description="Logs in a user and returns an API token and user data.",
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/LoginRequest")
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful login",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="token", type="string", example="1|aBcDeFgHiJkLmNoPqRsTuVwXyZ"),
     *              @OA\Property(property="user", type="object",
     *                  @OA\Property(property="data", ref="#/components/schemas/UserResource")
     *              )
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Unauthenticated",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="Invalid credentials")
     *          )
     *      )
     * )
     */
    public function login(LoginRequest $req): JsonResponse
    {
        $result = $this->authService->login($req->validated());

        return response()->json([
            'token' => $result['token'],
            'user' => [
                'data' => new UserResource($result['user']->load('assets')),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/logout",
     *      operationId="logoutUser",
     *      tags={"Authentication"},
     *      summary="Log out the current user",
     *      description="Invalidates the user's current API token.",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful logout",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="Logged out")
     *          )
     *      )
     * )
     */
    public function logout(Request $req): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $req->user();
        $this->authService->logout($user);

        return response()->json(['message' => 'Logged out']);
    }
}
