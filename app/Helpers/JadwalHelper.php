<?php

use Illuminate\Support\Facades\DB;

function adaBentrok($kelas_id, $mulai, $selesai)
{
    // Bentrok dengan jadwal dosen
    $jadwal = DB::table('jadwal_kelas')
        ->where('kelas_id', $kelas_id)
        ->where(function ($q) use ($mulai, $selesai) {
            $q->where('jam_mulai', '<=', $selesai)
              ->where('jam_selesai', '>=', $mulai);
        })
        ->exists();

    if ($jadwal) return true;

    // Bentrok dengan reservasi approved
    $reservasi = DB::table('reservasi')
        ->where('kelas_id', $kelas_id)
        ->where('status', 'approved')
        ->where(function ($q) use ($mulai, $selesai) {
            $q->where('jam_mulai', '<=', $selesai)
              ->where('jam_selesai', '>=', $mulai);
        })
        ->exists();

    return $reservasi;
}
