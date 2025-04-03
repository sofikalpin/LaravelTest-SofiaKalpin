<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ComplexAuthorization
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $resource = $request->route('resource'); // Assuming resource ID in route
        $currentHour = Carbon::now()->hour;

        // Ensure user is authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Check role-based permissions
        if (!$this->checkUserRole($user)) {
            return response()->json(['error' => 'Insufficient role permissions'], 403);
        }

        // Check department assignment
        if (!$this->checkDepartmentAssignment($user, $resource)) {
            return response()->json(['error' => 'Access denied based on department'], 403);
        }

        // Check resource ownership
        if (!$this->checkResourceOwnership($user, $resource)) {
            return response()->json(['error' => 'You do not own this resource'], 403);
        }

        // Time-based restrictions (e.g., only allow access between 9 AM - 6 PM)
        if ($currentHour < 9 || $currentHour > 18) {
            return response()->json(['error' => 'Access restricted to business hours'], 403);
        }

        return $next($request);
    }

    private function checkUserRole($user)
    {
        return in_array($user->role, ['admin', 'manager', 'staff']);
    }

    private function checkDepartmentAssignment($user, $resource)
    {
        return $user->department_id === $resource->department_id;
    }

    private function checkResourceOwnership($user, $resource)
    {
        return $user->id === $resource->owner_id;
    }
}

// Register middleware in Kernel.php
protected $routeMiddleware = [
    'complex.auth' => \App\Http\Middleware\ComplexAuthorization::class,
];

// Apply middleware in routes
Route::middleware(['auth', 'complex.auth'])->group(function () {
    Route::get('/resources/{resource}', 'ResourceController@show');
});
