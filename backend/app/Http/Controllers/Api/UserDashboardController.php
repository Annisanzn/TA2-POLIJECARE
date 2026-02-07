<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserDashboardRequest;
use App\Http\Requests\UserReportRequest;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    /**
     * Get user dashboard summary.
     * 
     * @param UserDashboardRequest $request
     * @return JsonResponse
     */
    public function dashboard(UserDashboardRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        try {
            // Get report statistics for the authenticated user only
            $userId = $user->id;
            
            $totalReports = Complaint::where('user_id', $userId)->count();
            $reportsInProgress = Complaint::where('user_id', $userId)
                ->where('status', 'diproses')
                ->count();
            $completedReports = Complaint::where('user_id', $userId)
                ->where('status', 'selesai')
                ->count();
            
            return response()->json([
                'success' => true,
                'message' => 'Dashboard data loaded successfully',
                'data' => [
                    'total_reports' => $totalReports,
                    'reports_in_progress' => $reportsInProgress,
                    'completed_reports' => $completedReports,
                ],
                'meta' => [
                    'user_id' => $userId,
                    'generated_at' => now()->toISOString(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Create a new report.
     * 
     * @param UserReportRequest $request
     * @return JsonResponse
     */
    public function storeReport(UserReportRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        try {
            DB::beginTransaction();
            
            $validated = $request->validated();
            
            // Create report with automatic user assignment
            $report = Complaint::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'violence_category_id' => $validated['category_id'],
                'user_id' => $user->id, // Automatic user assignment
                'status' => 'baru', // Default status
                'anonim' => $validated['anonim'] ?? false,
                'incident_date' => $validated['incident_date'] ?? null,
                'incident_location' => $validated['incident_location'] ?? null,
                'perpetrator_name' => $validated['perpetrator_name'] ?? null,
                'perpetrator_relationship' => $validated['perpetrator_relationship'] ?? null,
                'witnesses' => $validated['witnesses'] ?? null,
                'evidence_description' => $validated['evidence_description'] ?? null,
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Report created successfully',
                'data' => [
                    'report_id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                    'created_at' => $report->created_at->toISOString(),
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Get user's complaint history.
     * RESTful endpoint for fetching user's own complaints
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserComplaints(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        try {
            // Fetch only user's own complaints from complaints table
            // Filter by authenticated user_id and order by created_at DESC
            $complaints = Complaint::where('user_id', $user->id)
                ->with(['category:id,name']) // Load category relationship
                ->orderBy('created_at', 'desc')
                ->get(['id', 'title', 'violence_category_id', 'status', 'created_at']);
            
            // Transform data to match requested format
            $transformedData = $complaints->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'category' => $complaint->category ? $complaint->category->name : null,
                    'status' => $complaint->status,
                    'created_at' => $complaint->created_at->toISOString(),
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $transformedData
            ]);
            
        } catch (\Exception $e) {
            // Handle errors gracefully
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch complaint history',
                'data' => []
            ], 500);
        }
    }
    
    /**
     * Get user's report history.
     * 
     * @param UserDashboardRequest $request
     * @return JsonResponse
     */
    public function getReportHistory(UserDashboardRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        try {
            // Get pagination parameters with defaults
            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(50, max(1, (int) $request->get('per_page', 10)));
            
            // Query only user's own reports with security
            $reports = Complaint::where('user_id', $user->id)
                ->with(['category:id,name'])
                ->latest()
                ->paginate($perPage, ['id', 'title', 'status', 'created_at', 'violence_category_id'], 'page', $page);
            
            // Transform data for consistent response format
            $transformedData = $reports->getCollection()->map(function ($report) {
                return [
                    'report_id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                    'created_at' => $report->created_at->toISOString(),
                    'category' => $report->category ? [
                        'id' => $report->category->id,
                        'name' => $report->category->name,
                    ] : null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Report history loaded successfully',
                'data' => $transformedData,
                'pagination' => [
                    'current_page' => $reports->currentPage(),
                    'last_page' => $reports->lastPage(),
                    'per_page' => $reports->perPage(),
                    'total' => $reports->total(),
                    'from' => $reports->firstItem(),
                    'to' => $reports->lastItem(),
                ],
                'meta' => [
                    'user_id' => $user->id,
                    'generated_at' => now()->toISOString(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load report history',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
