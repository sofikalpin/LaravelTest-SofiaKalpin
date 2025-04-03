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
use Illuminate\Cache\RateLimiter as RateLimiterService;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Validator;

// Product controller with caching
class ProductController extends Controller
{
    protected $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function index(Request $request)
    {
        // ✅ Request validation
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $validator->errors(),
            ], 400);
        }

        // Cache to optimize the query
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

// Resource transformation
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

// Rate limiting configuration
Route::middleware('throttle:public')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});

Route::middleware('throttle:authenticated')->group(function () {
    Route::get('/user/products', [ProductController::class, 'index']);
});

RateLimiter::for('public', function (Request $request) {
    return Limit::perMinute(60)->by($request->ip());
});

RateLimiter::for('authenticated', function (Request $request) {
    $user = Auth::user();
    $limit = $user && $user->is_premium ? 300 : 120;
    return Limit::perMinute($limit)->by(optional($user)->id ?: $request->ip());
});

// Custom Response for Rate Limiting
Response::macro('rateLimited', function () {
    return Response::json([
        'success' => false,
        'message' => 'Too many requests. Please try again later.'
    ], 429);
});

// Payment Gateway Interface
namespace App\Contracts;

interface PaymentGatewayInterface
{
    public function charge(float $amount, array $paymentDetails);
    public function refund(string $transactionId);
}

// Stripe Payment Implementation
namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Exception;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected $apiKey;
    
    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }
    
    public function charge(float $amount, array $paymentDetails)
    {
        try {
            // Stripe-specific charge implementation
        } catch (Exception $e) {
            throw new Exception("Stripe Payment Failed: " . $e->getMessage());
        }
    }
    
    public function refund(string $transactionId)
    {
        try {
            // Stripe-specific refund implementation
        } catch (Exception $e) {
            throw new Exception("Stripe Refund Failed: " . $e->getMessage());
        }
    }
}

// PayPal Payment Implementation
class PayPalPaymentGateway implements PaymentGatewayInterface
{
    protected $clientId;
    protected $clientSecret;
    
    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    
    public function charge(float $amount, array $paymentDetails)
    {
        try {
            // PayPal-specific charge implementation
        } catch (Exception $e) {
            throw new Exception("PayPal Payment Failed: " . $e->getMessage());
        }
    }
    
    public function refund(string $transactionId)
    {
        try {
            // PayPal-specific refund implementation
        } catch (Exception $e) {
            throw new Exception("PayPal Refund Failed: " . $e->getMessage());
        }
    }
}

// Register Payment Service
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PaymentGatewayInterface;
use App\Services\StripePaymentGateway;
use App\Services\PayPalPaymentGateway;
use Illuminate\Support\Facades\Config;

class PaymentServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            $gateway = Config::get('payment.default_gateway');
            
            if ($gateway === 'stripe') {
                return new StripePaymentGateway(Config::get('payment.stripe.api_key'));
            } elseif ($gateway === 'paypal') {
                return new PayPalPaymentGateway(Config::get('payment.paypal.client_id'), Config::get('payment.paypal.client_secret'));
            }

            throw new \Exception('Invalid payment gateway selected.');
        });
    }
}

// Configuration file example
return [
    'default_gateway' => env('PAYMENT_GATEWAY', 'stripe'),
    'stripe' => [
        'api_key' => env('STRIPE_API_KEY'),
    ],
    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
    ],
];