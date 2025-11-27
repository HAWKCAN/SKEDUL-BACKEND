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
            'hari'     => 'required|string',
            'tanggal'  => 'required|date',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required',
            'alasan' => 'required|string',
        ]);

        // CEK BENTROK
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

        // SIMPAN
        $reservasi = Reservasi::create([
            'kelas_id'   => $validated['kelas_id'],
            'user_id'    => $req->user()->id,
            'hari'       => $validated['hari'],
            'tanggal'    => $validated['tanggal'],
            'jam_mulai'  => $validated['jam_mulai'],
            'jam_selesai'=> $validated['jam_selesai'],
            'alasan'     => $validated['alasan'],
            'status'     => 'pending',
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
        $mulaiDT   = Carbon::parse($mulai);
        $selesaiDT = Carbon::parse($selesai);

        $mulaiTime   = $mulaiDT->format('H:i:s');
        $selesaiTime = $selesaiDT->format('H:i:s');

        $tanggal = $mulaiDT->toDateString();

        // Periksa jadwal kuliah mingguan
        $jadwalBentrok = JadwalKelas::where('kelas_id', $kelas_id)
            ->whereRaw('LOWER(hari) = ?', [strtolower($hari)])
            ->whereRaw('TIME(jam_mulai) < ?', [$selesaiTime])
            ->whereRaw('TIME(jam_selesai) > ?', [$mulaiTime])
            ->exists();

        if ($jadwalBentrok) return true;

        // Periksa reservasi lain di tanggal yang sama
        $reservasiBentrok = Reservasi::where('kelas_id', $kelas_id)
            ->whereDate('jam_mulai', $tanggal)
            ->whereIn('status', ['approved', 'pending'])
            ->where('jam_mulai', '<', $selesaiDT)
            ->where('jam_selesai', '>', $mulaiDT)
            ->exists();

        return $reservasiBentrok;
    }

    public function pendingList()
    {
        return Reservasi::with(['kelas', 'user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function history()
    {
        return Reservasi::with(['kelas', 'user'])
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }
}
