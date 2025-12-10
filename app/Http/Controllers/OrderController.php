<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Asset;
use App\Models\Order;
use App\Services\MatchingService;
use App\Jobs\MatchOrderJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/orders",
     *      operationId="getOrderBook",
     *      tags={"Orders"},
     *      summary="Get open orders for the order book",
     *      description="Returns a list of all open buy and sell orders. Can be filtered by symbol.",
     *      @OA\Parameter(
     *          name="symbol",
     *          in="query",
     *          description="Filter orders by symbol (e.g., BTC, ETH)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/OrderResource")
     *          )
     *      )
     * )
     */
    public function index(Request $req): AnonymousResourceCollection
    {
        $symbol = $req->query('symbol');
        $query = Order::query()->where('status', Order::STATUS_OPEN);
        if ($symbol) $query->where('symbol', $symbol);
        $orders = $query->orderBy('created_at', 'asc')->get();
        return OrderResource::collection($orders);
    }

    /**
     * @OA\Post(
     *      path="/api/orders",
     *      operationId="createOrder",
     *      tags={"Orders"},
     *      summary="Create a new limit order",
     *      description="Places a new buy or sell limit order, locking the necessary funds or assets.",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/StoreOrderRequest")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Order created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="order", ref="#/components/schemas/OrderResource")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error or insufficient balance",
     *          @OA\JsonContent(
     *              oneOf={
     *                  @OA\Schema(ref="#/components/schemas/ValidationError"),
     *                  @OA\Schema(
     *                      @OA\Property(property="message", type="string", example="Insufficient USD balance")
     *                  )
     *              }
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function create(StoreOrderRequest $req, MatchingService $matcher): JsonResponse
    {
        $data = $req->validated();

        $result = DB::transaction(function () use ($req, $data) {
            /** @var \App\Models\User $user */
            $user = $req->user();

            if ($data['side'] === 'buy') {
                $usdRequired = bcmul((string) $data['amount'], (string) $data['price'], 8);
                // Check
                if (bccomp((string) $user->balance, $usdRequired, 8) === -1) {
                    return response()->json(['message' => 'Insufficient USD balance'], 422);
                }
                // Deduct immediately
                $user->balance = bcsub((string) $user->balance, $usdRequired, 8);
                $user->save();

                $order = Order::create([
                    'user_id' => $user->id,
                    'symbol' => $data['symbol'],
                    'side' => 'buy',
                    'price' => $data['price'],
                    'amount' => $data['amount'],
                    'status' => Order::STATUS_OPEN,
                    'locked_usd' => $usdRequired,
                    'locked_asset' => 0,
                ]);
            } else {
                // SELL
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $data['symbol'])
                    ->lockForUpdate()
                    ->first();

                if (!$asset || bccomp((string) $asset->amount, (string) $data['amount'], 8) === -1) {
                    return response()->json(['message' => 'Insufficient asset balance'], 422);
                }
                // Lock asset
                $asset->amount = bcsub((string) $asset->amount, (string) $data['amount'], 8); // move to locked
                $asset->locked_amount = bcadd((string) $asset->locked_amount, (string) $data['amount'], 8);
                $asset->save();

                $order = Order::create([
                    'user_id' => $user->id,
                    'symbol' => $data['symbol'],
                    'side' => 'sell',
                    'price' => $data['price'],
                    'amount' => $data['amount'],
                    'status' => Order::STATUS_OPEN,
                    'locked_usd' => 0,
                    'locked_asset' => $data['amount'],
                ]);
            }

            // Try match immediately
            // $match = $matcher->tryMatch($order);
            dispatch(new MatchOrderJob($order->id))->onQueue('matching');
            return $order;
        }, 3);

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json([
            'order' => new OrderResource($result->fresh()),
        ]);
    }

    /**
     * @OA\Post(
     *      path="/api/orders/{id}/cancel",
     *      operationId="cancelOrder",
     *      tags={"Orders"},
     *      summary="Cancel an open order",
     *      description="Cancels an open order and releases any locked funds or assets.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="ID of the order to cancel",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Order cancelled successfully",
     *          @OA\JsonContent(ref="#/components/schemas/OrderResource")
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Order not open",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Order not open")
     *          )
     *      ),
     *      @OA\Response(response=404, description="Order not found"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function cancel(Request $req, int $id): OrderResource|JsonResponse
    {
        return DB::transaction(function () use ($req, $id) {
            /** @var \App\Models\User $user */
            $user = $req->user();
            $order = Order::where('id', $id)->lockForUpdate()->first();

            if (!$order || $order->user_id !== $user->id) {
                return response()->json(['message' => 'Order not found'], 404);
            }
            if ($order->status !== Order::STATUS_OPEN) {
                return response()->json(['message' => 'Order not open'], 422);
            }

            if ($order->side === 'buy') {
                // Release USD back to user
                $user->balance = bcadd((string) $user->balance, (string) $order->locked_usd, 8);
                $user->save();
                $order->locked_usd = '0';
            } else {
                // Release asset from locked
                $asset = Asset::where('user_id', $user->id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();

                if ($asset) {
                    $asset->locked_amount = bcsub((string) $asset->locked_amount, (string) $order->locked_asset, 8);
                    $asset->amount = bcadd((string) $asset->amount, (string) $order->locked_asset, 8);
                    $asset->save();
                }
                $order->locked_asset = '0';
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            return new OrderResource($order);
        }, 3);
    }

    /**
     * @OA\Get(
     *      path="/api/orders/all",
     *      operationId="getUserOrders",
     *      tags={"Orders"},
     *      summary="Get all of the authenticated user's orders",
     *      description="Returns a list of all orders (open, filled, cancelled) for the authenticated user.",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/OrderResource")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listAll(Request $req): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = $req->user();
        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
        return OrderResource::collection($orders);
    }
}
