# Debugging API Authentication Issues in Laravel Passport

## Potential Issues

### Token Expiration
The most common cause of intermittent 401 errors is token expiration. By default, Laravel Passport access tokens expire after a short period.

**Debugging Steps:**
- Check  `config/passport.php` file for token lifetimes.
- Add logging to track token creation and expiration.
- Verify if failures correlate with token age.

**Solution:**
```php
// In AppServiceProvider or AuthServiceProvider
use Laravel\Passport\Passport;

public function boot()
{
    // Increase token lifetime
    Passport::tokensExpireIn(now()->addDays(15));
    Passport::refreshTokensExpireIn(now()->addDays(30));
}
```

### 2. Token Revocation
Tokens might be getting revoked unexpectedly.

**Debugging:**
- Check logs for `token_revoked` events.
- Examine if any code is calling `$token->revoke()` inadvertently.
- Look for automated cleanup jobs.

### 3. Incorrect Auth Guard Configuration

**Debugging:**
- Check your `config/auth.php` configuration.
- Verify the guard definitions and default settings.

**Solution:**
```php
// In config/auth.php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
    // ...
],
```

### 4. Race Conditions with Token Storage
If you're using database token storage, high traffic might cause race conditions.

**Solution:**
- Consider implementing token caching.
- Ensure the database is properly indexed.

### 5. Middleware Configuration Issues
The `auth:api` middleware might be improperly configured.

**Debugging:**
- Examine other middleware in the stack that might interfere.
- Check global middleware in `app/Http/Kernel.php`.

### 6. Client Secret Issues
If client credentials are being used, check if the client secret is properly stored and validated.

### 7. Cross-Domain/CORS Issues
If the API is called from different domains, CORS settings might affect authentication.

**Solution:**
```php
// In config/cors.php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Restrict appropriately
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true, // Important for auth
];
```

## Comprehensive Debugging Approach

### 1. Add Detailed Logging
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class PassportDebugMiddleware
{
    public function handle($request, Closure $next)
    {
        // Log the request and token information
        $bearerToken = $request->bearerToken();
        $hasToken = !empty($bearerToken);
        
        Log::channel('passport')->info('API Request', [
            'uri' => $request->getRequestUri(),
            'method' => $request->getMethod(),
            'has_token' => $hasToken,
            'token_first_chars' => $hasToken ? substr($bearerToken, 0, 10) . '...' : null,
        ]);
        
        // Process the request
        $response = $next($request);
        
        // Log the response status
        Log::channel('passport')->info('API Response', [
            'uri' => $request->getRequestUri(),
            'status' => $response->getStatusCode(),
            'has_token' => $hasToken,
        ]);
        
        // If unauthorized, log more details
        if ($response->getStatusCode() == 401) {
            try {
                // Try to validate the token directly for debugging
                if ($hasToken) {
                    $tokenId = app('db')->table('oauth_access_tokens')
                        ->where('id', $bearerToken)
                        ->orWhere('id', hash('sha256', $bearerToken))
                        ->first();
                    
                    Log::channel('passport')->warning('401 Unauthorized', [
                        'token_exists' => !is_null($tokenId),
                        'token_details' => !is_null($tokenId) ? [
                            'client_id' => $tokenId->client_id,
                            'user_id' => $tokenId->user_id,
                            'expires_at' => $tokenId->expires_at,
                            'revoked' => $tokenId->revoked,
                        ] : null
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('passport')->error('Error debugging token', [
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $response;
    }
}
```

### 2. Register the Middleware
```php
// In app/Http/Kernel.php
protected $middlewareGroups = [
    'api' => [
        // Other middleware
        \App\Http\Middleware\PassportDebugMiddleware::class,
    ],
];
```

### 3. Set Up a Dedicated Logging Channel
```php
// In config/logging.php
'channels' => [
    'passport' => [
        'driver' => 'daily',
        'path' => storage_path('logs/passport.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],
```

### Add Token Refresh Logic to Your Frontend
```javascript
// Example with Axios
axios.interceptors.response.use(
  response => response,
  error => {
    const originalRequest = error.config;
    
    // If it's a 401 error and we haven't tried refreshing yet
    if (error.response.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;
      
      // Call your refresh token endpoint
      return axios.post('/api/auth/refresh')
        .then(res => {
          // Update the token
          localStorage.setItem('token', res.data.access_token);
          
          // Update the authorization header
          axios.defaults.headers.common['Authorization'] = 'Bearer ' + res.data.access_token;
          originalRequest.headers['Authorization'] = 'Bearer ' + res.data.access_token;
          
          // Retry the original request
          return axios(originalRequest);
        });
    }
    
    return Promise.reject(error);
  }
);
```

