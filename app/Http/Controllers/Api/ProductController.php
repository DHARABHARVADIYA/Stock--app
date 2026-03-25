<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /* ========== ADD / UPDATE PRODUCT ========== */
    public function saveProduct(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',

            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('business_code', $user->business_code);
                })
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where(function ($query) use ($request, $user) {
                        return $query->where('category_id', $request->category_id)
                                     ->where('business_code', $user->business_code);
                    })
                    ->ignore($request->id)
            ],

            'buy_price' => 'required|numeric',
            'sell_price' => 'required|numeric',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'result' => $validator->errors()
            ], 422);
        }

        // ADD / UPDATE
        if ($request->id == 0) {
            $product = new Product();
            $product->business_code = $user->business_code;
        } else {
            $product = Product::where('id', $request->id)
                ->where('business_code', $user->business_code)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found',
                    'result' => null
                ], 404);
            }
        }

        // SAVE DATA
        $product->category_id = $request->category_id;
        $product->name = $request->name;
        $product->buy_price = $request->buy_price;
        $product->sell_price = $request->sell_price;
        $product->stock = $request->stock;
        $product->description = $request->description;

        // IMAGE UPLOAD
        if ($request->hasFile('image')) {
            if (!empty($product->image) && File::exists(public_path($product->image))) {
                File::delete(public_path($product->image));
            }

            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('uploads/products'), $imageName);

            $product->image = 'uploads/products/' . $imageName;
        }

        $product->save();

        return response()->json([
            'status' => 200,
            'message' => $request->id == 0 ? 'Product added successfully' : 'Product updated successfully',
            'result' => $product
        ]);
    }

    /* ========== GET ALL PRODUCTS ========== */
    public function getProducts()
    {
        $user = auth('api')->user();

        $products = Product::where('business_code', $user->business_code)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Product list',
            'result' => $products
        ]);
    }

    /* ========== GET PRODUCTS BY CATEGORY ========== */
    public function getProductsByCategory(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'id' => [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('business_code', $user->business_code);
                })
            ]
        ]);

        $products = Product::where('category_id', $request->id)
            ->where('business_code', $user->business_code)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Products fetched successfully',
            'result' => $products
        ]);
    }

    /* ========== DELETE PRODUCT ========== */
    public function deleteProduct(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'result' => $validator->errors()
            ], 422);
        }

        $product = Product::where('id', $request->id)
            ->where('business_code', $user->business_code)
            ->first();

        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found',
                'result' => null
            ], 404);
        }

        // DELETE IMAGE
        if (!empty($product->image) && File::exists(public_path($product->image))) {
            File::delete(public_path($product->image));
        }

        $product->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Product deleted successfully',
            'result' => null
        ]);
    }
}
