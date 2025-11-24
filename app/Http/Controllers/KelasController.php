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
public function show($id)
{
    $kelas = Kelas::find($id);

    if (!$kelas) {
        return response()->json(['message' => 'Kelas tidak ditemukan'], 404);
    }

    // ambil waktu sekarang
    $now = now();

    // CARI RESERVASI AKTIF
    $reservasiAktif = DB::table('reservasi')
        ->where('kelas_id', $id)
        ->where('status', 'approved')
        ->where('jam_mulai', '<=', $now)
        ->where('jam_selesai', '>=', $now)
        ->first();

    // CARI JADWAL AKTIF
    $jadwalAktif = DB::table('jadwal_kelas')
        ->where('kelas_id', $id)
        ->where('jam_mulai', '<=', $now)
        ->where('jam_selesai', '>=', $now)
        ->first();

    // SAMAKAN LOGIC DENGAN INDEX
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
        $kelas->dipakai_oleh = null;
        $kelas->jam_mulai = null;
        $kelas->jam_selesai = null;
    }

    return response()->json($kelas);
}
public function availability(Request $req, $id)
{
    $hari = $req->query('hari');
    if (!$hari) return response()->json(['message' => 'Hari wajib'], 400);

    $kelas = Kelas::find($id);
    if (!$kelas) return response()->json(['message' => 'Kelas tidak ditemukan'], 404);

    // Jam operasional
    $open = "07:00";
    $close = "17:00";

    // Ambil jadwal dosen
$jadwal = JadwalKelas::where('kelas_id', $id)
    ->whereRaw('LOWER(hari) = ?', [strtolower($hari)])
    ->orderBy('jam_mulai')
    ->get();


    // ====== Step 1: Buat list interval terpakai (asli db) ======
    $blocked = [];
    foreach ($jadwal as $j) {
        $blocked[] = [
            "mulai" => substr($j->jam_mulai, 11, 5),
            "selesai" => substr($j->jam_selesai, 11, 5),
            "status" => "dipakai",
            "jenis" => "jadwal"
        ];
    }

    // ====== Step 2: Buat slot kosong per jam ======
    $slots = [];
    $start = strtotime($open);
    $end = strtotime($close);

    for ($t = $start; $t < $end; $t += 3600) {
        $mulai = date("H:i", $t);
        $selesai = date("H:i", $t + 3600);

        // Cek apakah slot bentrok dengan blok asli
        $isBlocked = false;
        foreach ($blocked as $b) {
            if (!($selesai <= $b["mulai"] || $mulai >= $b["selesai"])) {
                $isBlocked = true;
                break;
            }
        }

        if (!$isBlocked) {
            $slots[] = [
                "mulai" => $mulai,
                "selesai" => $selesai,
                "status" => "tersedia",
                "jenis" => "slot"
            ];
        }
    }

    // ====== Gabungkan semua ======
    $result = array_merge($blocked, $slots);

    usort($result, function($a, $b) {
        return strcmp($a["mulai"], $b["mulai"]);
    });

    return response()->json([
        "kelas" => $kelas->nama_kelas,
        "hari" => $hari,
        "slots" => $result
    ]);
}


}
