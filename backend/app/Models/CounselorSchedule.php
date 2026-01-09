<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CounselorSchedule extends Model
{
    protected $fillable = [
        'counselor_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'status'
    ];

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }
}
