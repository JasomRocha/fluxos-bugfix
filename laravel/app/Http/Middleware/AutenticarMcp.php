<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Exige o token do servidor MCP no cabecalho Authorization: Bearer. */
class AutenticarMcp
{
    public function handle(Request $request, Closure $next): Response
    {
        $esperado = (string) config('services.mcp.token');
        $enviado = (string) $request->bearerToken();

        if ($esperado === '' || ! hash_equals($esperado, $enviado)) {
            return response()->json(['message' => 'Nao autorizado.'], 401, ['WWW-Authenticate' => 'Bearer realm="mcp"']);
        }

        return $next($request);
    }
}
