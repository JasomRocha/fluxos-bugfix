<?php

namespace Tests\Feature;

use App\Mcp\Servers\FluxosServer;
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
use App\Models\Execucao;
use App\Models\Fluxo;
use App\Models\Resolucao;
use App\Models\User;
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

    public function test_servidor_expoe_todas_as_ferramentas(): void
    {
        FluxosServer::tools()->assertRegistered([
            TiposDeNo::class, ListarFluxos::class, VerFluxo::class, CriarFluxo::class, AtualizarFluxo::class,
            ExecutarFluxo::class, ListarExecucoes::class, ConsultarExecucao::class, DecidirExecucao::class,
            ProximasEtapas::class, ConcluirEtapa::class, RegistrarResolucao::class, ListarResolucoes::class, VerResolucao::class,
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
            ->assertHasErrors(['nao esta aguardando']);   // ainda esta executando

        FluxosServer::tool(ConsultarExecucao::class, ['id' => $e->id])->assertSee('Abrir PR?');
        $this->assertSame('aguardando', $e->fresh()->status);

        FluxosServer::tool(DecidirExecucao::class, ['id' => $e->id, 'aprovado' => true])->assertOk();
        $this->assertSame('concluida', $e->fresh()->status);

        FluxosServer::tool(ListarExecucoes::class, ['status' => 'concluida'])->assertSee((string) $e->id);
        FluxosServer::tool(ListarExecucoes::class, ['status' => 'erro'])->assertDontSee('"id":'.$e->id);
    }

    public function test_tipos_de_no_lista_o_catalogo(): void
    {
        FluxosServer::tool(TiposDeNo::class)->assertSee(['aprovacao', 'sem_alteracao', 'git_preparar', 'etapa_guiada', 'modelo_correcao_guiada']);
        $this->assertContains('etapa_guiada', Fluxo::TIPOS);
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

    private function pendencia(array $pendencia, string $status = 'aguardando'): array
    {
        return ['id' => 'm9', 'status' => $status, 'pendencia' => $pendencia, 'erro' => null, 'dados' => ['bug' => 'B-1'], 'historico' => ['entrada recebida']];
    }

    public function test_modelo_guiado_e_uma_definicao_valida(): void
    {
        $this->assertNull(Fluxo::erroDaDefinicao(\App\Support\Modelos::correcaoGuiada()));
    }

    public function test_etapa_guiada_do_inicio_ao_fim(): void
    {
        $f = Fluxo::create(['nome' => 'Guiado', 'definicao' => \App\Support\Modelos::correcaoGuiada()]);
        $etapa = ['tipo' => 'etapa_guiada', 'titulo' => 'Investigar', 'instrucao' => 'Investigue B-1', 'campos' => ['causa']];
        Http::fake([
            '*/execucoes/m9/resposta' => Http::response($this->pendencia(['tipo' => 'aprovacao', 'pergunta' => 'Subir o PR?'])),
            // 1a consulta: a etapa ainda pendente; 2a consulta (depois de concluir): ja na aprovacao
            '*/execucoes/m9' => Http::sequence()
                ->push($this->pendencia($etapa))
                ->push($this->pendencia(['tipo' => 'aprovacao', 'pergunta' => 'Subir o PR?'])),
            '*/execucoes' => Http::response($this->pendencia($etapa, 'executando')),
        ]);

        FluxosServer::tool(ExecutarFluxo::class, ['fluxo_id' => $f->id, 'dados' => ['bug' => 'B-1']])->assertOk();
        $e = Execucao::firstOrFail();

        FluxosServer::tool(ProximasEtapas::class)->assertSee(['Investigue B-1', 'campos_esperados', 'etapas_para_executar']);

        // aprovar nao serve para etapa guiada
        FluxosServer::tool(DecidirExecucao::class, ['id' => $e->id, 'aprovado' => true])->assertHasErrors(['concluir_etapa']);

        FluxosServer::tool(ConcluirEtapa::class, ['execucao_id' => $e->id, 'resultado' => ['causa' => 'sinal']])->assertOk();
        $this->assertSame('aprovacao', $e->fresh()->pendencia['tipo']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/execucoes/m9/resposta') && $r['sucesso'] === true && (array) $r['resultado'] === ['causa' => 'sinal']);

        // concluir etapa quando a pendencia e uma aprovacao e recusado
        FluxosServer::tool(ConcluirEtapa::class, ['execucao_id' => $e->id, 'resultado' => []])->assertHasErrors(['aprovacao']);
        FluxosServer::tool(ProximasEtapas::class)->assertSee(['aprovacoes_do_usuario', 'Subir o PR?']);
    }

    public function test_registrar_atualizar_buscar_e_ver_resolucao(): void
    {
        $f = $this->fluxo();
        $e = $f->execucoes()->create(['status' => 'concluida']);
        $dados = ['execucao_id' => $e->id, 'origem' => 'B-1', 'titulo' => 'Soma errada', 'causa' => 'sinal trocado em calc.py',
            'alteracoes' => 'trocou - por +', 'arquivos' => ['calc.py'], 'documentacao' => '# Soma errada'];

        FluxosServer::tool(RegistrarResolucao::class, $dados)->assertOk();
        FluxosServer::tool(RegistrarResolucao::class, $dados + ['pr_url' => 'https://exemplo/pr/1'])->assertOk(); // atualiza
        $this->assertSame(1, Resolucao::count());
        $this->assertSame('https://exemplo/pr/1', Resolucao::first()->pr_url);

        FluxosServer::tool(ListarResolucoes::class, ['busca' => 'sinal trocado'])->assertSee('Soma errada');
        FluxosServer::tool(ListarResolucoes::class, ['busca' => 'inexistente'])->assertDontSee('Soma errada');
        FluxosServer::tool(VerResolucao::class, ['id' => Resolucao::first()->id])->assertSee('# Soma errada');
        FluxosServer::tool(RegistrarResolucao::class, ['execucao_id' => 999, 'titulo' => 'x', 'causa' => 'x', 'alteracoes' => 'x'])->assertHasErrors(['nao existe']);
    }

    public function test_telas_de_visao_e_historico(): void
    {
        $r = Resolucao::create(['titulo' => 'Soma errada', 'causa' => 'c', 'alteracoes' => 'a', 'documentacao' => 'doc']);
        $this->actingAs(User::factory()->create());
        $this->get('/')->assertOk()->assertInertia(fn ($p) => $p->component('Visao')->where('contagem.resolvidas', 1));
        $this->get('/resolucoes?q=Soma')->assertOk()->assertInertia(fn ($p) => $p->component('Resolucoes/Index')->has('resolucoes', 1));
        $this->get('/resolucoes?q=nada')->assertInertia(fn ($p) => $p->has('resolucoes', 0));
        $this->get("/resolucoes/{$r->id}")->assertOk()->assertInertia(fn ($p) => $p->component('Resolucoes/Show')->where('resolucao.documentacao', 'doc'));
    }

    public function test_pendencia_antiga_sem_tipo_vale_como_aprovacao(): void
    {
        $f = $this->fluxo();
        $e = $f->execucoes()->create(['status' => 'aguardando', 'motor_id' => 'm7', 'pendencia' => ['pergunta' => 'Abrir PR?']]);
        Http::fake(['*/execucoes/m7/decisao' => Http::response(['id' => 'm7', 'status' => 'concluida', 'pendencia' => null, 'erro' => null, 'dados' => [], 'historico' => []])]);
        FluxosServer::tool(DecidirExecucao::class, ['id' => $e->id, 'aprovado' => true])->assertOk();
        $this->assertSame('concluida', $e->fresh()->status);
    }
}
