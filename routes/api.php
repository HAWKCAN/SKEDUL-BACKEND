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
    Route::get('/mahasiswa/kelas/{id}', [KelasController::class, 'show']); 
    Route::post('/reservasi', [ReservasiController::class, 'store']);
    Route::get('/mahasiswa/reservasi', [ReservasiController::class, 'reservasiMahasiswa']);
    Route::get('/kelas/{id}/availability', [KelasController::class, 'availability']);

});


// Admin
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/register', [AuthController::class, 'register']);
      Route::get('/admin/reservasi/pending', [ReservasiController::class, 'pendingList']);
    Route::get('/admin/reservasi/history', [ReservasiController::class, 'history']);
    Route::patch('/admin/reservasi/{id}/approve', [ReservasiController::class, 'approve']);
    Route::patch('/admin/reservasi/{id}/reject', [ReservasiController::class, 'reject']);
    Route::delete('/admin/reservasi/history/reset', [ReservasiController::class, 'resetHistory']);

});

// Dosen
Route::middleware(['auth:sanctum', 'role:dosen'])->group(function () {
    Route::get('/dosen/jadwal', function () {
        return response()->json(['message' => 'Halo Dosen']);
    });
});
