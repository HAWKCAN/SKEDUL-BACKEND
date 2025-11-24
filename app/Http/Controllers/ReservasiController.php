<?php

namespace App\Http\Controllers;

use App\Models\Reservasi;
use App\Models\JadwalKelas;
use Illuminate\Http\Request;

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

        $reservasi = Reservasi::create([
            'kelas_id' => $validated['kelas_id'],
             'user_id' => $req->user()->id,
            'nama' => $validated['nama'],
            'hari' => $validated['hari'],
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
        // Cek bentrok dengan jadwal kuliah
        $jadwalBentrok = JadwalKelas::where('kelas_id', $kelas_id)
            ->where('hari', $hari)
            ->where(function ($q) use ($mulai, $selesai) {
                $q->whereBetween('jam_mulai', [$mulai, $selesai])
                  ->orWhereBetween('jam_selesai', [$mulai, $selesai])
                  ->orWhere(function ($q2) use ($mulai, $selesai) {
                      $q2->where('jam_mulai', '<=', $mulai)
                         ->where('jam_selesai', '>=', $selesai);
                  });
            })
            ->exists();

        if ($jadwalBentrok) return true;

        // Cek bentrok dengan reservasi lain yang sudah approved
        $reservasiBentrok = Reservasi::where('kelas_id', $kelas_id)
            ->where('hari', $hari)
            ->where('status', 'approved')
            ->where(function ($q) use ($mulai, $selesai) {
                $q->whereBetween('jam_mulai', [$mulai, $selesai])
                  ->orWhereBetween('jam_selesai', [$mulai, $selesai])
                  ->orWhere(function ($q2) use ($mulai, $selesai) {
                      $q2->where('jam_mulai', '<=', $mulai)
                         ->where('jam_selesai', '>=', $selesai);
                  });
            })
            ->exists();

        return $reservasiBentrok;
    }

    
}
