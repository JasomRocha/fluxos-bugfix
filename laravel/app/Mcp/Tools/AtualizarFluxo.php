<?php

namespace App\Mcp\Tools;

use App\Models\Fluxo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('atualizar_fluxo')]
#[Description('Altera o nome e/ou a definicao de um fluxo existente. A definicao enviada substitui a anterior por inteiro.')]
class AtualizarFluxo extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Id do fluxo')->required(),
            'nome' => $schema->string()->description('Novo nome'),
            'definicao' => $schema->object()->description('Definicao completa {inicio, nos, arestas}'),
        ];
    }

    public function handle(Request $request): Response
    {
        $d = $request->validate(['id' => 'required|integer', 'nome' => 'sometimes|string|max:120', 'definicao' => 'sometimes|array']);
        $fluxo = Fluxo::find($d['id']);
        if (! $fluxo) {
            return Response::error("Fluxo {$d['id']} nao existe.");
        }
        if (isset($d['definicao']) && ($erro = Fluxo::erroDaDefinicao($d['definicao']))) {
            return Response::error('Definicao invalida: '.$erro);
        }
        $fluxo->update(array_intersect_key($d, array_flip(['nome', 'definicao'])));

        return Response::json(['id' => $fluxo->id, 'nome' => $fluxo->nome]);
    }
}
