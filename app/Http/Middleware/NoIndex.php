<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Panel ini privat: minta search engine & cache untuk tidak mengindeks. */
class NoIndex
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request)->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
