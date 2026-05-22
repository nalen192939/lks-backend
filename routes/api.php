<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AtkController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\KeteranganController;
use App\Http\Controllers\StockHistoryController;
use App\Http\Controllers\TematikController;
use Illuminate\Support\Facades\Storage;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Route untuk Data
Route::apiResource('data', DataController::class);

Route::get('/cover-image', function (Request $request) {
    $path = trim((string) $request->query('path', ''));
    $path = str_replace('\\', '/', $path);
    $path = preg_replace('#^/?storage/#', '', $path);
    $path = ltrim($path, '/\\');

    if ($path === '' || str_contains($path, '..') || !str_starts_with($path, 'uploads/')) {
        abort(404);
    }

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return response()->file(Storage::disk('public')->path($path), [
        'Access-Control-Allow-Origin' => '*',
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

Route::get('/stock-histories', [StockHistoryController::class, 'index']);

// Route untuk Keterangan dengan explicit binding
Route::get('/keterangan', [KeteranganController::class, 'index']);
Route::post('/keterangan', [KeteranganController::class, 'store']);
Route::get('/keterangan/{nomor_rak}', [KeteranganController::class, 'show']);
Route::put('/keterangan/{nomor_rak}', [KeteranganController::class, 'update']);
Route::delete('/keterangan/{nomor_rak}', [KeteranganController::class, 'destroy']);

// Route untuk Barang
Route::get('/barang', [BarangController::class, 'index']);
Route::post('/barang', [BarangController::class, 'store']);
Route::get('/barang/{id}', [BarangController::class, 'show']);
Route::put('/barang/{id}', [BarangController::class, 'update']);
Route::delete('/barang/{id}', [BarangController::class, 'destroy']);

// Route untuk ATK
Route::get('/atk', [AtkController::class, 'index']);
Route::post('/atk', [AtkController::class, 'store']);
Route::get('/atk/{id}', [AtkController::class, 'show']);
Route::put('/atk/{id}', [AtkController::class, 'update']);
Route::delete('/atk/{id}', [AtkController::class, 'destroy']);

// Route untuk Tematik
Route::get('/tematik', [TematikController::class, 'index']);
Route::post('/tematik', [TematikController::class, 'store']);
Route::get('/tematik/{id}', [TematikController::class, 'show']);
Route::put('/tematik/{id}', [TematikController::class, 'update']);
Route::delete('/tematik/{id}', [TematikController::class, 'destroy']);
