<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Semua trafik (termasuk callback Tripay) lewat edge Cloudflare,
        // jadi percaya X-Forwarded-For dari proxy agar IP asli tercatat
        // di throttle + activity log. Origin sendiri difirewall hanya
        // untuk IP Cloudflare (lihat PAYMENT.md).
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'proxied' => \App\Http\Middleware\EnsureProxied::class,
        ]);
        $middleware->append(\App\Http\Middleware\NoIndex::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
