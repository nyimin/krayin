<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductApiController;

/*
|--------------------------------------------------------------------------
| PowerEdge CRM API
|--------------------------------------------------------------------------
|
| Lightweight internal API for the pricing-sync integration (n8n -> Airtable
| -> Krayin). Auth is via Laravel Sanctum bearer tokens; POST /api/v1/token
| exchanges admin credentials for a token.
|
*/

Route::get('/', fn () => response()->json(['app' => 'PowerEdge CRM API', 'status' => 'ok']));

// Token issuance (no auth required — validates credentials manually).
Route::post('/v1/token', [ProductApiController::class, 'token']);

// Authenticated endpoints.
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/products/upsert', [ProductApiController::class, 'upsert']);
    Route::get('/products/{sku}', [ProductApiController::class, 'show']);
});
