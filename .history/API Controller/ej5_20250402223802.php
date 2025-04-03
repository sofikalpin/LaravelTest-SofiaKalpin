<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->query('page', 1);
        $cacheKey = "products_page_{$page}";

        // Cache the query to reduce database load and improve response time
        $products = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Product::with([
                'category:id,name', 
                'tags:id,name', 
                'reviews:id,product_id,rating,comment,user_id', 
                'reviews.user:id,name'
            ])
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

// Resource to structure the API response
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
            'reviews' => $this->reviews->map(fn($review) => [
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user' => $review->user->name,
            ]),
        ];
    }
}
