<?php

namespace App\Http\Controllers;

use App\Models\Reservasi;
use App\Models\JadwalKelas;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReservasiController extends Controller
{
    public function store(Request $req)
{
    $validated = $req->validate([
        'kelas_id' => 'required|integer',
        'nama' => 'required|string',
        'hari' => 'required|string',
        'tanggal' => 'required|date',
        'jam_mulai' => 'required',
        'jam_selesai' => 'required',
        'alasan' => 'required|string',
    ]);

    // ===== CEK BENTROK DULU =====
    if ($this->adaBentrok(
        $validated['kelas_id'],
        $validated['hari'],
        $validated['jam_mulai'],
        $validated['jam_selesai']
    )) {
        return response()->json([
            'message' => 'Gagal: Ruangan bentrok pada waktu tersebut.'
        ], 409);
    }

    // ===== JIKA AMAN, BARU SIMPAN =====
    $reservasi = Reservasi::create([
        'kelas_id' => $validated['kelas_id'],
        'user_id' => $req->user()->id,
        'nama' => $validated['nama'],
        'Hari' => $validated['hari'],
        'tanggal' => $validated['tanggal'],
        'jam_mulai' => $validated['jam_mulai'],
        'jam_selesai' => $validated['jam_selesai'],
        'alasan' => $validated['alasan'],
        'status' => 'pending',
    ]);

    return response()->json([
        'message' => 'Reservasi berhasil diajukan',
        'data' => $reservasi
    ]);
}

    public function approve($id)
    {
        $res = Reservasi::findOrFail($id);

        if ($res->status !== 'pending') {
            return response()->json(['message' => 'Reservasi tidak valid.'], 400);
        }

        // Cek bentrok jadwal kuliah dan reservasi lain
        if ($this->adaBentrok($res->kelas_id, $res->hari, $res->jam_mulai, $res->jam_selesai)) {
            return response()->json(['message' => 'Gagal approve. Jadwal bentrok.'], 409);
        }

        $res->status = 'approved';
        $res->save();

        return response()->json(['message' => 'Reservasi disetujui.']);
    }

    public function reject($id)
    {
        $res = Reservasi::findOrFail($id);
        $res->status = 'rejected';
        $res->save();

        return response()->json(['message' => 'Reservasi ditolak.']);
    }
    


private function adaBentrok($kelas_id, $hari, $mulai, $selesai)
{
    // $mulai & $selesai dari frontend: "2025-11-24 09:00:00"
    $mulaiDT   = Carbon::parse($mulai);
    $selesaiDT = Carbon::parse($selesai);

    // Untuk jadwal_kelas kita cuma butuh JAM
    $mulaiTime   = $mulaiDT->format('H:i:s');
    $selesaiTime = $selesaiDT->format('H:i:s');

    // Untuk reservasi lain kita butuh TANGGAL yg sama
    $tanggal = $mulaiDT->toDateString();   // "2025-11-24"

    // 1️⃣ Cek bentrok dengan JADWAL KULIAH (berlaku tiap minggu)
    //    -> filter by hari
    //    -> bandingkan JAM saja (TIME), abaikan tanggal
    $jadwalBentrok = JadwalKelas::where('kelas_id', $kelas_id)
        ->whereRaw('LOWER(hari) = ?', [strtolower($hari)])
        ->where(function ($q) use ($mulaiTime, $selesaiTime) {
            $q->whereRaw('TIME(jam_mulai) < ?', [$selesaiTime])
              ->whereRaw('TIME(jam_selesai) > ?', [$mulaiTime]);
        })
        ->exists();

    if ($jadwalBentrok) {
        return true;
    }

    // 2️⃣ Cek bentrok dengan RESERVASI LAIN di tanggal yg sama
    //    -> cuma cek yg pending / approved
    //    -> full datetime overlap
    $reservasiBentrok = Reservasi::where('kelas_id', $kelas_id)
        ->whereDate('jam_mulai', $tanggal)
        ->whereIn('status', ['approved', 'pending'])
        ->where(function ($q) use ($mulaiDT, $selesaiDT) {
            $q->where('jam_mulai', '<', $selesaiDT)
              ->where('jam_selesai', '>', $mulaiDT);
        })
        ->exists();

    return $reservasiBentrok;
}
public function pendingList()
{
    $data = Reservasi::with(['kelas', 'user'])
        ->where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($data);
}

public function history()
{
    $data = Reservasi::with(['kelas', 'user'])
        ->whereIn('status', ['approved', 'rejected'])
        ->orderBy('updated_at', 'desc')
        ->get();

    return response()->json($data);
}




    
}
