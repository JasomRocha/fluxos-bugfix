<?php

namespace App\Http\Controllers;

use App\Models\Execucao;
use App\Models\Fluxo;
use App\Services\Execucoes;
use DomainException;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExecucaoController extends Controller
{
    public function __construct(private Execucoes $execucoes) {}

    public function store(Request $r, Fluxo $fluxo)
    {
        $dados = $r->validate(['dados' => 'nullable|string'])['dados'] ?? '{}';
        $dados = json_decode($dados, true);
        abort_unless(is_array($dados), 422, 'Dados devem ser um JSON de objeto.');

        $execucao = $this->execucoes->iniciar($fluxo, $dados);

        return redirect()->route('execucoes.show', $execucao);
    }

    public function show(Execucao $execucao)
    {
        $this->execucoes->sincronizar($execucao);

        return Inertia::render('Execucoes/Show', ['execucao' => $execucao->load('fluxo:id,nome')]);
    }

    public function decisao(Request $r, Execucao $execucao)
    {
        $aprovado = $r->validate(['aprovado' => 'required|boolean'])['aprovado'];
        try {
            $this->execucoes->decidir($execucao, $aprovado);
        } catch (DomainException $e) {
            abort(409, $e->getMessage());
        }

        return redirect()->route('execucoes.show', $execucao);
    }
}
