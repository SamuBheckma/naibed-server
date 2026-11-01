<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotaController;

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
    Route::get('/notas', [NotaController::class, 'index'])->name('notas.index');
    Route::get('/notas/criar', [NotaController::class, 'create'])->name('notas.create');
    Route::post('/notas', [NotaController::class, 'store'])->name('notas.store');
    Route::get('/notas/{nota}/editar', [NotaController::class, 'edit'])->whereNumber('nota')->name('notas.edit');
    Route::put('/notas/{nota}', [NotaController::class, 'update'])->whereNumber('nota')->name('notas.update');
    Route::get('/notas/{nota}/excluir', [NotaController::class, 'confirmDestroy'])->whereNumber('nota')->name('notas.confirm-destroy');
    Route::delete('/notas/{nota}', [NotaController::class, 'destroy'])->whereNumber('nota')->name('notas.destroy');
    Route::post('/sair', [LoginController::class, 'sair'])->name('sair');
});
