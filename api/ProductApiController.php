<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Webkul\Product\Repositories\ProductRepository;

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

        $repo = app(ProductRepository::class);

        $payload = [
            'entity_type' => 'products',
            'sku'         => $data['sku'],
            'name'        => $data['name'] ?? $data['sku'],
            'description' => $data['description'] ?? '',
            'price'       => $data['price'],
            'quantity'    => (int) ($data['quantity'] ?? 0),
        ];

        $existing = $repo->findOneWhere(['sku' => $data['sku']]);

        $product = $existing
            ? $repo->update($payload, $existing->id)
            : $repo->create($payload);

        return response()->json([
            'success' => true,
            'product' => [
                'sku'      => $product->sku,
                'name'     => $product->name,
                'price'    => (float) $product->price,
                'quantity' => (int) $product->quantity,
            ],
        ]);
    }

    /**
     * Read a product by SKU.
     */
    public function show(Request $request, string $sku): JsonResponse
    {
        $repo = app(ProductRepository::class);
        $product = $repo->findOneWhere(['sku' => $sku]);

        if (! $product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id'         => $product->id,
                'sku'        => $product->sku,
                'name'       => $product->name,
                'description'=> $product->description,
                'price'      => (float) $product->price,
                'quantity'   => (int) $product->quantity,
            ],
        ]);
    }
}
