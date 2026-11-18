<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AtualizarFluxo;
use App\Mcp\Tools\ConcluirEtapa;
use App\Mcp\Tools\ConsultarExecucao;
use App\Mcp\Tools\CriarFluxo;
use App\Mcp\Tools\DecidirExecucao;
use App\Mcp\Tools\ExecutarFluxo;
use App\Mcp\Tools\ListarExecucoes;
use App\Mcp\Tools\ListarFluxos;
use App\Mcp\Tools\ListarResolucoes;
use App\Mcp\Tools\ProximasEtapas;
use App\Mcp\Tools\RegistrarResolucao;
use App\Mcp\Tools\TiposDeNo;
use App\Mcp\Tools\VerFluxo;
use App\Mcp\Tools\VerResolucao;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Fluxos de correcao')]
#[Version('0.1.0')]
#[Instructions('Orquestrador de correcao de bugs. O usuario acompanha tudo na sessao. Roteiro: (1) proximas_etapas mostra o que espera por voce e listar_execucoes o andamento. (2) Para um bug novo, leia tipos_de_no (traz o modelo "correcao_guiada"), crie ou reutilize o fluxo e use executar_fluxo com dados {bug, descricao, repositorio}. (3) Execute cada etapa guiada de verdade, com suas ferramentas locais, e entregue com concluir_etapa preenchendo os campos pedidos; use sucesso=false se nao conseguir. (4) Em aprovacoes, mostre ao usuario o que mudou e so chame decidir_execucao com a decisao dele. Nunca aprove por conta propria. (5) Ao final, registrar_resolucao grava causa, mudanca, testes, PR e documentacao. Antes de investigar, procure bugs parecidos com listar_resolucoes.')]
class FluxosServer extends Server
{
    protected array $tools = [
        TiposDeNo::class,
        ListarFluxos::class,
        VerFluxo::class,
        CriarFluxo::class,
        AtualizarFluxo::class,
        ExecutarFluxo::class,
        ListarExecucoes::class,
        ConsultarExecucao::class,
        DecidirExecucao::class,
        ProximasEtapas::class,
        ConcluirEtapa::class,
        RegistrarResolucao::class,
        ListarResolucoes::class,
        VerResolucao::class,
    ];
}
