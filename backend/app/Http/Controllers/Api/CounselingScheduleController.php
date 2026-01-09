<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CounselingSchedule;
use Illuminate\Http\Request;

class CounselingScheduleController extends Controller
{
    public function index()
    {
        $schedules = CounselingSchedule::with('user:id,name')
            ->orderBy('tanggal_waktu', 'desc')
            ->get();

        return response()->json($schedules);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:dikonfirmasi,dibatalkan'
        ]);

        $schedule = CounselingSchedule::findOrFail($id);
        $schedule->status = $request->status;
        $schedule->save();

        return response()->json([
            'message' => 'Status jadwal berhasil diperbarui'
        ]);
    }
}
