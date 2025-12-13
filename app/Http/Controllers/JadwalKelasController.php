<?php

namespace App\Http\Controllers;

use App\Models\JadwalKelas;
use Illuminate\Http\Request;

class JadwalKelasController extends Controller
{
    public function index()
    {
        return JadwalKelas::with(['kelas', 'dosen'])
            ->orderBy('hari')
            ->orderBy('jam_mulai')
            ->get();
    }

  public function store(Request $request)
{
    $request->validate([
        'kelas_id' => 'required|exists:kelas,id',
        'user_id' => 'required|exists:users,id',
        'mata_kuliah' => 'required|string',
        'hari' => 'required|string',
        'jam_mulai' => 'required',
        'jam_selesai' => 'required',
    ]);

    // ambil tanggal hari ini (jadwal rutin)
    $tanggal = now()->toDateString();

    JadwalKelas::create([
        'kelas_id' => $request->kelas_id,
        'user_id' => $request->user_id,
        'mata_kuliah' => $request->mata_kuliah,
        'hari' => $request->hari,
        'jam_mulai' => $tanggal . ' ' . $request->jam_mulai,
        'jam_selesai' => $tanggal . ' ' . $request->jam_selesai,
    ]);

    return response()->json([
        'message' => 'Jadwal kelas berhasil ditambahkan'
    ], 201);
}
}
