<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="AssetResource",
 *     title="AssetResource",
 *     description="Asset resource representation",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="symbol", type="string", example="BTC"),
 *     @OA\Property(property="amount", type="string", format="decimal", example="1.50000000"),
 *     @OA\Property(property="lockedAmount", type="string", format="decimal", example="0.50000000")
 * )
 *
 * @mixin \App\Models\Asset
 */
class AssetResource extends JsonResource
{
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