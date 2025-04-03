
## **Implementation Plan**
1. **Middleware Configuration:**  
   - Use Laravel’s built-in rate limiter to define limits based on IP, authenticated user, and premium user status.

2. **Custom Rate Limiter Definition (`AppServiceProvider.php`):**  
   - Define rate limiters using Laravel’s `RateLimiter::for()`, applying limits based on the request type.

3. **Route Group Setup (`api.php`):**  
   - Apply the rate limiters to different API groups (public, authenticated, and premium users).

4. **Handling Rate Limit Exceeded Scenarios:**  
   - Laravel automatically returns `429 Too Many Requests` when the limit is exceeded.
   - Customize the response format with a JSON error message.

5. **Testing the Rate Limiting Implementation:**  
   - Use Laravel’s `artisan` command and PHPUnit tests to verify limits.

---

## **1. Middleware Configuration**
Laravel provides built-in middleware for rate limiting. You can apply it in `api.php` using named limiters.

## **2. Custom Rate Limiter Definition**
Modify `App\Providers\AppServiceProvider.php`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        RateLimiter::for('public-api', function ($request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('authenticated-api', function ($request) {
            return Limit::perMinute(120)->by(optional(Auth::user())->id ?: $request->ip());
        });

        RateLimiter::for('premium-api', function ($request) {
            if (Auth::check() && Auth::user()->is_premium) {
                return Limit::perMinute(300)->by(Auth::user()->id);
            }
            return Limit::perMinute(120)->by(Auth::user()->id);
        });
    }
}
```

---

## **3. Route Group Setup**
Modify `routes/api.php`:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:public-api'])->group(function () {
    Route::get('/public-data', 'PublicController@index');
});

Route::middleware(['auth:sanctum', 'throttle:authenticated-api'])->group(function () {
    Route::get('/user-data', 'UserController@index');
});

Route::middleware(['auth:sanctum', 'throttle:premium-api'])->group(function () {
    Route::get('/premium-data', 'PremiumController@index');
});
```

---

## **4. Handling Rate Limit Exceeded Scenarios**
Laravel automatically returns a `429 Too Many Requests` response when limits are exceeded.  
To customize the response, modify `app/Exceptions/Handler.php`:

```php
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

public function render($request, Throwable $exception)
{
    if ($exception instanceof TooManyRequestsHttpException) {
        return response()->json([
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $exception->getHeaders()['Retry-After'] ?? 60
        ], 429);
    }

    return parent::render($request, $exception);
}
```

---

## **5. Testing Rate Limiting**
### **A. Artisan Command**
```bash
php artisan rate:clear
```
Run this before testing to clear any previous rate limits.

### **B. PHPUnit Test (`tests/Feature/RateLimitingTest.php`)**
```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    public function test_public_api_rate_limit()
    {
        RateLimiter::clear('public-api');
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/public-data');
        }
        $response->assertStatus(429);
    }

    public function test_authenticated_api_rate_limit()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        RateLimiter::clear('authenticated-api');
        for ($i = 0; $i < 121; $i++) {
            $response = $this->getJson('/api/user-data');
        }
        $response->assertStatus(429);
    }
}
```