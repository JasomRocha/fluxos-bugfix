<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fluxo extends Model
{
    protected $table = 'fluxos';

    protected $fillable = ['nome', 'definicao'];

    protected $casts = ['definicao' => 'array'];

    public function execucoes(): HasMany
    {
        return $this->hasMany(Execucao::class);
    }
}
