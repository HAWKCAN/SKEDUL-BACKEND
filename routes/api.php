<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservasiController;
use App\Http\Controllers\KelasController;
use App\Http\Controllers\JadwalKelasController;
use App\Models\User;
use App\Models\Kelas;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


// ---------------------------
// MAHASISWA
// ---------------------------
Route::middleware(['auth:sanctum', 'role:mahasiswa'])->group(function () {

    Route::get('/mahasiswa/kelas', [KelasController::class, 'index']);
    Route::get('/mahasiswa/kelas/{id}', [KelasController::class, 'show']);
    Route::get('/mahasiswa/kelas/{id}/availability', [KelasController::class, 'availability']);

    Route::post('/mahasiswa/reservasi', [ReservasiController::class, 'store']);
    Route::get('/mahasiswa/reservasi', [ReservasiController::class, 'reservasiMahasiswa']);
    Route::patch('/mahasiswa/reservasi/{id}/cancel', [ReservasiController::class, 'cancel']);

});

// ---------------------------
// DOSEN
// ---------------------------
Route::middleware(['auth:sanctum', 'role:dosen'])->group(function () {

    Route::get('/dosen/kelas', [KelasController::class, 'kelasDosen']); 
    Route::delete('/dosen/kelas/{id}/cancel', [KelasController::class, 'cancelKelas']);
    Route::get('/dosen/kelas-pengganti', [KelasController::class, 'kelasPengganti']);


Route::get('/dosen/kelas-tersedia', [KelasController::class, 'kelasTersedia']);


    Route::post('/dosen/reservasi', [ReservasiController::class, 'store']);
    Route::get('/dosen/reservasi', [ReservasiController::class, 'reservasiDosen']);
});

// ---------------------------
// ADMIN
// ---------------------------
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/admin/register', [AuthController::class, 'register']);
    Route::get('/admin/reservasi/pending', [ReservasiController::class, 'pendingList']);
    Route::get('/admin/reservasi/history', [ReservasiController::class, 'history']);
    Route::patch('/admin/reservasi/{id}/approve', [ReservasiController::class, 'approve']);
    Route::patch('/admin/reservasi/{id}/reject', [ReservasiController::class, 'reject']);
    Route::delete('/admin/reservasi/history/reset', [ReservasiController::class, 'resetHistory']);

    Route::post('/admin/jadwal-kelas', [JadwalKelasController::class, 'store']);
    Route::get('/admin/jadwal-kelas', [JadwalKelasController::class, 'index']);

    Route::get('/admin/dosen', fn () =>
        \App\Models\User::where('role', 'dosen')->get()
    );

    Route::get('/admin/kelas', fn () =>
        \App\Models\Kelas::all()
    );
        Route::get('/admin/jadwal-kelas/dosen', function () {
        return User::where('role', 'dosen')
            ->select('id', 'name')
            ->get();
    });

    Route::get('/admin/jadwal-kelas/kelas', function () {
        return Kelas::select('id', 'nama_kelas', 'lokasi')
            ->get();
    });
});
