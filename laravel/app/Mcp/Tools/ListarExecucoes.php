<?php

namespace App\Mcp\Tools;

use App\Models\Execucao;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('listar_execucoes')]
#[Description('Lista as execucoes mais recentes, opcionalmente de um fluxo e/ou com um status. Util para achar as que aguardam decisao.')]
#[IsReadOnly]
class ListarExecucoes extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'fluxo_id' => $schema->integer()->description('Filtra por fluxo'),
            'status' => $schema->string()->enum(['executando', 'aguardando', 'concluida', 'erro'])->description('Filtra por status'),
            'limite' => $schema->integer()->description('Maximo de itens (padrao 20, maximo 100)'),
        ];
    }

    public function handle(Request $request): Response
    {
        $d = $request->validate(['fluxo_id' => 'sometimes|integer', 'status' => 'sometimes|in:executando,aguardando,concluida,erro', 'limite' => 'sometimes|integer|min:1|max:100']);
        $itens = Execucao::query()
            ->when($d['fluxo_id'] ?? null, fn ($q, $v) => $q->where('fluxo_id', $v))
            ->when($d['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest('id')->limit($d['limite'] ?? 20)->get(['id', 'fluxo_id', 'status', 'created_at']);

        return Response::json($itens->all());
    }
}
