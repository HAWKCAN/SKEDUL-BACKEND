<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservasiController extends Controller
{
    public function store(Request $req)
    {
        $req->validate([
            'kelas_id' => 'required|int',
            'jam_mulai' => 'required|date',
            'jam_selesai' => 'required|date|after:jam_mulai'
        ]);

        if (adaBentrok($req->kelas_id, $req->jam_mulai, $req->jam_selesai)) {
            return response()->json(['message' => 'Bentrok jadwal.'], 409);
        }

        $res = Reservasi::create([
            'kelas_id' => $req->kelas_id,
             'user_id' => $req->user()->id,
            'jam_mulai' => $req->jam_mulai,
            'jam_selesai' => $req->jam_selesai,
            'alasan' => $req->alasan,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Reservasi dibuat.', 'data' => $res]);
    }

    public function approve($id)
    {
        $res = Reservasi::findOrFail($id);

        if ($res->status !== 'pending') {
            return response()->json(['message' => 'Reservasi tidak valid.'], 400);
        }

        if (adaBentrok($res->kelas_id, $res->jam_mulai, $res->jam_selesai)) {
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
}
