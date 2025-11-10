<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/tes-db', function () {
    try {
        DB::connection()->getPdo();
        return '✅ Koneksi ke database berhasil!';
    } catch (\Exception $e) {
        return '❌ Gagal konek ke database: ' . $e->getMessage();
    }
});

