<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SessionAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        // Validate input (CSRF token is handled by middleware, not required in validation)
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.exists' => 'Email tidak terdaftar',
            'password.required' => 'Password wajib diisi',
            'password.min' => 'Password minimal 6 karakter',
        ]);


        // Use web guard for session-based auth
        if (!Auth::guard('web')->attempt($validated)) {
            Log::warning('Login failed', [
                'email' => $request->email,
                'reason' => 'invalid_credentials',
                'ip' => $request->ip(),
                'attempts' => session()->get('login_attempts', 0) + 1
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Email atau password salah',
                'error_code' => 'INVALID_CREDENTIALS',
                'debug_info' => [
                    'validation_passed' => true,
                    'csrf_token_valid' => $request->has('_token'),
                    'session_id' => session()->getId()
                ]
            ], 401);
        }

        $user = Auth::guard('web')->user();
        Log::info('Login success', [
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'login_time' => now()->toISOString()
        ]);

        // Determine dashboard route based on role
        $dashboardRoute = '/dashboard';
        $availableFeatures = [];
        
        switch ($user->role) {
            case 'operator':
                $availableFeatures = [
                    'user_management' => '/users',
                    'announcements' => '/announcements',
                    'reports' => '/reports',
                    'system_settings' => '/settings'
                ];
                break;
            case 'konselor':
                $availableFeatures = [
                    'counseling' => '/counseling',
                    'materials' => '/materials',
                    'schedules' => '/schedules',
                    'students' => '/students'
                ];
                break;
            case 'user':
                $availableFeatures = [
                    'reports' => '/user/reports',
                    'complaints' => '/user/complaints',
                    'profile' => '/user/profile',
                    'history' => '/user/history'
                ];
                break;
            default:
                $availableFeatures = [
                    'dashboard' => '/dashboard'
                ];
                break;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $this->getUserPermissions($user->role)
            ],
            'session_info' => [
                'session_id' => session()->getId(),
                'expires_at' => now()->addMinutes(config('session.lifetime'))->toISOString()
            ],
            'dashboard' => [
                'route' => $dashboardRoute,
                'features' => $availableFeatures
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak terautentikasi'
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    /**
     * Get CSRF token for frontend
     */
    public function csrfToken(Request $request): JsonResponse
    {
        return response()->json([
            'csrf_token' => csrf_token(),
            'timestamp' => now()->timestamp
        ]);
    }

    /**
     * Get user permissions based on role
     */
    public function getUserPermissions($role): array
    {
        switch ($role) {
            case 'operator':
                return [
                    'can_manage_users' => true,
                    'can_manage_announcements' => true,
                    'can_view_reports' => true,
                    'can_manage_system' => true,
                    'can_access_all_data' => true
                ];
            case 'konselor':
                return [
                    'can_manage_counseling' => true,
                    'can_manage_materials' => true,
                    'can_view_students' => true,
                    'can_manage_schedules' => true,
                    'can_view_reports' => true
                ];
            case 'user':
                return [
                    'can_create_reports' => true,
                    'can_view_own_reports' => true,
                    'can_manage_profile' => true,
                    'can_view_complaints' => true
                ];
            default:
                return [];
        }
    }

    /**
     * Refresh CSRF token for frontend
     * This endpoint helps when CSRF token expires or becomes invalid
     */
    public function refreshCsrfToken(Request $request): JsonResponse
    {
        // Regenerate CSRF token
        $request->session()->regenerateToken();
        
        return response()->json([
            'csrf_token' => csrf_token(),
            'timestamp' => now()->timestamp,
            'message' => 'CSRF token refreshed successfully'
        ]);
    }
}
