<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OperatorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'operator') {
            return response()->json([
                'message' => 'Unauthorized (Operator only)'
            ], 403);
        }

        return $next($request);
    }
}
