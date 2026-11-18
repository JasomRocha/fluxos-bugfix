<?php

namespace App\Mcp\Tools;

use App\Models\Execucao;
use App\Services\Execucoes;
use DomainException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('concluir_etapa')]
#[Description('Entrega o resultado de uma etapa guiada que voce executou e faz o fluxo seguir. Use sucesso=false se nao conseguiu (o fluxo toma o caminho de falha). Preencha "resultado" com os campos pedidos em campos_esperados.')]
class ConcluirEtapa extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'execucao_id' => $schema->integer()->description('Id da execucao')->required(),
            'sucesso' => $schema->boolean()->description('false se a etapa falhou. Padrao true'),
            'resultado' => $schema->object()->description('Objeto com os campos esperados, ex: {"causa": "...", "arquivos": ["a.py"]}'),
        ];
    }

    public function handle(Request $request, Execucoes $execucoes): Response
    {
        $d = $request->validate(['execucao_id' => 'required|integer', 'sucesso' => 'sometimes|boolean', 'resultado' => 'sometimes|array']);
        $execucao = Execucao::find($d['execucao_id']);
        if (! $execucao) {
            return Response::error("Execucao {$d['execucao_id']} nao existe.");
        }
        try {
            return Response::json($execucoes->responder($execucao, $d['sucesso'] ?? true, $d['resultado'] ?? [])->resumo());
        } catch (DomainException $e) {
            return Response::error($e->getMessage());
        }
    }
}
