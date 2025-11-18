<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function index()
    {
        $now = now();

        $kelas = Kelas::all()->map(function ($k) use ($now) {

            // cek jadwal dosen yg sedang berlangsung
            $jadwal = DB::table('jadwal_kelas')
                ->where('kelas_id', $k->id)
                ->where('jam_mulai', '<=', $now)
                ->where('jam_selesai', '>=', $now)
                ->first();

            // cek reservasi approved yg sedang berlangsung
            $reservasi = DB::table('reservasi')
                ->where('kelas_id', $k->id)
                ->where('status', 'approved')
                ->where('jam_mulai', '<=', $now)
                ->where('jam_selesai', '>=', $now)
                ->first();

            // tentukan status realtime
            if ($jadwal) {
                $k->status = "dipakai";
                $k->dipakai_oleh = $jadwal->user_id;
                $k->jam_mulai = $jadwal->jam_mulai;
                $k->jam_selesai = $jadwal->jam_selesai;

            } elseif ($reservasi) {
                $k->status = "dipakai";
                $k->dipakai_oleh = $reservasi->user_id;
                $k->jam_mulai = $reservasi->jam_mulai;
                $k->jam_selesai = $reservasi->jam_selesai;

            } else {
                $k->status = "tersedia";
                $k->dipakai_oleh = null;
                $k->jam_mulai = null;
                $k->jam_selesai = null;
            }

            return $k;
        });

        return response()->json($kelas);
    }
}
