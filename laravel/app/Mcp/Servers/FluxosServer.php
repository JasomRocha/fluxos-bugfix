<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AtualizarFluxo;
use App\Mcp\Tools\ConsultarExecucao;
use App\Mcp\Tools\CriarFluxo;
use App\Mcp\Tools\DecidirExecucao;
use App\Mcp\Tools\ExecutarFluxo;
use App\Mcp\Tools\ListarExecucoes;
use App\Mcp\Tools\ListarFluxos;
use App\Mcp\Tools\TiposDeNo;
use App\Mcp\Tools\VerFluxo;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Fluxos de correcao')]
#[Version('0.1.0')]
#[Instructions('Controla fluxos que automatizam a correcao de bugs. Para criar um fluxo, leia tipos_de_no primeiro. Para executar: executar_fluxo, depois consultar_execucao ate o status mudar. Quando o status for "aguardando", mostre ao usuario a pendencia e o historico e so chame decidir_execucao com a decisao dele.')]
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
    ];
}
