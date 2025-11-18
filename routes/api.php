<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\KelasController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');
// Mahasiswa
Route::middleware(['auth:sanctum', 'role:mahasiswa'])->group(function () {
    Route::get('/mahasiswa/kelas', [KelasController::class, 'index']);
    Route::post('/reservasi', [ReservasiController::class, 'store']);
});

// Admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/register', [AuthController::class, 'register']);
    Route::patch('/reservasi/{id}/approve', [ReservasiController::class, 'approve']);
    Route::patch('/reservasi/{id}/reject', [ReservasiController::class, 'reject']);
});

// Dosen
Route::middleware(['auth:sanctum', 'role:dosen'])->group(function () {
    Route::get('/dosen/jadwal', function () {
        return response()->json(['message' => 'Halo Dosen']);
    });
});
