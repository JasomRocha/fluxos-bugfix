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
        $this->exigirPendencia($execucao, 'aprovacao', 'responder com concluir_etapa');
        $this->aplicar($execucao, $this->motor->decidir($execucao->motor_id, $aprovado));

        return $execucao;
    }

    public function responder(Execucao $execucao, bool $sucesso, array $resultado): Execucao
    {
        $this->exigirPendencia($execucao, 'etapa_guiada', 'decidir com decidir_execucao');
        $this->aplicar($execucao, $this->motor->responder($execucao->motor_id, $sucesso, $resultado));

        return $execucao;
    }

    private function exigirPendencia(Execucao $e, string $tipo, string $alternativa): void
    {
        if ($e->status !== 'aguardando') {
            throw new DomainException('A execucao nao esta aguardando (status: '.$e->status.').');
        }
        $atual = $e->pendencia['tipo'] ?? 'aprovacao'; // sem tipo = pendencia antiga, sempre foi aprovacao
        if ($atual !== $tipo) {
            throw new DomainException("A pendencia desta execucao e do tipo '{$atual}'; para {$tipo} nao serve. Use: {$alternativa}.");
        }
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
