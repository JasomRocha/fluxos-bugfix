<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fluxos', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->json('definicao');
            $t->timestamps();
        });

        Schema::create('execucoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fluxo_id')->constrained('fluxos')->cascadeOnDelete();
            $t->string('motor_id')->nullable()->index();
            $t->string('status')->default('executando');
            $t->json('dados')->nullable();
            $t->json('historico')->nullable();
            $t->json('pendencia')->nullable();
            $t->text('erro')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execucoes');
        Schema::dropIfExists('fluxos');
    }
};
