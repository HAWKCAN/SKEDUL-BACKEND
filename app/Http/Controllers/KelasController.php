<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function index()
{
    $now = now();

    // Ambil semua kelas
    $kelas = Kelas::all();

    // Ambil reservasi aktif (approved dan sedang berlangsung)
    $reservasiAktif = DB::table('reservasi')
        ->where('status', 'approved')
        ->where('jam_mulai', '<=', $now)
        ->where('jam_selesai', '>=', $now)
        ->where('Hari','>=',$now)
        ->get()
        ->keyBy('kelas_id');

    // Ambil jadwal aktif dosen
    $jadwalAktif = DB::table('jadwal_kelas')
        ->where('jam_mulai', '<=', $now)
        ->where('jam_selesai', '>=', $now)
        ->where('Hari','>=',$now)
        ->get()
        ->keyBy('kelas_id');

    // Tentukan status kelas
    foreach ($kelas as $k) {
        if (isset($jadwalAktif[$k->id])) {
            $j = $jadwalAktif[$k->id];
            $k->status = "dipakai";
            $k->dipakai_oleh = $j->user_id;
            $k->jam_mulai = $j->jam_mulai;
            $k->jam_selesai = $j->jam_selesai;
            $k->Hari = $j->Hari;

        } elseif (isset($reservasiAktif[$k->id])) {
            $r = $reservasiAktif[$k->id];
            $k->status = "dipakai";
            $k->dipakai_oleh = $r->user_id;
            $k->jam_mulai = $r->jam_mulai;
            $k->jam_selesai = $r->jam_selesai;
            $k->Hari = $r->Hari;


        } else {
            $k->status = "tersedia";
            $k->dipakai_oleh = null;
            $k->jam_mulai = null;
            $k->jam_selesai = null;
            $k->Hari = null;
        }
    }

    return response()->json($kelas);
}

}
