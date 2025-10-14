<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Helpers\ValidationHelper;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $search = $request->query('search');
            $type = $request->query('type');
            $perPage = $request->query('per_page', 10);

            $query = Category::query();

            if (!empty($search)) {
                $query->where('category_name', 'like', '%' . $search . '%');
            }

            if (!empty($type)) {
                $query->where('type', $type);
            }

            $categories = $query->orderBy('created_at', 'desc')->paginate($perPage);

            if ($categories->isEmpty()) {
                return response()->json([
                    'categories' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ]
                ], 200);
            }

            return response()->json([
                'categories' => $categories->items(),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'last_page' => $categories->lastPage(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data kategori.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Kategori tidak ditemukan.'], 404);
        }

        return response()->json($category, 200);
    }

    public function store(Request $request)
    {
        try {
            $validator = ValidationHelper::validateCategory(request()->all(), true);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            if (Category::where('category_name', $data['category_name'])->exists()) {
                return response()->json(['message' => 'Nama kategori sudah ada.'], 422);
            }

            Category::create($data);

            return response()->json([
                'message' => 'Kategori berhasil ditambahkan.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['message' => 'Kategori tidak ditemukan.'], 404);
            }

            $validator = ValidationHelper::validateCategory(request()->all(), false);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $data = $validator->validated();

            if (Category::where('category_name', $data['category_name'])->where('id', '!=', $id)->exists()) {
                return response()->json(['message' => 'Nama kategori sudah ada.'], 422);
            }

            $category->update($data);

            return response()->json([
                'message' => 'Kategori berhasil diperbarui.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json(['message' => 'Kategori tidak ditemukan.'], 404);
            }

            $category->delete();

            return response()->json([
                'message' => 'Kategori berhasil dihapus.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
