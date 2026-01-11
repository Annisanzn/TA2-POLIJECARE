<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query()
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        $status = $request->query('status');

        if ($status === 'tayang' || $request->boolean('published_only')) {
            $query
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        }

        if ($search = $request->query('search')) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 12);
        $perPage = $perPage > 0 ? min($perPage, 100) : 12;

        $announcements = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $announcements->getCollection()
                ->map(fn (Announcement $announcement) => $this->transform($announcement))
                ->values(),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $announcement = Announcement::query()->findOrFail($id);

        $shouldHideDraft = !Auth::guard('sanctum')->check() && !$request->boolean('preview');

        if ($shouldHideDraft && ($announcement->published_at === null || $announcement->published_at->isFuture())) {
            return response()->json([
                'success' => false,
                'message' => 'Artikel tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transform($announcement),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $announcement = new Announcement();
        $announcement->fill($this->extractAttributes($validated));
        $announcement->image = $this->prepareImagePath($request);
        $announcement->save();

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil ditambahkan.',
            'data' => $this->transform($announcement),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $announcement = Announcement::query()->findOrFail($id);

        $validated = $this->validatePayload($request);
        $announcement->fill($this->extractAttributes($validated));
        $announcement->image = $this->prepareImagePath($request, $announcement->image);
        $announcement->save();

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil diperbarui.',
            'data' => $this->transform($announcement),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $announcement = Announcement::query()->findOrFail($id);

        $this->deleteStoredImage($announcement->image);
        $announcement->delete();

        return response()->json(null, 204);
    }

    protected function transform(Announcement $announcement): array
    {
        $publishedAt = $announcement->published_at;

        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'summary' => Str::limit(strip_tags($announcement->content), 220),
            'content' => $announcement->content,
            'image' => $announcement->image,
            'image_url' => $this->resolveImageUrl($announcement->image),
            'published_at' => $publishedAt?->toIso8601String(),
            'formatted_date' => $publishedAt ? $publishedAt->translatedFormat('j F Y') : null,
            'status' => $announcement->status,
            'created_at' => $announcement->created_at?->toIso8601String(),
            'updated_at' => $announcement->updated_at?->toIso8601String(),
        ];
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'image' => ['sometimes', 'file', 'image', 'max:2048'],
            'image_url' => ['sometimes', 'nullable', 'url'],
        ]);
    }

    protected function extractAttributes(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'content' => $validated['content'],
            'published_at' => $validated['published_at'] ?? null,
        ];
    }

    protected function prepareImagePath(Request $request, ?string $currentPath = null): ?string
    {
        if ($request->has('image_url')) {
            $url = trim((string) $request->input('image_url'));

            if ($url === '') {
                $this->deleteStoredImage($currentPath);

                return null;
            }

            if ($this->isExternalUrl($url)) {
                $this->deleteStoredImage($currentPath);

                return $url;
            }

            return $url;
        }

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($currentPath);

            return $request->file('image')->store('announcements', 'public');
        }

        return $currentPath;
    }

    protected function resolveImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if ($this->isExternalUrl($path)) {
            return $path;
        }

        $relativePath = ltrim($path, '/');

        if (Str::startsWith($relativePath, 'storage/')) {
            return asset($relativePath);
        }

        return asset('storage/' . $relativePath);
    }

    protected function deleteStoredImage(?string $path): void
    {
        if (!$path || $this->isExternalUrl($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function isExternalUrl(string $value): bool
    {
        return Str::startsWith($value, ['http://', 'https://']);
    }

}
