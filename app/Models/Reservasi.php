<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservasi extends Model
{
    protected $table = 'reservasi';

protected $fillable = [
    'kelas_id',
    'user_id',
    'nama',
    'hari',
    'tanggal',
    'jam_mulai',
    'jam_selesai',
    'status',
    'alasan'
];

    public function kelas()
{
    return $this->belongsTo(\App\Models\Kelas::class, 'kelas_id');
}
}

