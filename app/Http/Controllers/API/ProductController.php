<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
class ProductController extends BaseController
{
    public function index(): JsonResponse
    {
        $products = Product::all();

        return response()->json(['data' => ProductResource::collection($products)], 200);
    }
    
    public function store(Request $request): JsonResponse
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'detail' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation Error', 'message' => $validator->errors()], 422);
        }

        $product = Product::create($input);

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function show($id): JsonResponse
    {
        $product = Product::find($id);

        if (is_null($product)) {
            return response()->json(['error' => 'Product not found.'], 404);
        }

        return response()->json(['data' => new ProductResource($product)], 200);
    }

    public function update(Request $request, Product $product)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required',
            'detail' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation Error', 'message' => $validator->errors()], 422);
        }

        $product->update($input);

        return response()->json(['data' => new ProductResource($product)], 200);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([], 204);
    }

    // ... other methods as needed
}
