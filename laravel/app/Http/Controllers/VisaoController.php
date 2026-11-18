<?php

namespace App\Http\Controllers;

use App\Models\Execucao;
use App\Models\Resolucao;
use App\Services\Execucoes;
use Inertia\Inertia;

class VisaoController extends Controller
{
    public function index(Execucoes $execucoes)
    {
        $abertas = Execucao::with('fluxo:id,nome')->whereIn('status', ['executando', 'aguardando'])->latest('id')->limit(30)->get();
        $abertas->each(fn ($e) => $execucoes->sincronizar($e));

        return Inertia::render('Visao', [
            'contagem' => [
                'em_andamento' => $abertas->where('status', 'executando')->count(),
                'aguardando' => $abertas->where('status', 'aguardando')->count(),
                'resolvidas' => Resolucao::count(),
                'com_erro' => Execucao::where('status', 'erro')->count(),
            ],
            'abertas' => $abertas->map(fn ($e) => [
                'id' => $e->id, 'fluxo' => $e->fluxo?->nome, 'status' => $e->status,
                'pendencia' => $e->pendencia, 'dados' => $e->dados,
            ])->values(),
            'resolucoes' => Resolucao::latest('id')->limit(10)->get(['id', 'origem', 'titulo', 'causa', 'pr_url', 'created_at']),
        ]);
    }
}
