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

  public function availability(Request $request, $id)
{
    $hari = $request->query('hari');      
    $tanggal = $request->query('tanggal'); 


    $jadwal = DB::table('jadwal_kelas')
        ->where('kelas_id', $id)
        ->where('hari', $hari)
        ->where('is_canceled', 0)
        ->get();


    $reservasi = DB::table('reservasi')
        ->where('kelas_id', $id)
        ->whereDate('tanggal', $tanggal)
        ->where('status', 'approved')
        ->get();

    $slots = [];

  for ($jam = 7; $jam < 17; $jam++) {
    $mulai = sprintf('%02d:00', $jam);
    $selesai = sprintf('%02d:00', $jam + 1);

    $status = 'kosong';

    // === CEK JADWAL MATKUL (HARI SAJA) ===
    foreach ($jadwal as $j) {
        $jm = substr($j->jam_mulai, 11, 5);   // ambil jam saja
        $js = substr($j->jam_selesai, 11, 5);

        if ($mulai < $js && $selesai > $jm) {
            $status = 'dipakai';
            break;
        }
    }

    // === CEK RESERVASI (TANGGAL + JAM) ===
    if ($status === 'kosong') {
        foreach ($reservasi as $r) {
            $rm = substr($r->jam_mulai, 11, 5);
            $rs = substr($r->jam_selesai, 11, 5);

            if ($mulai < $rs && $selesai > $rm) {
                $status = 'dipakai';
                break;
            }
        }
    }

    $slots[] = [
        'jam_mulai' => $mulai,
        'jam_selesai' => $selesai,
        'status' => $status,
    ];
}

    return response()->json([
        'hari' => $hari,
        'tanggal' => $tanggal,
        'slots' => $slots
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

public function cancelKelas($id)
{
    $kelas = JadwalKelas::findOrFail($id);

    $kelas->is_canceled = 1;
    $kelas->save();

    return response()->json([
        'message' => 'Kelas dibatalkan',
        'kelas_id' => $kelas->kelas_id,
        'hari' => $kelas->hari,
        'jam_mulai' => $kelas->jam_mulai,
        'jam_selesai' => $kelas->jam_selesai
    ]);
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
public function kelasPengganti(Request $req)
{
    $kelasId = $req->query('kelas_id');
    $hari = $req->query('hari');
    $mulai = $req->query('jam_mulai');
    $selesai = $req->query('jam_selesai');

    // Ambil semua kelas
    $kelas = Kelas::all();

    // Hilangkan kelas yang sama
    $kelas = $kelas->filter(fn($k) => $k->id != $kelasId);

    // Cari kelas yang kosong
    $available = [];
    foreach ($kelas as $k) {
        $dipakai = DB::table('reservasi')
            ->where('kelas_id', $k->id)
            ->where('hari', $hari)
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai)
            ->exists();

        if (!$dipakai) {
            $available[] = $k;
        }
    }

    return response()->json($available);
}


}

