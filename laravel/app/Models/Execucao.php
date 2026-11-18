<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Execucao extends Model
{
    protected $table = 'execucoes';

    protected $fillable = ['fluxo_id', 'motor_id', 'status', 'dados', 'historico', 'pendencia', 'erro'];

    protected $casts = ['dados' => 'array', 'historico' => 'array', 'pendencia' => 'array'];

    public function fluxo(): BelongsTo
    {
        return $this->belongsTo(Fluxo::class);
    }

    public function emAndamento(): bool
    {
        return in_array($this->status, ['executando', 'aguardando'], true);
    }

    /** Formato enxuto para clientes externos; textos longos sao cortados. */
    public function resumo(int $limiteTexto = 2000): array
    {
        $corta = fn ($v) => is_string($v) && mb_strlen($v) > $limiteTexto ? mb_substr($v, 0, $limiteTexto).'... (cortado)' : $v;

        return [
            'id' => $this->id,
            'fluxo_id' => $this->fluxo_id,
            'status' => $this->status,
            'pendencia' => $this->pendencia,
            'erro' => $this->erro,
            'historico' => $this->historico ?? [],
            'dados' => array_map($corta, $this->dados ?? []),
        ];
    }
}
