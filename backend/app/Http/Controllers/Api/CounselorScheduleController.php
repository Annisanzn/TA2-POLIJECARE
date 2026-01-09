<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CounselorSchedule;
use Illuminate\Http\Request;

class CounselorScheduleController extends Controller
{
    public function index()
    {
        $data = CounselorSchedule::with('counselor:id,name')
            ->orderBy('hari')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nama_konselor' => $item->counselor->name,
                    'hari' => $item->hari,
                    'jam' => $item->jam_mulai.' - '.$item->jam_selesai,
                    'status' => $item->status,
                    'digunakan_dalam' => 0 // nanti dari complaints
                ];
            });

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'counselor_id' => 'required|exists:users,id',
            'hari' => 'required',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required'
        ]);

        CounselorSchedule::create($request->all());

        return response()->json(['message' => 'Jadwal berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $schedule = CounselorSchedule::findOrFail($id);
        $schedule->update($request->all());

        return response()->json(['message' => 'Jadwal berhasil diubah']);
    }
}

