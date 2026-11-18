<?php

namespace App\Mcp\Tools;

use App\Models\Execucao;
use App\Services\Execucoes;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('proximas_etapas')]
#[Description('Mostra o que esta esperando por voce: etapas guiadas para executar (com a instrucao) e aprovacoes que dependem do usuario. Chame no inicio da sessao e depois de cada concluir_etapa.')]
#[IsReadOnly]
class ProximasEtapas extends Tool
{
    public function handle(Request $request, Execucoes $execucoes): Response
    {
        $etapas = [];
        $aprovacoes = [];
        $emAndamento = Execucao::with('fluxo:id,nome')->whereIn('status', ['executando', 'aguardando'])->latest('id')->limit(30)->get();

        foreach ($emAndamento as $e) {
            $execucoes->sincronizar($e);
            $p = $e->pendencia ?? [];
            $base = ['execucao_id' => $e->id, 'fluxo' => $e->fluxo?->nome, 'dados' => $e->resumo(600)['dados']];
            if ($e->status === 'aguardando' && ($p['tipo'] ?? null) === 'etapa_guiada') {
                $etapas[] = $base + ['titulo' => $p['titulo'] ?? '', 'instrucao' => $p['instrucao'] ?? '', 'campos_esperados' => $p['campos'] ?? [], 'historico' => $e->historico];
            } elseif ($e->status === 'aguardando' && ($p['tipo'] ?? 'aprovacao') === 'aprovacao') {
                $aprovacoes[] = $base + ['pergunta' => $p['pergunta'] ?? '', 'historico' => $e->historico];
            }
        }

        return Response::json([
            'etapas_para_executar' => $etapas,
            'aprovacoes_do_usuario' => $aprovacoes,
            'em_andamento_no_motor' => $emAndamento->where('status', 'executando')->pluck('id')->values(),
        ]);
    }
}
