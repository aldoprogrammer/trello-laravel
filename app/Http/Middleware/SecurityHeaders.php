<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // SENIOR TOUCH: Ambil Tracing ID dari Nginx dan balikin ke User
        if ($request->header('X-Request-ID')) {
            $response->headers->set('X-Request-ID', $request->header('X-Request-ID'));
        }

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        $isSwaggerRoute = $request->is('api/documentation')
            || $request->is('api/docs')
            || $request->is('docs/asset/*');
        $isTelescopeRoute = $request->is('telescope')
            || $request->is('telescope/*');

        if ($isSwaggerRoute) {
            // Swagger UI needs inline scripts/styles to render.
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data:; font-src 'self' data:; connect-src 'self' https://unpkg.com; object-src 'none';"
            );
        } elseif ($isTelescopeRoute) {
            // Telescope uses inline module code and runtime evaluation.
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' https://fonts.bunny.net; img-src 'self' data:; font-src 'self' data: https://fonts.bunny.net; connect-src 'self'; object-src 'none';"
            );
        } else {
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self'; object-src 'none';");
        }

        return $response;
    }
}
