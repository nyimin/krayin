<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Webkul\Product\Models\Product;

class ProductApiController extends Controller
{
    /**
     * Issue a Sanctum personal access token for integrations (n8n).
     */
    public function token(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = \Webkul\User\Models\User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'success' => true,
            'token'   => $user->createToken('pricing-sync')->plainTextToken,
            'type'    => 'Bearer',
        ]);
    }

    /**
     * Upsert a product's sell price by SKU (create if it does not yet exist).
     */
    public function upsert(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sku'         => 'required|string',
            'name'        => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'nullable|integer|min:0',
        ]);

        $product = Product::where('sku', $data['sku'])->first();

        if (! $product) {
            $product = new Product;
            $product->sku = $data['sku'];
            $product->name = $data['name'] ?? $data['sku'];
            $product->description = $data['description'] ?? '';
        } else {
            if (isset($data['name'])) {
                $product->name = $data['name'];
            }
            if (isset($data['description'])) {
                $product->description = $data['description'];
            }
        }

        $product->price = $data['price'];
        if (isset($data['quantity'])) {
            $product->quantity = $data['quantity'];
        }
        $product->save();

        return response()->json([
            'success' => true,
            'product' => [
                'sku'      => $product->sku,
                'name'     => $product->name,
                'price'    => $product->price,
                'quantity' => $product->quantity,
            ],
        ]);
    }

    /**
     * Read a product by SKU.
     */
    public function show(Request $request, string $sku): JsonResponse
    {
        $product = Product::where('sku', $sku)->first();

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        return response()->json(['success' => true, 'product' => $product]);
    }
}
