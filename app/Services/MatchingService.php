<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\OrderMatched;
use App\Enums\MatchResults;
use App\Enums\OrderStatuses;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchingService
{
    /**
     * @return array<string, string>|MatchResults
     */
    public function tryMatch(Order $newOrder, User $userModel = new User()): array|MatchResults
    {
        return DB::transaction(function () use ($newOrder, $userModel) { // phpcs:ignore
            $newOrder = Order::where('id', $newOrder->id)->lockForUpdate()->first();

            if (! $newOrder || $newOrder->status !== OrderStatuses::OPEN) {
                return MatchResults::ORDER_NOT_OPEN_OR_INVALID;
            }

            if ($newOrder->side === 'buy') {
                $counter = Order::where('symbol', $newOrder->symbol)
                    ->where('side', 'sell')
                    ->where('status', OrderStatuses::OPEN)
                    ->where('price', '<=', $newOrder->price)
                    ->orderBy('price', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
            } else { // newOrder is a 'sell'
                $counter = Order::where('symbol', $newOrder->symbol)
                    ->where('side', 'buy')
                    ->where('status', OrderStatuses::OPEN)
                    ->where('price', '>=', $newOrder->price)
                    ->orderBy('price', 'desc')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $counter) {
                return MatchResults::NO_COUNTER_ORDER;
            }

            if (bccomp((string) $newOrder->amount, (string) $counter->amount, 8) !== 0) {
                return MatchResults::AMOUNTS_NOT_EQUAL;
            }

            $buy = $newOrder->side === 'buy' ? $newOrder : $counter;
            $sell = $newOrder->side === 'sell' ? $newOrder : $counter;
            $price = (string) $sell->price; // execute at sell price (or mid logic; consistent rule)
            $amount = (string) $newOrder->amount;
            $usdValue = bcmul($amount, $price, 8);

            $buyerFeeUsd = bcmul($usdValue, (string) config('app.commission_rate'), 8);
            $sellerFeeAsset = bcmul($amount, (string) config('app.commission_rate'), 8);

            $buyer = $userModel->where('id', $buy->user_id)->lockForUpdate()->first();
            if (! $buyer) {
                return MatchResults::BUYER_NOT_FOUND;
            }

            $totalCostToBuyer = bcadd($usdValue, $buyerFeeUsd, 8);

            if (bccomp((string) $buy->locked_usd, $totalCostToBuyer, 8) === -1) {
                return MatchResults::INSUFFICIENT_BUYER_FUNDS;
            }

            $seller = $userModel->where('id', $sell->user_id)->lockForUpdate()->firstOrFail();

            $sellerAsset = Asset::where('user_id', $seller->id)
                ->where('symbol', $sell->symbol)
                ->lockForUpdate()
                ->firstOrFail();

            if (bccomp((string) $sellerAsset->locked_amount, $amount, 8) === -1) {
                return MatchResults::INSUFFICIENT_SELLER_ASSET_LOCKED;
            }

            $buyerAsset = Asset::firstOrCreate(
                ['user_id' => $buyer->id, 'symbol' => $buy->symbol],
                ['amount' => '0', 'locked_amount' => '0']
            );

            $actualCostWithFee = bcadd($usdValue, $buyerFeeUsd, 8);
            $refundToBuyer = bcsub((string) $buy->locked_usd, $actualCostWithFee, 8);

            $buyer->balance = bcadd((string) $buyer->balance, $refundToBuyer, 8);
            $buyer->save();

            $seller->balance = bcadd((string) $seller->balance, $usdValue, 8);
            $seller->save();

            $netAssetToBuyer = bcsub($amount, $sellerFeeAsset, 8);

            $sellerAsset->locked_amount = bcsub((string) $sellerAsset->locked_amount, $amount, 8);
            $sellerAsset->save();

            $buyerAsset->amount = bcadd((string) $buyerAsset->amount, $netAssetToBuyer, 8);
            $buyerAsset->save();

            $buy->status = OrderStatuses::FILLED;
            $sell->status = OrderStatuses::FILLED;
            $buy->locked_usd = '0';
            $sell->locked_asset = '0';
            $buy->save();
            $sell->save();

            Trade::create([
                'symbol' => $buy->symbol,
                'buy_order_id' => $buy->id,
                'sell_order_id' => $sell->id,
                'price' => $price,
                'amount' => $amount,
                'usd_value' => $usdValue,
                'buyer_fee_usd' => $buyerFeeUsd,
                'seller_fee_asset' => $sellerFeeAsset,
            ]);

            event(new OrderMatched($buy, $sell, (string) $buy->symbol, $price, $amount));

            return [
                'price' => $price,
                'amount' => $amount,
                'buyer_fee_usd' => $buyerFeeUsd,
                'seller_fee_asset' => $sellerFeeAsset,
            ];
        }, 3); // phpcs:ignore
    }
}
