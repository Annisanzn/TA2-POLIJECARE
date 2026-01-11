<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $paginator = Complaint::query()
            ->with([
                'reporter:id,name,email',
                'counselor:id,name,email',
                'category:id,name',
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->appends($request->only(['page', 'per_page']));

        $data = $paginator->getCollection()
            ->map(fn (Complaint $complaint) => $this->formatComplaint($complaint))
            ->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Complaint $complaint): JsonResponse
    {
        $complaint->loadMissing(['reporter:id,name,email', 'counselor:id,name,email', 'category:id,name']);

        return response()->json([
            'data' => $this->formatComplaint($complaint),
        ]);
    }

    public function updateStatus(Request $request, Complaint $complaint): JsonResponse
    {
        $payload = $request->validate([
            'status' => ['required', Rule::in(['baru', 'diproses', 'selesai'])],
        ]);

        $this->ensureCounselorOwnsComplaint($request, $complaint);

        $complaint->status = $payload['status'];
        $complaint->save();

        $complaint->loadMissing(['reporter:id,name,email', 'counselor:id,name,email', 'category:id,name']);

        return response()->json([
            'data' => $this->formatComplaint($complaint),
        ]);
    }

    public function assignCounselor(Request $request, Complaint $complaint): JsonResponse
    {
        $this->ensureOperator($request);

        $payload = $request->validate([
            'counselor_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'konselor')),
            ],
        ]);

        $complaint->counselor_id = $payload['counselor_id'];
        $complaint->save();

        $complaint->loadMissing(['reporter:id,name,email', 'counselor:id,name,email', 'category:id,name']);

        return response()->json([
            'data' => $this->formatComplaint($complaint),
        ]);
    }

    public function destroy(Request $request, Complaint $complaint): JsonResponse
    {
        $this->ensureOperator($request);

        $complaint->delete();

        return response()->json([
            'message' => 'Complaint deleted successfully.',
        ]);
    }

    private function ensureCounselorOwnsComplaint(Request $request, Complaint $complaint): void
    {
        $user = $request->user();

        if ($user && $user->role === 'konselor' && $complaint->counselor_id !== $user->id) {
            abort(403, 'Konselor tidak diperbolehkan mengubah status pengaduan milik konselor lain.');
        }
    }

    private function ensureOperator(Request $request): void
    {
        $user = $request->user();

        if (! $user || $user->role !== 'operator') {
            abort(403, 'Hanya operator yang diperbolehkan melakukan aksi ini.');
        }
    }

    private function formatComplaint(Complaint $complaint): array
    {
        $complaint->loadMissing(['reporter:id,name,email', 'counselor:id,name,email', 'category:id,name']);

        return [
            'id' => $complaint->id,
            'user_name' => $complaint->reporter?->name,
            'user_email' => $complaint->reporter?->email,
            'category' => $complaint->category?->name,
            'title' => $complaint->title,
            'description' => $complaint->description,
            'status' => $complaint->status,
            'created_at' => optional($complaint->created_at)->toDateString(),
            'counselor' => $complaint->counselor ? [
                'id' => $complaint->counselor->id,
                'name' => $complaint->counselor->name,
                'email' => $complaint->counselor->email,
            ] : null,
        ];
    }
}
