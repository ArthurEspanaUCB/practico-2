<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevenir sniffing de contenido
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevenir Clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Filtro XSS básico
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS (Solo en producción)
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Content Security Policy (CSP)
        // Permite Vue, Vite (HMR), Google Fonts/FontAwesome (si se usan), e imágenes blob.
        $csp = "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173 http://127.0.0.1:5173; ".
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com http://localhost:5173 http://127.0.0.1:5173; ".
            "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:; ".
            "img-src 'self' data: blob:; ".
            "connect-src 'self' ws: wss: http://localhost:5173 http://127.0.0.1:5173 ws://127.0.0.1:5173 http://cdn.datatables.net https://cdn.datatables.net;";

        $response->headers->set('Content-Security-Policy', $csp);

        // Remover cabecera de versión
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
