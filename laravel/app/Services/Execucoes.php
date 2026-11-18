<?php

namespace App\Services;

use App\Models\Execucao;
use App\Models\Fluxo;
use DomainException;

/** Regras de execucao compartilhadas pela interface web e pelo servidor MCP. */
class Execucoes
{
    public function __construct(private Motor $motor) {}

    public function iniciar(Fluxo $fluxo, array $dados): Execucao
    {
        $execucao = $fluxo->execucoes()->create(['status' => 'executando', 'dados' => $dados]);
        $this->aplicar($execucao, $this->motor->iniciar($fluxo->definicao, $dados));

        return $execucao;
    }

    /** Atualiza a execucao com o estado atual do motor, se ainda estiver em andamento. */
    public function sincronizar(Execucao $execucao): Execucao
    {
        if ($execucao->motor_id && $execucao->emAndamento()) {
            $this->aplicar($execucao, $this->motor->consultar($execucao->motor_id));
        }

        return $execucao;
    }

    public function decidir(Execucao $execucao, bool $aprovado): Execucao
    {
        if ($execucao->status !== 'aguardando') {
            throw new DomainException('A execucao nao aguarda decisao.');
        }
        $this->aplicar($execucao, $this->motor->decidir($execucao->motor_id, $aprovado));

        return $execucao;
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
