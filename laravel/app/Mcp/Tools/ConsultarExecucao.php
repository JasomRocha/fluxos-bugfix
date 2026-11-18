<?php

namespace App\Mcp\Tools;

use App\Models\Execucao;
use App\Services\Execucoes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('consultar_execucao')]
#[Description('Mostra status, historico, dados e a pergunta pendente de uma execucao. Status: executando, aguardando (espera decidir_execucao), concluida ou erro.')]
#[IsReadOnly]
class ConsultarExecucao extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Id da execucao')->required()];
    }

    public function handle(Request $request, Execucoes $execucoes): Response
    {
        $id = $request->validate(['id' => 'required|integer'])['id'];
        $execucao = Execucao::find($id);

        return $execucao
            ? Response::json($execucoes->sincronizar($execucao)->resumo())
            : Response::error("Execucao {$id} nao existe.");
    }
}
