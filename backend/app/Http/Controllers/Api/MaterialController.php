<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        // Get pagination parameters
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(50, max(1, (int) $request->get('per_page', 10)));
        
        // Query all materials (both draft & active) for operator & konselor
        $materials = Material::with('user')
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
        
        // Transform data to match frontend expectations
        $transformedData = $materials->getCollection()->map(function ($material) {
            return [
                'id' => $material->id,
                'judul' => $material->judul, // Keep original field name
                'title' => $material->judul, // Also provide as title for compatibility
                'tipe' => $material->tipe,
                'kategori' => $material->kategori,
                'file_path' => $material->file_path,
                'file_url' => $material->file_path ? asset('storage/' . $material->file_path) : null,
                'link' => $material->link,
                'link_url' => $material->link,
                'uploaded_by' => $material->uploaded_by,
                'created_at' => $material->created_at->toISOString(),
                'updated_at' => $material->updated_at->toISOString(),
                'user' => $material->user ? [
                    'id' => $material->user->id,
                    'name' => $material->user->name,
                    'email' => $material->user->email,
                    'role' => $material->user->role,
                ] : null,
                'diunggah_oleh' => $material->user ? $material->user->name : '-', // For frontend display
            ];
        });
        
        // Return valid JSON response even if data is empty
        return response()->json([
            'data' => $transformedData,
            'total' => $materials->total(),
            'current_page' => $materials->currentPage(),
            'last_page' => $materials->lastPage(),
            'per_page' => $materials->perPage(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Operator & Konselor can create materials
        if (!in_array($user->role, ['operator', 'konselor'])) {
            return response()->json(['message' => 'Unauthorized - Only Operator and Konselor can create materials'], 403);
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:pdf,link',
            'kategori' => 'nullable|string|max:255',
            'file' => 'required_if:tipe,pdf|file|mimes:pdf|max:10240', // Max 10MB
            'link' => 'required_if:tipe,link|url',
            'link_url' => 'nullable|url', // For compatibility
        ], [
            'judul.required' => 'Judul wajib diisi',
            'tipe.required' => 'Tipe wajib dipilih',
            'tipe.in' => 'Tipe harus pdf atau link',
            'file.required_if' => 'File PDF wajib diunggah untuk tipe PDF',
            'file.mimes' => 'File harus berformat PDF',
            'file.max' => 'Ukuran file maksimal 10MB',
            'link.required_if' => 'Link wajib diisi untuk tipe link',
            'link.url' => 'Format link tidak valid',
        ]);

        try {
            $filePath = null;
            
            if ($validated['tipe'] === 'pdf' && $request->hasFile('file')) {
                $file = $request->file('file');
                
                // Create directory if not exists
                $directory = 'materials';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                
                // Store file with unique name
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs($directory, $fileName, 'public');
            }

            $material = Material::create([
                'judul' => $validated['judul'],
                'tipe' => $validated['tipe'],
                'file_path' => $filePath,
                'link' => $validated['link_url'] ?? $validated['link'] ?? null,
                'kategori' => $validated['kategori'] ?? null,
                'uploaded_by' => auth()->id(), // Use auth()->id() as requested
            ]);

            // Load material with user relation
            $material->load('user');

            return response()->json([
                'message' => 'Material berhasil dibuat',
                'data' => [
                    'id' => $material->id,
                    'title' => $material->judul,
                    'created_at' => $material->created_at->toISOString(),
                    'user' => $material->user ? [
                        'id' => $material->user->id,
                        'name' => $material->user->name,
                        'email' => $material->user->email,
                        'role' => $material->user->role,
                    ] : null,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat materi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // Only Operator can update materials (Konselor read-only)
        if ($user->role !== 'operator') {
            return response()->json(['message' => 'Unauthorized - Only Operator can update materials'], 403);
        }

        $material = Material::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'tipe' => 'required|in:pdf,link',
            'kategori' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,draft', // Status optional in update
            'file' => 'nullable|file|mimes:pdf|max:10240', // File optional in update
            'link' => 'nullable|url',
            'link_url' => 'nullable|url', // For compatibility
        ], [
            'judul.required' => 'Judul wajib diisi',
            'tipe.required' => 'Tipe wajib dipilih',
            'tipe.in' => 'Tipe harus pdf atau link',
            'status.in' => 'Status harus active atau draft',
            'file.mimes' => 'File harus berformat PDF',
            'file.max' => 'Ukuran file maksimal 10MB',
            'link.url' => 'Format link tidak valid',
        ]);

        try {
            $updateData = [
                'judul' => $validated['judul'],
                'tipe' => $validated['tipe'],
                'link' => $validated['link_url'] ?? $validated['link'] ?? null,
                'kategori' => $validated['kategori'] ?? null,
            ];

            // Only update status if explicitly sent in request (no auto override)
            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }

            // Handle file upload if new file provided
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                
                // Create directory if not exists
                $directory = 'materials';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                
                // Store file with unique name
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs($directory, $fileName, 'public');
                
                $updateData['file_path'] = $filePath;
                
                // Delete old file if exists
                if ($material->file_path) {
                    Storage::disk('public')->delete($material->file_path);
                }
            }

            $material->update($updateData);
            
            // Load material with user relation
            $material->load(['user:id,name,email']);

            return response()->json([
                'message' => 'Material berhasil diperbarui',
                'data' => $material
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memperbarui materi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = Auth::user();

        // Only Operator can delete materials (Konselor read-only)
        if ($user->role !== 'operator') {
            return response()->json(['message' => 'Unauthorized - Only Operator can delete materials'], 403);
        }

        $material = Material::findOrFail($id);

        try {
            // Delete file if exists
            if ($material->file_path) {
                Storage::disk('public')->delete($material->file_path);
            }

            $material->delete();

            return response()->json(['message' => 'Material berhasil dihapus']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menghapus materi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
