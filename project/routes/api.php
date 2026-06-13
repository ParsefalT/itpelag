<?php

use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(["auth.basic", "throttle:60,1"])->prefix("v1")->group(function (): void {
    Route::apiResource("transactions", TransactionApiController::class);
    Route::get("accounts", [AccountApiController::class, "index"]);
    Route::get("accounts/{account}/balance", [AccountApiController::class, "balance"]);
});
