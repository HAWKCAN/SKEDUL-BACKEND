<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\JadwalKelas;
use App\Models\Reservasi;
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
    $hari = strtolower($req->query('hari'));
    if (!$hari) {
        return response()->json(['message' => 'Parameter hari wajib'], 400);
    }

    $kelas = Kelas::find($id);
    if (!$kelas) {
        return response()->json(['message' => 'Kelas tidak ditemukan'], 404);
    }

    // jam operasional
    $open = strtotime("07:00");
    $close = strtotime("17:00");

    // Ambil jadwal kuliah hari itu
    $jadwal = JadwalKelas::where('kelas_id', $id)
        ->whereRaw('LOWER(hari) = ?', [$hari])
        ->orderBy('jam_mulai')
        ->get();

    // Ambil reservasi yang approved
    $reservasi = Reservasi::where('kelas_id', $id)
        ->whereRaw('LOWER(hari) = ?', [$hari])
        ->where('status', 'approved')
        ->orderBy('jam_mulai')
        ->get();

    $slots = [];


    foreach ($jadwal as $j) {
        $slots[] = [
            "jam_mulai" => date("H:i", strtotime($j->jam_mulai)),
            "jam_selesai" => date("H:i", strtotime($j->jam_selesai)),
            "status" => "Dipakai (Kuliah)"
        ];
    }

    foreach ($reservasi as $r) {
        $slots[] = [
            "jam_mulai" => date("H:i", strtotime($r->jam_mulai)),
            "jam_selesai" => date("H:i", strtotime($r->jam_selesai)),
            "status" => "Dipakai (Reservasi)"
        ];
    }

 
    // Merge jadwal & reservasi biar urut
    $all = collect($slots)->sortBy('jam_mulai')->values();

    $lastEnd = $open;

    foreach ($all as $item) {
        $start = strtotime($item['jam_mulai']);
        if ($lastEnd < $start) {
            // Masih tersedia
            $slots[] = [
                "jam_mulai" => date("H:i", $lastEnd),
                "jam_selesai" => date("H:i", $start),
                "status" => "Tersedia"
            ];
        }
        $lastEnd = max($lastEnd, strtotime($item['jam_selesai']));
    }

    if ($lastEnd < $close) {
        $slots[] = [
            "jam_mulai" => date("H:i", $lastEnd),
            "jam_selesai" => date("H:i", $close),
            "status" => "Tersedia"
        ];
    }

    // 5️⃣ urutkan hasil final
    $slots = collect($slots)->sortBy('jam_mulai')->values();

    return response()->json([
        "kelas" => $kelas->nama_kelas,
        "hari" => ucfirst($hari),
        "slots" => $slots
    ]);
}




}
