<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
{
    return response()->json(
        Announcement::orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
    );
}

}
