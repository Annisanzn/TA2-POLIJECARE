<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'role', 'created_at', 'updated_at')
            ->orderBy('name')
            ->get();

        return response()->json($users);
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
