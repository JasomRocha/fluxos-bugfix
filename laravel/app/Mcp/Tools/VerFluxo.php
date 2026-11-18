<?php

namespace App\Mcp\Tools;

use App\Models\Fluxo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_fluxo')]
#[Description('Devolve a definicao completa (nos e arestas) de um fluxo.')]
#[IsReadOnly]
class VerFluxo extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Id do fluxo')->required()];
    }

    public function handle(Request $request): Response
    {
        $id = $request->validate(['id' => 'required|integer'])['id'];
        $fluxo = Fluxo::find($id);

        return $fluxo
            ? Response::json(['id' => $fluxo->id, 'nome' => $fluxo->nome, 'definicao' => $fluxo->definicao])
            : Response::error("Fluxo {$id} nao existe.");
    }
}
