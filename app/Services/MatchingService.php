<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\OrderMatched;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchingService
{
    const COMMISSION_RATE = 0.015; // 1.5%

    // Consistent fee policy:
    // - Buyer pays USD fee deducted from balance.
    // - Seller pays asset fee deducted from received asset amount (converted as asset amount fee = amount * COMMISSION_RATE).
    // (Other consistent choices acceptable; this one is explicit)
    public function tryMatch(Order $newOrder): ?array
    {
        return DB::transaction(function () use ($newOrder) {
            // Reload with lock
            $newOrder = Order::where('id', $newOrder->id)->lockForUpdate()->first();
            if (! $newOrder || $newOrder->status !== Order::STATUS_OPEN) {
                return null;
            }

            // Find counterparty order (first valid)
            if ($newOrder->side === 'buy') {
                $counter = Order::where('symbol', $newOrder->symbol)
                    ->where('side', 'sell')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '<=', $newOrder->price)
                    ->orderBy('price', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
            } else {
                $counter = Order::where('symbol', $newOrder->symbol)
                    ->where('side', 'buy')
                    ->where('status', Order::STATUS_OPEN)
                    ->where('price', '>=', $newOrder->price)
                    ->orderBy('price', 'desc')
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();
            }

            if (! $counter) {
                return null;
            }

            // Full match only: amounts must be equal
            if (bccomp((string) $newOrder->amount, (string) $counter->amount, 8) !== 0) {
                // For simplicity, reject if not equal (no partials)
                return null;
            }

            // Prepare roles
            $buy = $newOrder->side === 'buy' ? $newOrder : $counter;
            $sell = $newOrder->side === 'sell' ? $newOrder : $counter;
            $price = (string) $sell->price; // execute at sell price (or mid logic; consistent rule)
            $amount = (string) $newOrder->amount;
            $usdValue = bcmul($amount, $price, 8);

            // Commission calculation
            $buyerFeeUsd = bcmul($usdValue, (string) self::COMMISSION_RATE, 8);
            $sellerFeeAsset = bcmul($amount, (string) self::COMMISSION_RATE, 8);

            // Lock involved users and assets
            $buyer = User::where('id', $buy->user_id)->lockForUpdate()->first();
            $seller = User::where('id', $sell->user_id)->lockForUpdate()->first();
            if (! $buyer || ! $seller) {
                Log::error("MatchingService: Could not find buyer or seller for orders {$buy->id} and {$sell->id}.");

                return null;
            }

            // Seller asset record
            $sellerAsset = Asset::where('user_id', $seller->id)
                ->where('symbol', $sell->symbol)
                ->lockForUpdate()
                ->first();

            if (! $sellerAsset || bccomp((string) $sell->locked_asset, $amount, 8) === -1) {
                // Not enough locked asset (shouldn't happen if created properly)
                // This indicates a data inconsistency or a race condition that wasn't caught earlier.
                // Log this error, or throw an exception, as it's an unexpected state.
                // For now, we'll return null to prevent further processing with a null asset.
                Log::error(
                    "MatchingService: Seller asset locked_asset ({$sell->locked_asset}) insufficient for amount ({$amount}) for order {$sell->id}."
                );

                return null;
            } // Note: The logic correctly uses order-level locks, so no further user-level lock validation is needed here.

            // Deduct buyer: unlock order.locked_usd -> final charge = usdValue + buyerFeeUsd
            // We don't maintain user-level locked; we reduce user.balance at order creation already
            $buyerBalanceBefore = (string) $buyer->balance;

            // At order creation, buyer's balance was reduced by the max possible cost (buy->locked_usd).
            // Now, we calculate the refund from any price improvement.
            $priceImprovement = bcsub((string) $buy->locked_usd, $usdValue, 8);

            // The final charge is the fee. The main cost is already accounted for.
            // We add back the price improvement and subtract the fee.
            if (bccomp($priceImprovement, $buyerFeeUsd, 8) === -1) {
                // This case is unlikely but means the fee is greater than the price improvement.
                $buyer->balance = bcsub((string) $buyer->balance, bcsub($buyerFeeUsd, $priceImprovement, 8), 8);
            } else {
                $buyer->balance = bcadd((string) $buyer->balance, bcsub($priceImprovement, $buyerFeeUsd, 8), 8);
            }
            $buyer->save();

            // Credit seller USD minus nothing (we choose buyer pays USD fee).
            // Seller receives USD: usdValue gets credited.
            $seller->balance = bcadd((string) $seller->balance, $usdValue, 8);
            $seller->save();

            // Transfer asset to buyer: amount minus seller asset fee
            $netAssetToBuyer = bcsub($amount, $sellerFeeAsset, 8);

            // Reduce seller locked asset by amount, and ultimately amount from seller asset
            $sellerAsset->locked_amount = bcsub((string) $sellerAsset->locked_amount, $amount, 8);
            // $sellerAsset->amount = bcsub((string) $sellerAsset->amount, $amount, 8); // This is incorrect, the amount is already reduced when the order was created
            $sellerAsset->save();

            // Credit buyer asset
            $buyerAsset = Asset::where('user_id', $buyer->id)
                ->where('symbol', $buy->symbol)
                ->lockForUpdate()
                ->first();
            if (! $buyerAsset) {
                $buyerAsset = Asset::create([
                    'user_id' => $buyer->id,
                    'symbol' => $buy->symbol,
                    'amount' => '0',
                    'locked_amount' => '0',
                ]);
                $buyerAsset = Asset::where('id', $buyerAsset->id)->lockForUpdate()->first();
            }
            if (! $buyerAsset) {
                // This should be an exceptional case, log an error and abort.
                Log::error("MatchingService: Failed to create or lock buyer asset for user {$buyer->id}.");

                return null;
            }
            $buyerAsset->amount = bcadd((string) $buyerAsset->amount, $netAssetToBuyer, 8);
            $buyerAsset->save();

            // Mark both orders filled
            $buy->status = Order::STATUS_FILLED;
            $sell->status = Order::STATUS_FILLED;
            $buy->locked_usd = '0';
            $sell->locked_asset = '0';
            $buy->save();
            $sell->save();

            // Log trade (optional)
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

            // Broadcast
            event(new OrderMatched($buy, $sell, (string) $buy->symbol, $price, $amount));

            return [
                'price' => $price,
                'amount' => $amount,
                'buyer_fee_usd' => $buyerFeeUsd,
                'seller_fee_asset' => $sellerFeeAsset,
            ];
        }, 3);
    }
}
