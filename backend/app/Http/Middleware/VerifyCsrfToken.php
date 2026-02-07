<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // CSRF token endpoints - these need to be accessible without CSRF verification
        '/csrf-token',
        '/refresh-csrf',
        '/csrf-cookie', // Laravel XSRF cookie route
        '/sanctum/csrf-cookie', // Sanctum CSRF cookie endpoint
    ];


}
