<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CounselingSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'jenis_pengaduan',
        'tanggal_waktu',
        'metode',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

