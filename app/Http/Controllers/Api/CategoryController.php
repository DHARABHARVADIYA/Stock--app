<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Category;
use Illuminate\Support\Facades\File;

class CategoryController extends Controller
{
    /* ========== ADD / UPDATE CATEGORY ========== */
    public function saveCategory(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id'    => 'required|integer',
            'name'  => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->all(), 
                'result' => (object)[]
            ], 422);
        }

        if ($request->id == 0) {
            $category = new Category();
            $category->business_code = $user->business_code;
        } else {
            $category = Category::where('id', $request->id)
                ->where('business_code', $user->business_code)
                ->first();

            if (!$category) {
                return response()->json([
                    'status' => 404,
                    'message' => ['Category not found'],
                    'data' => (object)[]
                ], 404);
            }
        }

        $category->name = $request->name;

        if ($request->hasFile('image')) {
            if (!empty($category->image) && File::exists(public_path($category->image))) {
                File::delete(public_path($category->image));
            }
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/categories'), $imageName);
            $category->image = 'uploads/categories/' . $imageName;
        }

        $category->save();

        return response()->json([
            'status' => 200,
            'message' => [
                $request->id == 0 
                    ? 'Category added successfully' 
                    : 'Category updated successfully'
            ],
            'data' => $category
        ]);
    }

    /* ========== GET ALL CATEGORIES ========== */
    public function getCategories()
    {
        $user = auth('api')->user();

        $categories = Category::where('business_code', $user->business_code)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => ['Category list fetched successfully'],
            'result' => $categories
        ]);
    }

    /* ========== DELETE CATEGORY ========== */
    public function deleteCategory(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors()->all(),
                'result' => (object)[]
            ], 422);
        }

        $category = Category::where('id', $request->id)
            ->where('business_code', $user->business_code)
            ->first();

        if (!$category) {
            return response()->json([
                'status' => 404,
                'message' => ['Category not found'],
                'result' => (object)[]
            ], 404);
        }

        if ($category->products()->count() > 0) {
            return response()->json([
                'status' => 409,
                'message' => ['Cannot delete category. Products exist under this category.'],
                'result' => (object)[]
            ], 409);
        }

        if (!empty($category->image) && File::exists(public_path($category->image))) {
            File::delete(public_path($category->image));
        }

        $category->delete();

        return response()->json([
            'status' => 200,
            'message' => ['Category deleted successfully'],
            'result' => (object)[]
        ]);
    }
}