<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $cacheKey = 'products_page_' . ($request->query('page', 1));

        $products = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Product::with(['category:id,name', 'tags:id,name', 'reviews:id,product_id,rating,comment,user_id', 'reviews.user:id,name'])
                ->select('id', 'name', 'price', 'description', 'category_id')
                ->paginate(10);
        });

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }
}

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
            'category' => $this->category->name,
            'tags' => $this->tags->pluck('name'),
            'reviews' => $this->reviews->map(function ($review) {
                return [
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => $review->user->name,
                ];
            }),
        ];
    }
}

// Middleware configuration for rate limiting
Route::middleware('throttle:public')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});

Route::middleware('throttle:authenticated')->group(function () {
    Route::get('/user/products', [ProductController::class, 'index']);
});

// Custom rate limiter definition
RateLimiter::for('public', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

RateLimiter::for('authenticated', function (Request $request) {
    $user = Auth::user();
    $limit = $user && $user->is_premium ? 300 : 120;
    return Limit::perMinute($limit)->by(optional($user)->id ?: $request->ip());
});

// Handling rate limit exceeded scenarios
Response::macro('rateLimited', function () {
    return Response::json([
        'success' => false,
        'message' => 'Too many requests. Please try again later.'
    ], 429);
});

// Test rate limiting
// Use tools like Postman or Laravel's built-in testing framework to send repeated requests and check responses.
