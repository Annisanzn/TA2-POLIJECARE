<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * CounselorController
 * 
 * Handles counselor-related operations including:
 * - Fetching available counselors for complaint creation
 * 
 * IMPORTANT: This endpoint requires authentication and role-based access control.
 * Only authenticated users with role 'user' can access the counselor list.
 * This ensures that only legitimate users can view counselor information for complaint creation.
 */
class CounselorController extends Controller
{
    /**
     * Get list of available counselors.
     * 
     * RESTful endpoint: GET /api/counselors
     * 
     * Authentication Requirements:
     * - User must be authenticated via Sanctum (cookie-based for SPA)
     * - Route is protected by auth:sanctum middleware
     * - No additional role restrictions (accessible to any authenticated user)
     * 
     * Purpose: Provide counselor options for users creating complaints
     * Security: Counselor list is not publicly accessible (requires authentication)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Get authenticated user (route middleware ensures user is authenticated)
        $user = Auth::user();
        
        // Additional security check - ensure user is authenticated
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated. Please login to access this resource.'
            ], 401); // Unauthorized
        }
        
        try {
            // Fetch users with role 'konselor' only
            // Return only id and name as specified in requirements
            $counselors = User::where('role', 'konselor')
                ->select(['id', 'name']) // Only return required fields
                ->orderBy('name', 'asc')
                ->get();
            
            // Transform data to match required response format
            $transformedData = $counselors->map(function ($counselor) {
                return [
                    'id' => $counselor->id,
                    'name' => $counselor->name,
                ];
            });
            
            return response()->json([
                'status' => 'success',
                'data' => $transformedData
            ]);
            
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('Error fetching counselors: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'user_role' => $user->role
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch counselors',
                'data' => []
            ], 500);
        }
    }
}
