<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="UserResource",
 *     description="User resource representation"
 * )
 */

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $balance
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     * @OA\Property(property="name", type="string", example="John Doe")
     * @OA\Property(property="email", type="string", format="email", example="john.doe@example.com")
     * @OA\Property(property="balanceUsd", type="string", format="decimal", example="10000.00000000")
     * @OA\Property(property="assets", type="array", @OA\Items(ref="#/components/schemas/AssetResource"))
     */
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