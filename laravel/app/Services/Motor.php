<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Cliente HTTP do motor de fluxos (servico Python). */
class Motor
{
    private function http(): PendingRequest
    {
        return Http::baseUrl(config('services.motor.url'))
            ->withHeaders(['X-Token' => (string) config('services.motor.token')])
            ->acceptJson()
            ->timeout(15)
            ->throw();
    }

    public function iniciar(array $fluxo, array $dados): array
    {
        return $this->http()->post('/execucoes', ['fluxo' => $fluxo, 'dados' => (object) $dados])->json();
    }

    public function consultar(string $id): array
    {
        return $this->http()->get("/execucoes/{$id}")->json();
    }

    public function decidir(string $id, bool $aprovado): array
    {
        return $this->http()->post("/execucoes/{$id}/decisao", ['aprovado' => $aprovado])->json();
    }
}
