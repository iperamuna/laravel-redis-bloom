<?php

namespace Iperamuna\LaravelRedisBloom\Middleware;

use Closure;
use Illuminate\Http\Request;
use Iperamuna\LaravelRedisBloom\Facades\Bloom;

class BloomMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Usage:
     * Route::middleware('bloom:emails,email')->post(...)
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $filter, string $field = 'email')
    {
        $value = $request->input($field);

        if (! $value) {
            return $next($request);
        }

        try {
            $exists = Bloom::filter($filter)->exists($value);

            if ($exists) {
                return response()->json([
                    'message' => 'Possible duplicate detected (Bloom filter match).',
                ], 409);
            }
        } catch (\Throwable $e) {
            // Silently continue if Bloom fails in middleware,
            // or we could log it.
        }

        return $next($request);
    }
}
