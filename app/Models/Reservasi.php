<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservasi extends Model
{
    protected $table = 'reservasi';

    protected $fillable = [
        'kelas_id', 'user_id',
        'jam_mulai', 'jam_selesai',
        'status', 'alasan',
        'Hari'
    ];
}
