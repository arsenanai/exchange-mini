<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Jobs\MatchOrderJob;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    /**
     * @OA\Get(
     *      path="/api/orders",
     *      operationId="getOrderBook",
     *      tags={"Orders"},
     *      summary="Get open orders for the order book",
     *      description="Returns a list of all open buy and sell orders. Can be filtered by symbol.",
     *
     *      @OA\Parameter(
     *          name="symbol",
     *          in="query",
     *          description="Filter orders by symbol (e.g., BTC, ETH)",
     *          required=false,
     *
     *          @OA\Schema(type="string")
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(properties={
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/OrderResource"))
     *          }
     *          )
     *      )
     * )
     */
    public function index(Request $req): AnonymousResourceCollection
    {
        $orders = $this->orderService->getOpenOrders(
            $req->query('symbol')
        );
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
     *
     *      @OA\RequestBody(
     *          required=true,
     *
     *          @OA\JsonContent(ref="#/components/schemas/StoreOrderRequest")
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Order created successfully",
     *
     *          @OA\JsonContent(
     *              @OA\Property(property="data", ref="#/components/schemas/OrderResource")
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Validation error or insufficient balance",
     *
     *          @OA\JsonContent(
     *              oneOf={
     *
     *                  @OA\Schema(ref="#/components/schemas/ValidationError"),
     *                  @OA\Schema(
     *
     *                      @OA\Property(property="message", type="string", example="Insufficient USD balance")
     *                  )
     *              }
     *          )
     *      ),
     *
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function create(StoreOrderRequest $req): OrderResource
    {
        /** @var \App\Models\User $user */
        $user = $req->user();
        $order = $this->orderService->createOrder($req->validated(), $user);

        dispatch(new MatchOrderJob($order->id))->onQueue('matching');
        return new OrderResource($order->fresh());
    }

    /**
     * @OA\Post(
     *      path="/api/orders/{id}/cancel",
     *      operationId="cancelOrder",
     *      tags={"Orders"},
     *      summary="Cancel an open order",
     *      description="Cancels an open order and releases any locked funds or assets.",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="ID of the order to cancel",
     *          required=true,
     *
     *          @OA\Schema(type="integer")
     *      ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Order cancelled successfully",
     *
     *          @OA\JsonContent(
     *              properties={
     *                  @OA\Property(property="data", ref="#/components/schemas/OrderResource"),
     *              }
     *          )
     *      ),
     *
     *      @OA\Response(
     *          response=422,
     *          description="Order not open",
     *
     *          @OA\JsonContent(
     *
     *              @OA\Property(property="message", type="string", example="Order not open")
     *          )
     *      ),
     *
     *      @OA\Response(response=404, description="Order not found"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function cancel(Request $req, int $id): OrderResource
    {
        /** @var \App\Models\User $user */
        $user = $req->user();

        $order = $this->orderService->cancelOrder($id, $user);

        return new OrderResource($order);
    }

    /**
     * @OA\Get(
     *      path="/api/orders/all",
     *      operationId="getUserOrders",
     *      tags={"Orders"},
     *      summary="Get all of the authenticated user's orders",
     *      description="Returns a list of all orders (open, filled, cancelled) for the authenticated user.",
     *      security={{"bearerAuth":{}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *
     *          @OA\JsonContent(properties={
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/OrderResource"))
     *          }
     *          )
     *      ),
     *
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function listAll(Request $req): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = $req->user();
        $orders = $this->orderService->getUserOrders($user);
        return OrderResource::collection($orders);
    }
}
