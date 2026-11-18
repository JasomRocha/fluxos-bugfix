<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resolucao extends Model
{
    protected $table = 'resolucoes';

    protected $fillable = ['execucao_id', 'origem', 'titulo', 'causa', 'alteracoes', 'arquivos', 'testes', 'branch', 'pr_url', 'documentacao'];

    protected $casts = ['arquivos' => 'array'];

    public function execucao(): BelongsTo
    {
        return $this->belongsTo(Execucao::class);
    }

    public function resumo(): array
    {
        return $this->only(['id', 'execucao_id', 'origem', 'titulo', 'causa', 'alteracoes', 'arquivos', 'testes', 'branch', 'pr_url'])
            + ['tem_documentacao' => filled($this->documentacao), 'criada_em' => $this->created_at?->toIso8601String()];
    }
}
