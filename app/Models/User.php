<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;   // <-- WAJIB ADA

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;  // <-- WAJIB ADA

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nim',
        'no_hp',
        'alamat',
        'gender',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
