<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JadwalKelas extends Model
{
    protected $table = 'jadwal_kelas';

    protected $fillable = [
        'kelas_id', 'user_id',
        'mata_kuliah',
        'jam_mulai', 'jam_selesai',
        'Hari'

    ];
}
