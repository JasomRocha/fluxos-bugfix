<?php

namespace App\Mcp\Tools;

use App\Models\Resolucao;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('ver_resolucao')]
#[Description('Devolve uma resolucao completa, inclusive a documentacao em markdown.')]
#[IsReadOnly]
class VerResolucao extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Id da resolucao')->required()];
    }

    public function handle(Request $request): Response
    {
        $id = $request->validate(['id' => 'required|integer'])['id'];
        $r = Resolucao::find($id);

        return $r ? Response::json($r->resumo() + ['documentacao' => $r->documentacao]) : Response::error("Resolucao {$id} nao existe.");
    }
}
