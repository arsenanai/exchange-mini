<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="AssetResource",
 *     description="Asset resource representation"
 * )
 */

/**
 * @property int $id
 * @property string $symbol
 * @property string $amount
 * @property string $locked_amount
 * @mixin \App\Models\Asset
 */
class AssetResource extends JsonResource
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     * @OA\Property(property="symbol", type="string", example="BTC")
     * @OA\Property(property="amount", type="string", format="decimal", example="1.50000000")
     * @OA\Property(property="lockedAmount", type="string", format="decimal", example="0.50000000")
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
            'symbol' => $this->symbol,
            'amount' => $this->amount,
            'lockedAmount' => $this->locked_amount,
        ];
    }
}