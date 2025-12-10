<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     title="UserResource",
 *     description="User resource representation",
 *     type="object",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="balanceUsd", type="string", format="decimal", example="10000.00000000"),
 *     @OA\Property(
 *         property="assets",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/AssetResource")
 *     )
 * )
 *
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'balanceUsd' => $this->balance,
            'assets' => AssetResource::collection($this->whenLoaded('assets')),
        ];
    }
}
