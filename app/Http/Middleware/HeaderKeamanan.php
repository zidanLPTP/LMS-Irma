<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HeaderKeamanan
{
    /**
     * Terapkan header keamanan enterprise OWASP pada setiap respon HTTP.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Header Pertahanan Standar OWASP
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy (dimulai dengan mode Report-Only sesuai rekomendasi fase audit)
        // Sumber frame YouTube nocookie, worker blob: untuk PDF.js, img-src data: & self, font Google & self
        $csp = "default-src 'self'; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; "
            ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            ."font-src 'self' https://fonts.gstatic.com data:; "
            ."img-src 'self' data:; "
            ."frame-src 'self' https://www.youtube-nocookie.com; "
            ."worker-src 'self' blob:; "
            ."connect-src 'self';";

        $response->headers->set('Content-Security-Policy-Report-Only', $csp);

        return $response;
    }
}
