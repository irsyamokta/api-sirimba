<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Server is running',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
