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
    /* ========== COMMON RESPONSE ========== */
    protected function sendResponse($status, $messages = [], $data = null)
    {
        return response()->json([
            'status' => $status,
            'message' => (array) $messages,
            'result' => $data
        ], $status);
    }

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
            return $this->sendResponse(
                422,
                $validator->errors()->all(),
                null
            );
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
                return $this->sendResponse(
                    404,
                    ['Product not found'],
                    null
                );
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

        return $this->sendResponse(
            200,
            [$request->id == 0 ? 'Product added successfully' : 'Product updated successfully'],
            $product
        );
    }

    /* ========== GET ALL PRODUCTS ========== */
    public function getProducts()
    {
        $user = auth('api')->user();

        $products = Product::where('business_code', $user->business_code)
            ->orderBy('id', 'desc')
            ->get();

        return $this->sendResponse(
            200,
            ['Product list'],
            $products
        );
    }

    /* ========== GET PRODUCTS BY CATEGORY ========== */
    public function getProductsByCategory(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id' => [
                'required',
                Rule::exists('categories', 'id')->where(function ($query) use ($user) {
                    return $query->where('business_code', $user->business_code);
                })
            ]
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(
                422,
                $validator->errors()->all(),
                null
            );
        }

        $products = Product::where('category_id', $request->id)
            ->where('business_code', $user->business_code)
            ->orderBy('id', 'desc')
            ->get();

        return $this->sendResponse(
            200,
            ['Products fetched successfully'],
            $products
        );
    }

    /* ========== DELETE PRODUCT ========== */
    public function deleteProduct(Request $request)
    {
        $user = auth('api')->user();

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return $this->sendResponse(
                422,
                $validator->errors()->all(),
                null
            );
        }

        $product = Product::where('id', $request->id)
            ->where('business_code', $user->business_code)
            ->first();

        if (!$product) {
            return $this->sendResponse(
                404,
                ['Product not found'],
                null
            );
        }

        // DELETE IMAGE
        if (!empty($product->image) && File::exists(public_path($product->image))) {
            File::delete(public_path($product->image));
        }

        $product->delete();

        return $this->sendResponse(
            200,
            ['Product deleted successfully'],
            null
        );
    }
}
