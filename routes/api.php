<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\KelasController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->get('/kelas', [KelasController::class, 'index']);


// 🔹 Route yang hanya bisa diakses oleh admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', function () {
        return response()->json([
            'message' => 'Halo Admin, selamat datang di dashboard!',
        ]);
    });
    Route::post('/admin/register', [AuthController::class, 'register']);
});

// 🔹 Route untuk dosen
Route::middleware(['auth:sanctum', 'role:dosen'])->group(function () {
    Route::get('/dosen/jadwal', function () {
        return response()->json([
            'message' => 'Halo Dosen, ini halaman jadwalmu.',
        ]);
    });
});

// 🔹 Route untuk mahasiswa
Route::middleware(['auth:sanctum', 'role:mahasiswa'])->group(function () {
    Route::get('/mahasiswa/kelas', function () {
        return response()->json([
            'message' => 'Halo Mahasiswa, ini kelas yang kamu booking.',
        ]);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/kelas', [KelasController::class, 'index']);
    Route::post('/reservasi', [ReservasiController::class, 'store']);
    Route::patch('/reservasi/{id}/approve', [ReservasiController::class, 'approve']);
    Route::patch('/reservasi/{id}/reject', [ReservasiController::class, 'reject']);
});
