<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\JadwalKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{

    public function index()
    {
        $now = now();
        $kelas = Kelas::all();

        // Reservasi aktif
        $reservasiAktif = DB::table('reservasi')
            ->where('status', 'approved')
            ->where('jam_mulai', '<=', $now)
            ->where('jam_selesai', '>=', $now)
            ->get()
            ->keyBy('kelas_id');

        // Jadwal aktif
        $jadwalAktif = DB::table('jadwal_kelas')
            ->where('jam_mulai', '<=', $now)
            ->where('jam_selesai', '>=', $now)
            ->get()
            ->keyBy('kelas_id');

        foreach ($kelas as $k) {
            if (isset($jadwalAktif[$k->id])) {
                $j = $jadwalAktif[$k->id];
                $k->status = "dipakai";
                $k->dipakai_oleh = $j->user_id;
                $k->jam_mulai = $j->jam_mulai;
                $k->jam_selesai = $j->jam_selesai;
                $k->hari = $j->hari;
            }
            elseif (isset($reservasiAktif[$k->id])) {
                $r = $reservasiAktif[$k->id];
                $k->status = "dipakai";
                $k->dipakai_oleh = $r->user_id;
                $k->jam_mulai = $r->jam_mulai;
                $k->jam_selesai = $r->jam_selesai;
                $k->hari = $r->hari;
            }
            else {
                $k->status = "tersedia";
                $k->dipakai_oleh = null;
                $k->jam_mulai = null;
                $k->jam_selesai = null;
                $k->hari = null;
            }
        }

        return response()->json($kelas);
    }


    public function show($id)
    {
        $kelas = Kelas::find($id);
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

        $now = now();

        $reservasiAktif = DB::table('reservasi')
            ->where('kelas_id', $id)
            ->where('status', 'approved')
            ->where('jam_mulai', '<=', $now)
            ->where('jam_selesai', '>=', $now)
            ->first();

        $jadwalAktif = DB::table('jadwal_kelas')
            ->where('kelas_id', $id)
            ->where('jam_mulai', '<=', $now)
            ->where('jam_selesai', '>=', $now)
            ->first();

        if ($jadwalAktif) {
            $kelas->status = "dipakai";
            $kelas->dipakai_oleh = $jadwalAktif->user_id;
            $kelas->jam_mulai = $jadwalAktif->jam_mulai;
            $kelas->jam_selesai = $jadwalAktif->jam_selesai;
        }
        elseif ($reservasiAktif) {
            $kelas->status = "dipakai";
            $kelas->dipakai_oleh = $reservasiAktif->user_id;
            $kelas->jam_mulai = $reservasiAktif->jam_mulai;
            $kelas->jam_selesai = $reservasiAktif->jam_selesai;
        }
        else {
            $kelas->status = "tersedia";
        }

        return response()->json($kelas);
    }

    public function availability(Request $req, $id)
    {
        $hari = $req->query('hari');
        if (!$hari) return response()->json(['message' => 'Parameter hari wajib'], 400);

        $kelas = Kelas::find($id);
        if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

        $open = "07:00";
        $close = "17:00";

        $jadwal = DB::table('jadwal_kelas')
            ->where('kelas_id', $id)
            ->where('hari', $hari)
            ->get();

        $reservasi = DB::table('reservasi')
            ->where('kelas_id', $id)
            ->where('hari', $hari)
            ->where('status', 'approved')
            ->get();

        $slots = [];
        for ($t = strtotime($open); $t < strtotime($close); $t += 3600) {
            $mulai = date("H:i", $t);
            $selesai = date("H:i", $t + 3600);

            $status = "kosong";

            foreach ($jadwal as $j) {
                if ($mulai < date("H:i", strtotime($j->jam_selesai)) && 
                    $selesai > date("H:i", strtotime($j->jam_mulai))) {
                    $status = "dipakai";
                }
            }

            foreach ($reservasi as $r) {
                if ($mulai < date("H:i", strtotime($r->jam_selesai)) && 
                    $selesai > date("H:i", strtotime($r->jam_mulai))) {
                    $status = "dipakai";
                }
            }

            $slots[] = [
                "jam_mulai" => $mulai,
                "jam_selesai" => $selesai,
                "status" => $status
            ];
        }

        return response()->json([
            "kelas" => $kelas->nama_kelas,
            "hari" => $hari,
            "slots" => $slots
        ]);
    }

 public function kelasDosen(Request $req)
{
    // semua jadwal kelas milik dosen yg login
    return JadwalKelas::with('kelas')
        ->where('user_id', $req->user()->id)
        ->orderBy('hari')
        ->orderBy('jam_mulai')
        ->get();
}

public function cancelKelas(Request $req, $id)
{
    // pastikan dosen cuma bisa batalin jadwal miliknya sendiri
    $jadwal = JadwalKelas::where('id', $id)
        ->where('user_id', $req->user()->id)
        ->firstOrFail();

    $jadwal->delete();

    return response()->json(['message' => 'Kelas berhasil dibatalkan']);
}
public function kelasTersedia(Request $req)
{
    $now = now();

    $kelas = Kelas::all();

    $jadwalDosen = DB::table('jadwal_kelas')
        ->where('user_id', $req->user()->id)
        ->pluck('kelas_id');

    // Jangan tampilkan kelas yang sedang dipakai dosen pada jadwal normalnya
    foreach ($kelas as $k) {
        if ($jadwalDosen->contains($k->id)) {
            $k->tidak_boleh_dipinjam = true;
        } else {
            $k->tidak_boleh_dipinjam = false;
        }
    }

    return response()->json($kelas);
}


}

