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
}
