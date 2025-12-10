<?php

namespace App\Events;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderMatched implements ShouldBroadcast
{
    public function __construct(
        public Order $buyOrder,
        public Order $sellOrder,
        public string $symbol,
        public string $price,
        public string $amount
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->buyOrder->user_id}"),
            new PrivateChannel("user.{$this->sellOrder->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }

    public function broadcastWith(): array
    {
        return [
            'symbol' => $this->symbol,
            'price' => $this->price,
            'amount' => $this->amount,
            'buyOrder' => new OrderResource($this->buyOrder),
            'sellOrder' => new OrderResource($this->sellOrder),
        ];
    }
}
