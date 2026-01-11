<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = [
        'title',
        'content',
        'image',
        'published_at'
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // helper status (opsional, tapi keren buat TA)
    public function getStatusAttribute()
    {
        if (!$this->published_at) {
            return 'draft';
        }

        return $this->published_at->isPast()
            ? 'tayang'
            : 'draft';
    }
}
