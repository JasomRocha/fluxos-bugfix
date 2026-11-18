<?php

namespace Tests\Feature;

use App\Mcp\Servers\FluxosServer;
use App\Mcp\Tools\AtualizarFluxo;
use App\Mcp\Tools\ConsultarExecucao;
use App\Mcp\Tools\CriarFluxo;
use App\Mcp\Tools\DecidirExecucao;
use App\Mcp\Tools\ExecutarFluxo;
use App\Mcp\Tools\ListarExecucoes;
use App\Mcp\Tools\ListarFluxos;
use App\Mcp\Tools\TiposDeNo;
use App\Mcp\Tools\VerFluxo;
use App\Models\Execucao;
use App\Models\Fluxo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    private const DEF = [
        'inicio' => 'e',
        'nos' => [['id' => 'e', 'tipo' => 'entrada'], ['id' => 'f', 'tipo' => 'fim']],
        'arestas' => [['de' => 'e', 'para' => 'f']],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.mcp.token' => 'token-de-teste', 'services.motor.url' => 'http://motor:8000']);
    }

    private function fluxo(): Fluxo
    {
        return Fluxo::create(['nome' => 'Exemplo', 'definicao' => self::DEF]);
    }

    public function test_servidor_expoe_as_nove_ferramentas(): void
    {
        FluxosServer::tools()->assertRegistered([
            TiposDeNo::class, ListarFluxos::class, VerFluxo::class, CriarFluxo::class, AtualizarFluxo::class,
            ExecutarFluxo::class, ListarExecucoes::class, ConsultarExecucao::class, DecidirExecucao::class,
        ]);
    }

    public function test_criar_fluxo_valido_e_invalido(): void
    {
        FluxosServer::tool(CriarFluxo::class, ['nome' => 'Novo', 'definicao' => self::DEF])->assertOk()->assertSee('Novo');
        $this->assertSame(1, Fluxo::count());

        $ruim = self::DEF;
        $ruim['nos'][0]['tipo'] = 'inventado';
        FluxosServer::tool(CriarFluxo::class, ['nome' => 'Ruim', 'definicao' => $ruim])->assertHasErrors(['Tipo de no desconhecido']);

        $semDestino = self::DEF;
        $semDestino['arestas'][] = ['de' => 'e', 'para' => 'nao_existe'];
        FluxosServer::tool(CriarFluxo::class, ['nome' => 'Ruim2', 'definicao' => $semDestino])->assertHasErrors(['inexistente']);
        $this->assertSame(1, Fluxo::count());
    }

    public function test_listar_ver_e_atualizar_fluxo(): void
    {
        $f = $this->fluxo();
        FluxosServer::tool(ListarFluxos::class)->assertSee('Exemplo');
        FluxosServer::tool(VerFluxo::class, ['id' => $f->id])->assertSee('entrada');
        FluxosServer::tool(VerFluxo::class, ['id' => 999])->assertHasErrors(['nao existe']);

        FluxosServer::tool(AtualizarFluxo::class, ['id' => $f->id, 'nome' => 'Outro nome'])->assertOk();
        $this->assertSame('Outro nome', $f->fresh()->nome);
        $this->assertSame(self::DEF, $f->fresh()->definicao);  // definicao intacta
    }

    public function test_executar_consultar_e_decidir(): void
    {
        $f = $this->fluxo();
        Http::fake([
            '*/execucoes/m1/decisao' => Http::response(['id' => 'm1', 'status' => 'concluida', 'pendencia' => null, 'erro' => null, 'dados' => ['x' => 1], 'historico' => ['aprovado']]),
            '*/execucoes/m1' => Http::response(['id' => 'm1', 'status' => 'aguardando', 'pendencia' => ['pergunta' => 'Abrir PR?'], 'erro' => null, 'dados' => [], 'historico' => ['entrada recebida']]),
            '*/execucoes' => Http::response(['id' => 'm1', 'status' => 'executando', 'pendencia' => null, 'erro' => null, 'dados' => [], 'historico' => []]),
        ]);

        FluxosServer::tool(ExecutarFluxo::class, ['fluxo_id' => $f->id, 'dados' => ['bug' => '7']])->assertOk();
        $e = Execucao::firstOrFail();
        $this->assertSame('m1', $e->motor_id);
        $this->assertSame(['bug' => '7'], $e->dados);

        FluxosServer::tool(DecidirExecucao::class, ['id' => $e->id, 'aprovado' => true])
            ->assertHasErrors(['nao aguarda']);   // ainda esta executando

        FluxosServer::tool(ConsultarExecucao::class, ['id' => $e->id])->assertSee('Abrir PR?');
        $this->assertSame('aguardando', $e->fresh()->status);

        FluxosServer::tool(DecidirExecucao::class, ['id' => $e->id, 'aprovado' => true])->assertOk();
        $this->assertSame('concluida', $e->fresh()->status);

        FluxosServer::tool(ListarExecucoes::class, ['status' => 'concluida'])->assertSee((string) $e->id);
        FluxosServer::tool(ListarExecucoes::class, ['status' => 'erro'])->assertDontSee('"id":'.$e->id);
    }

    public function test_tipos_de_no_lista_o_catalogo(): void
    {
        FluxosServer::tool(TiposDeNo::class)->assertSee(['aprovacao', 'sem_alteracao', 'git_preparar']);
        $this->assertEqualsCanonicalizing(Fluxo::TIPOS, ['entrada', 'git_preparar', 'agente', 'comando', 'aprovacao', 'abrir_pr', 'fim']);
    }

    public function test_http_exige_token(): void
    {
        $corpo = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => []];
        $this->postJson('/mcp', $corpo)->assertStatus(401);
        $this->postJson('/mcp', $corpo, ['Authorization' => 'Bearer errado'])->assertStatus(401);
        $this->postJson('/mcp', $corpo, ['Authorization' => 'Bearer token-de-teste'])
            ->assertOk()->assertSee('executar_fluxo');
    }

    public function test_sem_token_configurado_nada_passa(): void
    {
        config(['services.mcp.token' => '']);
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], ['Authorization' => 'Bearer '])
            ->assertStatus(401);
    }
}
