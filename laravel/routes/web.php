<?php

use App\Http\Controllers\ExecucaoController;
use App\Http\Controllers\FluxoController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'formulario'])->name('login');
    Route::post('/login', [LoginController::class, 'entrar'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/sair', [LoginController::class, 'sair'])->name('sair');
    Route::redirect('/', '/fluxos');
    Route::resource('fluxos', FluxoController::class)->parameters(['fluxos' => 'fluxo']);
    Route::post('/fluxos/{fluxo}/execucoes', [ExecucaoController::class, 'store'])->name('execucoes.store');
    Route::get('/execucoes/{execucao}', [ExecucaoController::class, 'show'])->name('execucoes.show');
    Route::post('/execucoes/{execucao}/decisao', [ExecucaoController::class, 'decisao'])->name('execucoes.decisao');
});
