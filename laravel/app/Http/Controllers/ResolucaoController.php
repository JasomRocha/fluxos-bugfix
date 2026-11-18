<?php

namespace App\Http\Controllers;

use App\Models\Resolucao;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResolucaoController extends Controller
{
    public function index(Request $r)
    {
        $busca = trim((string) $r->query('q', ''));

        return Inertia::render('Resolucoes/Index', [
            'q' => $busca,
            'resolucoes' => Resolucao::query()
                ->when($busca !== '', fn ($q) => $q->where(fn ($w) => $w->where('titulo', 'like', "%{$busca}%")->orWhere('causa', 'like', "%{$busca}%")->orWhere('alteracoes', 'like', "%{$busca}%")->orWhere('origem', 'like', "%{$busca}%")))
                ->latest('id')->limit(100)->get(['id', 'origem', 'titulo', 'causa', 'pr_url', 'created_at']),
        ]);
    }

    public function show(Resolucao $resolucao)
    {
        return Inertia::render('Resolucoes/Show', ['resolucao' => $resolucao]);
    }
}
