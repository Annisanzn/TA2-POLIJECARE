<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payload = $this->dashboardService->buildDashboardPayload();

        return response()->json([
            'data' => $payload,
            'meta' => [
                'generated_at' => now()->toISOString(),
                'role' => $request->user()?->role,
            ],
        ]);
    }
}
