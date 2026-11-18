<?php

namespace App\Mcp\Tools;

use App\Models\Fluxo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('criar_fluxo')]
#[Description('Cria um fluxo novo. Chame tipos_de_no antes para conhecer o formato. A definicao e validada antes de salvar.')]
class CriarFluxo extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'nome' => $schema->string()->description('Nome do fluxo')->required(),
            'definicao' => $schema->object()->description('Objeto {inicio, nos, arestas}')->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $dados = $request->validate(['nome' => 'required|string|max:120', 'definicao' => 'required|array']);
        if ($erro = Fluxo::erroDaDefinicao($dados['definicao'])) {
            return Response::error('Definicao invalida: '.$erro);
        }
        $fluxo = Fluxo::create($dados);

        return Response::json(['id' => $fluxo->id, 'nome' => $fluxo->nome]);
    }
}
