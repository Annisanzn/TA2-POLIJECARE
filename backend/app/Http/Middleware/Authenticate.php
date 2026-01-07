<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        return Route::has('login') ? route('login') : null;
    }

    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            throw new AuthenticationException('Unauthenticated.', $guards);
        }

        parent::unauthenticated($request, $guards);
    }
}
