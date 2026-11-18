<?php

namespace App\Mcp\Tools;

use App\Models\Execucao;
use App\Models\Resolucao;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('registrar_resolucao')]
#[Description('Grava no historico como um bug foi resolvido: causa, o que mudou, testes, branch, PR e documentacao em markdown. Chame ao final do trabalho. Chamar de novo para a mesma execucao atualiza o registro.')]
class RegistrarResolucao extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'execucao_id' => $schema->integer()->description('Execucao que originou a correcao'),
            'origem' => $schema->string()->description('De onde veio o bug: chave do Jira, e-mail, "simulado"...'),
            'titulo' => $schema->string()->description('Titulo curto do bug')->required(),
            'causa' => $schema->string()->description('Causa raiz')->required(),
            'alteracoes' => $schema->string()->description('O que foi alterado e por que')->required(),
            'arquivos' => $schema->array()->items($schema->string())->description('Arquivos alterados'),
            'testes' => $schema->string()->description('Como foi validado e o resultado'),
            'branch' => $schema->string(),
            'pr_url' => $schema->string(),
            'documentacao' => $schema->string()->description('Documentacao da correcao em markdown'),
        ];
    }

    public function handle(Request $request): Response
    {
        $d = $request->validate([
            'execucao_id' => 'sometimes|integer', 'origem' => 'sometimes|string|max:200', 'titulo' => 'required|string|max:255',
            'causa' => 'required|string', 'alteracoes' => 'required|string', 'arquivos' => 'sometimes|array', 'arquivos.*' => 'string',
            'testes' => 'sometimes|string', 'branch' => 'sometimes|string|max:255', 'pr_url' => 'sometimes|string|max:500',
            'documentacao' => 'sometimes|string',
        ]);
        if (isset($d['execucao_id']) && ! Execucao::whereKey($d['execucao_id'])->exists()) {
            return Response::error("Execucao {$d['execucao_id']} nao existe.");
        }
        $chave = isset($d['execucao_id']) ? ['execucao_id' => $d['execucao_id']] : [];
        $r = $chave ? Resolucao::updateOrCreate($chave, $d) : Resolucao::create($d);

        return Response::json($r->resumo());
    }
}
