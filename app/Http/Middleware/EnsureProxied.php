<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Halaman checkout hanya boleh diakses lewat proxy resmi
 * (Pages Functions wasender.pandusolusi.com) yang menyertakan
 * header X-Proxy-Secret. Selain itu → 404 (disembunyikan).
 */
class EnsureProxied
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('tripay.proxy_secret', '');

        if ($secret === '' && app()->environment('local', 'testing')) {
            return $next($request);
        }

        if ($secret !== '' && hash_equals($secret, (string) $request->header('X-Proxy-Secret'))) {
            return $next($request);
        }

        abort(404);
    }
}
