<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// Não há cadastro público nem recuperação de senha por e-mail.
// A única forma de criar ou redefinir o administrador é o comando
// Artisan `painel:admin`, executado no servidor.

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [LoginController::class, 'mostrar'])->name('login');
    Route::post('/entrar', [LoginController::class, 'entrar'])->name('login.enviar');
});

// Tudo abaixo exige sessão autenticada, verificada no backend.
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('inicio');
    Route::get('/projetos', [ProjectController::class, 'index'])->name('projetos');
    Route::post('/sair', [LoginController::class, 'sair'])->name('sair');
});
