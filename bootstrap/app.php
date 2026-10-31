<?php

use App\Http\Middleware\CabecalhosDeSeguranca;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cabeçalhos de segurança em toda resposta dinâmica.
        $middleware->web(append: [CabecalhosDeSeguranca::class]);

        // Visitantes vão para o login; quem já entrou vai para o início.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
