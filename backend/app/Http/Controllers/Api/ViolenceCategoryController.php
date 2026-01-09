<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ViolenceCategory;
use Illuminate\Http\Request;

class ViolenceCategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            ViolenceCategory::latest()->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $data = ViolenceCategory::create($request->all());

        return response()->json($data, 201);
    }

    public function update(Request $request, $id)
    {
        $category = ViolenceCategory::findOrFail($id);

        $category->update($request->all());

        return response()->json($category);
    }

    public function destroy($id)
    {
        ViolenceCategory::destroy($id);

        return response()->json(['message' => 'Deleted']);
    }
}

