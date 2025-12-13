<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatuses;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $symbol
 * @property string $side
 * @property string $price
 * @property string $amount
 * @property OrderStatuses $status
 * @property string $locked_usd
 * @property string $locked_asset
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'symbol', 'side', 'price', 'amount', 'status', 'locked_usd', 'locked_asset',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'amount' => 'decimal:8',
            'locked_usd' => 'decimal:8',
            'locked_asset' => 'decimal:8',
            'status' => OrderStatuses::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
