<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('tipos_de_no')]
#[Description('Catalogo dos tipos de no que um fluxo pode usar, com os campos de config e as rotas que cada um produz. Leia antes de criar ou alterar um fluxo.')]
#[IsReadOnly]
class TiposDeNo extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'formato' => 'Fluxo = {inicio, nos:[{id,tipo,config,posicao?}], arestas:[{de,para,quando?}]}. '
                .'Uma aresta com "quando" so e seguida se a rota do no anterior for igual. Sem "quando" e o caminho padrao. '
                .'Use {variavel} em textos da config para ler os dados da execucao.',
            'tipos' => [
                'entrada' => ['config' => [], 'rotas' => []],
                'git_preparar' => ['config' => ['repositorio', 'branch (padrao correcao/{bug})', 'pasta_base'], 'grava' => ['diretorio', 'branch'], 'rotas' => []],
                'agente' => ['config' => ['prompt', 'diretorio', 'modelo', 'max_turnos', 'insistencias', 'comandos_permitidos', 'chave'], 'rotas' => ['ok', 'limite', 'sem_alteracao']],
                'comando' => ['config' => ['comando', 'diretorio', 'chave', 'limite_segundos'], 'grava' => ['<chave>_saida', '<chave>_codigo'], 'rotas' => ['ok', 'falha']],
                'aprovacao' => ['config' => ['pergunta'], 'obs' => 'pausa a execucao ate alguem decidir (decidir_execucao)', 'rotas' => ['aprovado', 'recusado']],
                'abrir_pr' => ['config' => ['titulo', 'corpo'], 'grava' => ['pr_url'], 'rotas' => ['ok', 'sem_alteracao']],
                'fim' => ['config' => [], 'rotas' => []],
            ],
        ]);
    }
}
