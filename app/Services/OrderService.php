<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrderStatuses;
use App\Exceptions\InsufficientFundsException;
use App\Exceptions\OrderNotOpenException;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\UnauthorizedException;

class OrderService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws InsufficientFundsException
     */
    public function createOrder(array $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) { // phpcs:ignore
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            if ($data['side'] === 'buy') {
                $usdRequired = bcmul((string) $data['amount'], (string) $data['price'], 8);

                if (bccomp((string) $user->balance, $usdRequired, 8) === -1) {
                    throw new InsufficientFundsException('Insufficient USD balance');
                }

                $user->balance = bcsub((string) $user->balance, $usdRequired, 8);
                $user->save();

                return Order::create([
                    'user_id' => $user->id,
                    'symbol' => $data['symbol'],
                    'side' => 'buy',
                    'price' => $data['price'],
                    'amount' => $data['amount'],
                    'status' => OrderStatuses::OPEN,
                    'locked_usd' => $usdRequired,
                    'locked_asset' => '0',
                ]);
            }

            $asset = Asset::where('user_id', $user->id)
                ->where('symbol', $data['symbol'])
                ->lockForUpdate()
                ->first();

            if (! $asset || bccomp((string) $asset->amount, (string) $data['amount'], 8) === -1) {
                throw new InsufficientFundsException('Insufficient asset balance');
            }

            $asset->amount = bcsub((string) $asset->amount, (string) $data['amount'], 8);
            $asset->locked_amount = bcadd((string) $asset->locked_amount, (string) $data['amount'], 8);
            $asset->save();

            return Order::create([
                'user_id' => $user->id,
                'symbol' => $data['symbol'],
                'side' => 'sell',
                'price' => $data['price'],
                'amount' => $data['amount'],
                'status' => OrderStatuses::OPEN,
                'locked_usd' => '0',
                'locked_asset' => $data['amount'],
            ]);
        }, 3); // phpcs:ignore
    }

    public function cancelOrder(int $orderId, User $user): Order
    {
        return DB::transaction(function () use ($orderId, $user) { // phpcs:ignore
            $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail(); // phpcs:ignore

            if ($order->user_id !== $user->id) {
                throw new UnauthorizedException('You are not authorized to cancel this order.');
            }
            if ($order->status !== OrderStatuses::OPEN) {
                throw new OrderNotOpenException('Order not open');
            }

            if ($order->side === 'buy') {
                $user = User::where('id', $user->id)->lockForUpdate()->first();
                $user->balance = bcadd((string) $user->balance, (string) $order->locked_usd, 8);
                $user->save();
            } else {
                $asset = Asset::where('user_id', $user->id)->where('symbol', $order->symbol)->lockForUpdate()->firstOrFail();
                $asset->locked_amount = bcsub((string) $asset->locked_amount, (string) $order->locked_asset, 8);
                $asset->amount = bcadd((string) $asset->amount, (string) $order->locked_asset, 8);
                $asset->save();
            }

            $order->update(['status' => OrderStatuses::CANCELLED, 'locked_usd' => '0', 'locked_asset' => '0']);

            return $order;
        }, 3); // phpcs:ignore
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOpenOrders(?string $symbol): Collection
    {
        $query = Order::query()->where('status', OrderStatuses::OPEN);
        if ($symbol) {
            $query->where('symbol', $symbol);
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    /**
     * @return Collection<int, Order>
     */
    public function getUserOrders(User $user): Collection
    {
        return $user->orders()->orderBy('created_at', 'desc')->get();
    }
}
