<?php

namespace App\Http\Controllers;

use App\Models\Execucao;
use App\Models\Fluxo;
use App\Services\Motor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExecucaoController extends Controller
{
    public function __construct(private Motor $motor) {}

    public function store(Request $r, Fluxo $fluxo)
    {
        $dados = $r->validate(['dados' => 'nullable|string'])['dados'] ?? '{}';
        $dados = json_decode($dados, true);
        abort_unless(is_array($dados), 422, 'Dados devem ser um JSON de objeto.');

        $execucao = $fluxo->execucoes()->create(['status' => 'executando', 'dados' => $dados]);
        $this->aplicar($execucao, $this->motor->iniciar($fluxo->definicao, $dados));

        return redirect()->route('execucoes.show', $execucao);
    }

    public function show(Execucao $execucao)
    {
        if ($execucao->motor_id && $execucao->emAndamento()) {
            $this->aplicar($execucao, $this->motor->consultar($execucao->motor_id));
        }

        return Inertia::render('Execucoes/Show', ['execucao' => $execucao->load('fluxo:id,nome')]);
    }

    public function decisao(Request $r, Execucao $execucao)
    {
        $aprovado = $r->validate(['aprovado' => 'required|boolean'])['aprovado'];
        abort_unless($execucao->status === 'aguardando', 409, 'A execucao nao aguarda decisao.');
        $this->aplicar($execucao, $this->motor->decidir($execucao->motor_id, $aprovado));

        return redirect()->route('execucoes.show', $execucao);
    }

    private function aplicar(Execucao $e, array $m): void
    {
        $e->update([
            'motor_id' => $m['id'],
            'status' => $m['status'],
            'pendencia' => $m['pendencia'] ?? null,
            'erro' => $m['erro'] ?? null,
            'dados' => $m['dados'] ?: $e->dados,
            'historico' => $m['historico'] ?? [],
        ]);
    }
}
