<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min($perPage, 100));

        $users = User::query()
            ->select('id', 'name', 'email', 'role', 'created_at', 'updated_at')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->only(['page', 'per_page']));

        $data = $users->getCollection()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => optional($user->created_at)->toISOString(),
                    'updated_at' => optional($user->updated_at)->toISOString(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'links' => [
                'first' => $users->url(1),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
                'last' => $users->url($users->lastPage()),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::in(['user', 'operator', 'admin'])],
        ]);

        $user = User::create($data);

        return response()->json($user->only('id', 'name', 'email', 'role'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['sometimes', 'required', 'string', Rule::in(['user', 'operator', 'admin'])],
        ]);

        if (isset($data['password']) && $data['password'] === null) {
            unset($data['password']);
        }

        if (empty($data)) {
            return response()->json($user->only('id', 'name', 'email', 'role'));
        }

        $user->fill(array_filter(
            $data,
            fn ($value) => $value !== null
        ));

        if (array_key_exists('password', $data) && $data['password'] === '') {
            $user->makeHidden('password');
        }

        $user->save();

        return response()->json($user->only('id', 'name', 'email', 'role'));
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
