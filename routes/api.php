<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/orders', [OrderController::class, 'index']); // open orderbook

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/orders/all', [OrderController::class, 'listAll']); // user’s past
    Route::post('/orders', [OrderController::class, 'create']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});
