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

#[Name('decidir_execucao')]
#[Description('Aprova ou recusa uma execucao que esta aguardando decisao. Aprovar pode abrir PR e outras acoes com efeito externo: so aprove depois de revisar o historico e os dados com consultar_execucao.')]
class DecidirExecucao extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Id da execucao')->required(),
            'aprovado' => $schema->boolean()->description('true aprova, false recusa')->required(),
        ];
    }

    public function handle(Request $request, Execucoes $execucoes): Response
    {
        $d = $request->validate(['id' => 'required|integer', 'aprovado' => 'required|boolean']);
        $execucao = Execucao::find($d['id']);
        if (! $execucao) {
            return Response::error("Execucao {$d['id']} nao existe.");
        }
        try {
            return Response::json($execucoes->decidir($execucao, $d['aprovado'])->resumo());
        } catch (DomainException $e) {
            return Response::error($e->getMessage());
        }
    }
}
