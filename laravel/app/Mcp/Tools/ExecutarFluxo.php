<?php

namespace App\Mcp\Tools;

use App\Models\Fluxo;
use App\Services\Execucoes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('executar_fluxo')]
#[Description('Inicia a execucao de um fluxo e devolve o id da execucao. O motor roda em segundo plano: use consultar_execucao para acompanhar.')]
class ExecutarFluxo extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'fluxo_id' => $schema->integer()->description('Id do fluxo')->required(),
            'dados' => $schema->object()->description('Variaveis da execucao, ex: {"repositorio": "...", "bug": "123"}'),
        ];
    }

    public function handle(Request $request, Execucoes $execucoes): Response
    {
        $d = $request->validate(['fluxo_id' => 'required|integer', 'dados' => 'sometimes|array']);
        $fluxo = Fluxo::find($d['fluxo_id']);
        if (! $fluxo) {
            return Response::error("Fluxo {$d['fluxo_id']} nao existe.");
        }

        return Response::json($execucoes->iniciar($fluxo, $d['dados'] ?? [])->resumo());
    }
}
