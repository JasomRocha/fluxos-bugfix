<?php

namespace App\Mcp\Tools;

use App\Models\Fluxo;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('listar_fluxos')]
#[Description('Lista os fluxos cadastrados (id, nome, quantidade de nos e de execucoes).')]
#[IsReadOnly]
class ListarFluxos extends Tool
{
    public function handle(Request $request): Response
    {
        return Response::json(
            Fluxo::withCount('execucoes')->orderBy('id')->get()->map(fn (Fluxo $f) => [
                'id' => $f->id,
                'nome' => $f->nome,
                'nos' => count($f->definicao['nos'] ?? []),
                'execucoes' => $f->execucoes_count,
            ])->all()
        );
    }
}
