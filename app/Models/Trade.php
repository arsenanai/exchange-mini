<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    protected $fillable = [
        'symbol','buy_order_id','sell_order_id','price','amount','usd_value',
        'buyer_fee_usd','seller_fee_asset'
    ];
}
