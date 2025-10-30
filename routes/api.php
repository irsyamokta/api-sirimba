<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\PriceController;

Route::get('/', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Server is running',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// Authenticate
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Users
Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::patch('/update', [UserController::class, 'update'])->middleware('role:super_admin|admin|member');
    Route::middleware('role:super_admin|admin')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/create', [UserController::class, 'store']);
        Route::patch('/update/{id}', [UserController::class, 'updateUserById']);
        Route::delete('/delete/{id}', [UserController::class, 'destroy']);
    });
});

// Categories
Route::prefix('categories')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::middleware('role:super_admin|admin')->group(function () {
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::post('/create', [CategoryController::class, 'store']);
        Route::patch('/update/{id}', [CategoryController::class, 'update']);
        Route::delete('/delete/{id}', [CategoryController::class, 'destroy']);
    });
});

// Submissions
Route::prefix('submissions')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [SubmissionController::class, 'index']);
    Route::middleware('role:super_admin|admin')->group(function () {
        Route::get('/{id}', [SubmissionController::class, 'show']);
        Route::post('/create', [SubmissionController::class, 'store']);
        Route::patch('/update/{id}', [SubmissionController::class, 'update']);
        Route::delete('/delete/{id}', [SubmissionController::class, 'destroy']);
    });
});

// Prices
Route::prefix('prices')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/', [PriceController::class, 'index']);
    Route::middleware('role:super_admin|admin')->group(function () {
        Route::get('/{id}', [PriceController::class, 'show']);
        Route::post('/create', [PriceController::class, 'store']);
        Route::patch('/update/{id}', [PriceController::class, 'update']);
        Route::delete('/delete/{id}', [PriceController::class, 'destroy']);
    });
});
