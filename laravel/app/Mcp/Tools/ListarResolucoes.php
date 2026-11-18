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

#[Name('listar_resolucoes')]
#[Description('Historico dos bugs ja resolvidos (mais recentes primeiro). Use "busca" para procurar no titulo, causa e alteracoes, por exemplo para ver se um bug parecido ja foi resolvido antes.')]
#[IsReadOnly]
class ListarResolucoes extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'busca' => $schema->string()->description('Texto a procurar'),
            'limite' => $schema->integer()->description('Maximo de itens (padrao 20, maximo 100)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $d = $request->validate(['busca' => 'sometimes|string|max:200', 'limite' => 'sometimes|integer|min:1|max:100']);
        $itens = Resolucao::query()
            ->when($d['busca'] ?? null, fn ($q, $b) => $q->where(fn ($w) => $w->where('titulo', 'like', "%{$b}%")->orWhere('causa', 'like', "%{$b}%")->orWhere('alteracoes', 'like', "%{$b}%")))
            ->latest('id')->limit($d['limite'] ?? 20)->get();

        return Response::json($itens->map->resumo()->all());
    }
}
