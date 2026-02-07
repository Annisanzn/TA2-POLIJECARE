<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * UserReportController
 * 
 * Handles user-specific report operations.
 * Provides RESTful endpoints for managing user complaints.
 */
class UserReportController extends Controller
{
    /**
     * Get user's complaint history.
     * 
     * RESTful endpoint: GET /api/user/reports
     * Fetches all complaints for the authenticated user.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        try {
            // Fetch complaints from the complaints table
            // Filter by authenticated user's ID
            // Order by created_at in descending order (newest first)
            $complaints = Complaint::where('user_id', $user->id)
                ->with(['category:id,name']) // Load category relationship for category name
                ->orderBy('created_at', 'desc')
                ->get(['id', 'title', 'violence_category_id', 'status', 'created_at']);
            
            // Transform data to match the required response format
            $transformedData = $complaints->map(function ($complaint) {
                return [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'category' => $complaint->category ? $complaint->category->name : null,
                    'status' => $complaint->status,
                    'created_at' => $complaint->created_at->format('Y-m-d'), // Format as YYYY-MM-DD
                ];
            });
            
            // Return clean JSON response as specified
            return response()->json([
                'status' => 'success',
                'data' => $transformedData
            ]);
            
        } catch (\Exception $e) {
            // Handle errors gracefully - return empty array on failure
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch reports',
                'data' => []
            ], 500);
        }
    }
    
    /**
     * Get user's complaint detail.
     * 
     * RESTful endpoint: GET /api/user/reports/{id}
     * Fetches specific complaint details for the authenticated user.
     * Includes authorization check to ensure user can only access their own complaints.
     * 
     * @param Request $request
     * @param int $id Complaint ID
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        
        try {
            // Find the complaint with the given ID
            $complaint = Complaint::find($id);
            
            // Return 404 if complaint not found
            if (!$complaint) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Complaint not found'
                ], 404);
            }
            
            // Authorization check: ensure complaint belongs to authenticated user
            if ($complaint->user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Access denied - you can only view your own complaints'
                ], 403);
            }
            
            // Transform data to match the required response format
            $complaintData = [
                'id' => $complaint->id,
                'report_reference' => $complaint->report_reference,
                'title' => $complaint->title,
                'description' => $complaint->description,
                'violence_category_id' => $complaint->violence_category_id,
                'victim_type' => $complaint->victim_type,
                'victim_name' => $complaint->victim_name,
                'victim_relationship' => $complaint->victim_relationship,
                'chronology' => $complaint->chronology,
                'status' => $complaint->status,
                'urgency_level' => $complaint->urgency_level,
                'is_anonymous' => $complaint->is_anonymous,
                'created_at' => $complaint->created_at->format('Y-m-d H:i:s'),
            ];
            
            // Return successful response with complaint details
            return response()->json([
                'status' => 'success',
                'data' => $complaintData
            ]);
            
        } catch (\Exception $e) {
            // Handle unexpected errors
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch complaint details',
                'data' => null
            ], 500);
        }
    }
}
