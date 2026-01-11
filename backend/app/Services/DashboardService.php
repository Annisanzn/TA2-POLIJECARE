<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\CounselingSchedule;
use App\Models\Material;
use Carbon\Carbon;

class DashboardService
{
    public function buildDashboardPayload(): array
    {
        $complaintsTotal = Complaint::query()->count();

        $complaintsByStatus = Complaint::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $counselingTotal = CounselingSchedule::query()->count();

        $counselingByStatus = CounselingSchedule::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $materialsTotal = Material::query()->count();

        $dailyStart = Carbon::today()->subDays(6);
        $dailyComplaints = Complaint::query()
            ->whereDate('tanggal_laporan', '>=', $dailyStart)
            ->selectRaw('DATE(tanggal_laporan) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->date,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        $monthlyStart = Carbon::today()->startOfMonth()->subMonths(5);
        $monthlyComplaints = Complaint::query()
            ->whereDate('tanggal_laporan', '>=', $monthlyStart)
            ->selectRaw("DATE_FORMAT(tanggal_laporan, '%Y-%m') as month, COUNT(*) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => (string) $row->month,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'complaints' => [
                    'total' => $complaintsTotal,
                    'by_status' => [
                        'baru' => (int) ($complaintsByStatus['baru'] ?? 0),
                        'diproses' => (int) ($complaintsByStatus['diproses'] ?? 0),
                        'selesai' => (int) ($complaintsByStatus['selesai'] ?? 0),
                    ],
                ],
                'counseling_schedules' => [
                    'total' => $counselingTotal,
                    'by_status' => [
                        'menunggu_konfirmasi' => (int) ($counselingByStatus['menunggu_konfirmasi'] ?? 0),
                        'dikonfirmasi' => (int) ($counselingByStatus['dikonfirmasi'] ?? 0),
                        'dibatalkan' => (int) ($counselingByStatus['dibatalkan'] ?? 0),
                    ],
                ],
                'materials' => [
                    'total' => $materialsTotal,
                ],
            ],
            'charts' => [
                'complaints' => [
                    'daily_last_7_days' => $dailyComplaints,
                    'monthly_last_6_months' => $monthlyComplaints,
                ],
            ],
        ];
    }
}
