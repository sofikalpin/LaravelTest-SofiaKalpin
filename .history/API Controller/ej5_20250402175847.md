```md
# Optimización de API en Laravel

## 1. Controlador: `ProductController.php`
```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProductResource;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $cacheKey = "products_page_{$page}";

        $products = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            return Product::with(['category', 'tags', 'reviews.user'])->paginate(10);
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
```
```

## 2. API Resource: `ProductResource.php`
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'description' => $this->description,
            'category' => $this->category->name ?? null,
            'tags' => $this->tags->pluck('name'),
            'reviews' => $this->reviews->map(function ($review) {
                return [
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => $review->user->name ?? 'Anonymous'
                ];
            })
        ];
    }
}
```
```

## 3. Rutas: `routes/api.php`
```php
use App\Http\Controllers\API\ProductController;

Route::get('/products', [ProductController::class, 'index']);
```
```