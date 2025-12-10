<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\AssetResource;
use App\Http\Resources\UserResource;
use App\Models\Asset;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/profile",
     *      operationId="getProfile",
     *      tags={"Profile"},
     *      summary="Get user profile and balances",
     *      description="Returns the authenticated user's profile, including USD balance and asset holdings.",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/UserResource")
     *      ),
     *
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $req): UserResource
    {
        /** @var \App\Models\User $user */
        $user = $req->user();
        $user->load('assets');

        return new UserResource($user);
    }
}
