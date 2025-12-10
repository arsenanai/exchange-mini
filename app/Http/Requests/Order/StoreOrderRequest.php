<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @OA\Schema(
 *     title="StoreOrderRequest",
 *     description="Request body for creating a new order",
 *     required={"symbol", "side", "price", "amount"}
 * )
 */
class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled by auth:sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    /**
     * @OA\Property(property="symbol", type="string", enum={"BTC", "ETH"}, example="BTC")
     * @OA\Property(property="side", type="string", enum={"buy", "sell"}, example="buy")
     * @OA\Property(property="price", type="number", format="float", description="Price per unit", example="50000.00")
     * @OA\Property(property="amount", type="number", format="float", description="Amount of the asset to trade", example="0.1")
     */
    public function rules(): array
    {
        return [
            'symbol' => 'required|in:BTC,ETH',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ];
    }
}