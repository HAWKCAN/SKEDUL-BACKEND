<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalKelas extends Model
{
    use HasFactory;

    protected $table = 'jadwal_kelas';
        const UPDATED_AT = 'update_at';

    protected $fillable = [
        'kelas_id',
        'user_id',
        'mata_kuliah',
        'jam_mulai',
        'jam_selesai',
        'hari',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    public function dosen()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
