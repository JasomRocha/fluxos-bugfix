<?php

namespace App\Http\Controllers;

use App\Models\Fluxo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FluxoController extends Controller
{
    public function index()
    {
        return Inertia::render('Fluxos/Index', [
            'fluxos' => Fluxo::withCount('execucoes')->latest()->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Fluxos/Form', ['fluxo' => null, 'modelo' => $this->modelo()]);
    }

    public function store(Request $r)
    {
        $fluxo = Fluxo::create($this->validar($r));

        return redirect()->route('fluxos.show', $fluxo);
    }

    public function show(Fluxo $fluxo)
    {
        return Inertia::render('Fluxos/Show', [
            'fluxo' => $fluxo,
            'execucoes' => $fluxo->execucoes()->latest()->limit(20)->get(),
        ]);
    }

    public function edit(Fluxo $fluxo)
    {
        return Inertia::render('Fluxos/Form', ['fluxo' => $fluxo, 'modelo' => null]);
    }

    public function update(Request $r, Fluxo $fluxo)
    {
        $fluxo->update($this->validar($r));

        return redirect()->route('fluxos.show', $fluxo);
    }

    public function destroy(Fluxo $fluxo)
    {
        $fluxo->delete();

        return redirect()->route('fluxos.index');
    }

    private function validar(Request $r): array
    {
        $dados = $r->validate(['nome' => 'required|string|max:120', 'definicao' => 'required|string']);
        $def = json_decode($dados['definicao'], true);
        if (! is_array($def) || empty($def['nos']) || empty($def['inicio'])) {
            abort(422, 'Definicao invalida: informe "inicio" e "nos".');
        }

        return ['nome' => $dados['nome'], 'definicao' => $def];
    }

    private function modelo(): array
    {
        return [
            'inicio' => 'entrada',
            'nos' => [
                ['id' => 'entrada', 'tipo' => 'entrada'],
                ['id' => 'preparar', 'tipo' => 'git_preparar', 'config' => ['repositorio' => '{repositorio}']],
                ['id' => 'corrigir', 'tipo' => 'agente', 'config' => ['prompt' => 'Corrija o bug: {descricao}', 'diretorio' => '{diretorio}']],
                ['id' => 'testar', 'tipo' => 'comando', 'config' => ['comando' => '{comando_testes}', 'diretorio' => '{diretorio}', 'chave' => 'testes']],
                ['id' => 'aprovar', 'tipo' => 'aprovacao', 'config' => ['pergunta' => 'Abrir o PR da correcao?']],
                ['id' => 'pr', 'tipo' => 'abrir_pr'],
                ['id' => 'fim', 'tipo' => 'fim'],
            ],
            'arestas' => [
                ['de' => 'entrada', 'para' => 'preparar'],
                ['de' => 'preparar', 'para' => 'corrigir'],
                ['de' => 'corrigir', 'para' => 'testar'],
                ['de' => 'testar', 'para' => 'aprovar', 'quando' => 'ok'],
                ['de' => 'testar', 'para' => 'fim', 'quando' => 'falha'],
                ['de' => 'aprovar', 'para' => 'pr', 'quando' => 'aprovado'],
                ['de' => 'aprovar', 'para' => 'fim', 'quando' => 'recusado'],
                ['de' => 'pr', 'para' => 'fim'],
            ],
        ];
    }
}
