<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     title="OrderResource",
 *     description="Order resource representation"
 * )
 */
/**
 * @property int $id
 * @property int $user_id
 * @property string $symbol
 * @property string $side
 * @property string $price
 * @property string $amount
 * @property int $status
 * @property string $locked_usd
 * @property string $locked_asset
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * @OA\Property(property="id", type="integer", example=1)
     * @OA\Property(property="userId", type="integer", example=1)
     * @OA\Property(property="symbol", type="string", example="BTC")
     * @OA\Property(property="side", type="string", enum={"buy", "sell"}, example="buy")
     * @OA\Property(property="price", type="string", format="decimal", example="50000.00000000")
     * @OA\Property(property="amount", type="string", format="decimal", example="0.10000000")
     * @OA\Property(property="status", type="integer", description="1: open, 2: filled, 3: cancelled", example=1)
     * @OA\Property(property="lockedUsd", type="string", format="decimal", example="5000.00000000")
     * @OA\Property(property="lockedAsset", type="string", format="decimal", example="0.00000000")
     * @OA\Property(property="createdAt", type="string", format="date-time")
     * @OA\Property(property="updatedAt", type="string", format="date-time")
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
            'userId' => $this->user_id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'price' => $this->price,
            'amount' => $this->amount,
            'status' => $this->status,
            'lockedUsd' => $this->locked_usd,
            'lockedAsset' => $this->locked_asset,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
