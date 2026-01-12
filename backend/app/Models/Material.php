<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'judul',
        'tipe',
        'file_path',
        'link',
        'kategori',
        'uploaded_by'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded the material
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Alias for user relation for backward compatibility
     */
    public function uploader()
    {
        return $this->user();
    }
}

