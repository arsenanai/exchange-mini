<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use App\Services\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MatchOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $orderId) {}

    public function handle(MatchingService $matcher): void
    {
        $order = Order::find($this->orderId);
        if ($order) $matcher->tryMatch($order);
    }
}
