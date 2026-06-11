<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return view("welcome");
});

Route::prefix("/transactions")->group(function () {
    Route::get("/create", function () {
        return "test";
    });
});
