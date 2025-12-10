<?php

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
     *      security={{"sanctum":{}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="balanceUsd", type="string", example="9850.00000000"),
     *              @OA\Property(property="assets", type="array", @OA\Items(ref="#/components/schemas/AssetResource"))
     *          )
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
