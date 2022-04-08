<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['AllowCorsPolicy', 'checkKey']], function() {
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    Route::get('/trades', [OrderController::class, 'trades']);
    Route::get('/sell-orders', [OrderController::class, 'sellOrders']);

    Route::post('/buy', [OrderController::class, 'store']);
    Route::put('/sell/{id}', [OrderController::class, 'sell']);

    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'edit']);

    Route::get('/status', [SettingsController::class, 'status']);
    Route::put('/status/{status}', [SettingsController::class, 'editStatus']);

    Route::get('/reply-to-message', [SettingsController::class, 'replyToMessage']);
    Route::put('/reply-to-message/{replyToMessage}', [SettingsController::class, 'editReplyToMessage']);

    Route::get('/report', [OrderController::class, 'report']);
});

