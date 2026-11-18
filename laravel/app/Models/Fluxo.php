<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fluxo extends Model
{
    public const TIPOS = ['entrada', 'git_preparar', 'agente', 'comando', 'aprovacao', 'etapa_guiada', 'abrir_pr', 'fim'];

    protected $table = 'fluxos';

    protected $fillable = ['nome', 'definicao'];

    protected $casts = ['definicao' => 'array'];

    public function execucoes(): HasMany
    {
        return $this->hasMany(Execucao::class);
    }

    /** Devolve a mensagem de erro da definicao, ou null se estiver coerente. */
    public static function erroDaDefinicao(mixed $def): ?string
    {
        if (! is_array($def) || empty($def['nos']) || ! is_array($def['nos']) || empty($def['inicio'])) {
            return 'Informe "inicio" e uma lista "nos" nao vazia.';
        }
        $ids = [];
        foreach ($def['nos'] as $no) {
            if (! is_array($no) || empty($no['id']) || empty($no['tipo'])) {
                return 'Todo no precisa de "id" e "tipo".';
            }
            if (! in_array($no['tipo'], self::TIPOS, true)) {
                return 'Tipo de no desconhecido: '.$no['tipo'].'. Validos: '.implode(', ', self::TIPOS).'.';
            }
            $ids[] = $no['id'];
        }
        if (count($ids) !== count(array_unique($ids))) {
            return 'Ha ids de nos repetidos.';
        }
        if (! in_array($def['inicio'], $ids, true)) {
            return 'O no inicial "'.$def['inicio'].'" nao existe.';
        }
        foreach ($def['arestas'] ?? [] as $a) {
            if (! in_array($a['de'] ?? null, $ids, true) || ! in_array($a['para'] ?? null, $ids, true)) {
                return 'Ligacao aponta para no inexistente: '.($a['de'] ?? '?').' -> '.($a['para'] ?? '?').'.';
            }
        }

        return null;
    }
}
