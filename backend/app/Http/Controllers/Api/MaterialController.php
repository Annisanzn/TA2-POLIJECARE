<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index()
    {
        $materials = Material::with('uploader')
            ->latest()
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'judul' => $m->judul,
                    'tipe' => $m->tipe,
                    'kategori' => $m->kategori,
                    'tanggal_upload' => $m->created_at->format('Y-m-d'),
                    'diunggah_oleh' => $m->uploader->name ?? '-',
                ];
            });

        return response()->json($materials);
    }
}
