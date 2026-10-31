<?php

use Illuminate\Support\Str;

return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => (bool) env('SESSION_ENCRYPT', true),
    'files' => storage_path('framework/sessions'),
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', Str::slug('painel-pessoal').'-sessao'),
    'path' => '/',
    'domain' => null,

    // Secure só funciona se o navegador acessar por HTTPS. O acesso local por
    // túnel SSH (http://127.0.0.1) e por serviço onion (http://....onion) é HTTP,
    // então o padrão é false. Ative (true) apenas se houver HTTPS de ponta a ponta.
    'secure' => env('SESSION_SECURE_COOKIE', false),

    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
];
