<?php

declare(strict_types=1);

namespace App\Models;

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
 * @property int $status
 * @property string $locked_usd
 * @property string $locked_asset
 */
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'symbol', 'side', 'price', 'amount', 'status', 'locked_usd', 'locked_asset',
    ];

    const STATUS_OPEN = 1;

    const STATUS_FILLED = 2;

    const STATUS_CANCELLED = 3;

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
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
