<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança para respostas dinâmicas.
 *
 * Não envia HSTS nem exige HTTPS: o acesso é HTTP por túnel SSH e por serviço
 * onion (o Tor já cifra o trajeto). A política de conteúdo bloqueia qualquer
 * recurso externo: só scripts, estilos e imagens do próprio painel.
 */
class CabecalhosDeSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        $politica = implode('; ', [
            "default-src 'none'",
            "script-src 'self'",
            "style-src 'self'",
            "img-src 'self'",
            "font-src 'self'",
            "connect-src 'self'",
            "form-action 'self'",
            "base-uri 'none'",
            "frame-ancestors 'none'",
            "object-src 'none'",
        ]);

        $resposta->headers->set('Content-Security-Policy', $politica);
        $resposta->headers->set('X-Content-Type-Options', 'nosniff');
        $resposta->headers->set('X-Frame-Options', 'DENY');
        $resposta->headers->set('Referrer-Policy', 'no-referrer');
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $resposta->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $resposta->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $resposta->headers->set('Cache-Control', 'no-store, private');
        $resposta->headers->set('Pragma', 'no-cache');

        return $resposta;
    }
}
