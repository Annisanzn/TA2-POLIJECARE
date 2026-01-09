<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'nama_pelapor',
        'jenis_pengaduan',
        'tanggal_laporan',
        'status',
        'jadwal_konseling',
        'konselor'
    ];
}
