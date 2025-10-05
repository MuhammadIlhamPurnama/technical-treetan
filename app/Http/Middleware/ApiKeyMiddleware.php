<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from header
        $apiKey = $request->header('X-API-Key');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
                'error' => 'Missing X-API-Key header'
            ], 401);
        }

        // Validate API key
        $validApiKey = config('app.api_key');
        
        if (!$validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key validation not configured',
                'error' => 'Server configuration error'
            ], 500);
        }

        if ($apiKey !== $validApiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key',
                'error' => 'Unauthorized access'
            ], 401);
        }

        return $next($request);
    }
}
