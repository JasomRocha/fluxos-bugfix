<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolucoes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('execucao_id')->nullable()->unique()->constrained('execucoes')->nullOnDelete();
            $t->string('origem')->nullable();       // chave do Jira, e-mail, "simulado"...
            $t->string('titulo');
            $t->text('causa');
            $t->text('alteracoes');
            $t->json('arquivos')->nullable();
            $t->text('testes')->nullable();
            $t->string('branch')->nullable();
            $t->string('pr_url')->nullable();
            $t->longText('documentacao')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolucoes');
    }
};
