<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('usuario:criar {email} {senha} {--nome=Admin}', function () {
    \App\Models\User::updateOrCreate(
        ['email' => $this->argument('email')],
        ['name' => $this->option('nome'), 'password' => \Illuminate\Support\Facades\Hash::make($this->argument('senha'))]
    );
    $this->info('Usuario salvo.');
})->purpose('Cria ou atualiza um usuario');
