<?php

namespace App\Support;

/** Fluxos prontos. O Claude Code conduz as etapas guiadas e o usuario acompanha. */
class Modelos
{
    public static function correcaoGuiada(): array
    {
        $etapa = fn (string $id, string $chave, string $titulo, string $instrucao, array $campos) => [
            'id' => $id, 'tipo' => 'etapa_guiada',
            'config' => ['chave' => $chave, 'titulo' => $titulo, 'instrucao' => $instrucao, 'campos' => $campos],
        ];

        return [
            'inicio' => 'entrada',
            'nos' => [
                ['id' => 'entrada', 'tipo' => 'entrada'],
                $etapa('investigar', 'investigar', 'Investigar o bug',
                    'Bug {bug}: {descricao}. No repositorio {repositorio}, reproduza o problema e encontre a causa. Nao altere nenhum arquivo ainda.',
                    ['causa', 'arquivos']),
                $etapa('corrigir', 'corrigir', 'Corrigir e testar',
                    'Crie a branch correcao/{bug}, corrija a causa ({investigar_resultado[causa]}) com a menor mudanca possivel e rode os testes.',
                    ['branch', 'alteracoes', 'testes', 'arquivos']),
                ['id' => 'revisar', 'tipo' => 'aprovacao', 'config' => [
                    'pergunta' => 'Correcao na branch {corrigir_resultado[branch]}: {corrigir_resultado[alteracoes]} Testes: {corrigir_resultado[testes]} Subir o PR?',
                ]],
                $etapa('abrir_pr', 'pr', 'Subir o PR',
                    'Envie a branch {corrigir_resultado[branch]} e abra o PR com titulo e descricao claros (causa, mudanca, testes).',
                    ['pr_url']),
                $etapa('documentar', 'docs', 'Documentar e registrar',
                    'Escreva a documentacao da correcao em markdown (contexto, causa, solucao, como validar, riscos). Depois chame registrar_resolucao com tudo, inclusive a documentacao.',
                    ['documentacao']),
                ['id' => 'fim', 'tipo' => 'fim'],
                ['id' => 'sem_correcao', 'tipo' => 'fim'],
            ],
            'arestas' => [
                ['de' => 'entrada', 'para' => 'investigar'],
                ['de' => 'investigar', 'para' => 'corrigir', 'quando' => 'ok'],
                ['de' => 'investigar', 'para' => 'sem_correcao', 'quando' => 'falha'],
                ['de' => 'corrigir', 'para' => 'revisar', 'quando' => 'ok'],
                ['de' => 'corrigir', 'para' => 'sem_correcao', 'quando' => 'falha'],
                ['de' => 'revisar', 'para' => 'abrir_pr', 'quando' => 'aprovado'],
                ['de' => 'revisar', 'para' => 'sem_correcao', 'quando' => 'recusado'],
                ['de' => 'abrir_pr', 'para' => 'documentar', 'quando' => 'ok'],
                ['de' => 'abrir_pr', 'para' => 'sem_correcao', 'quando' => 'falha'],
                ['de' => 'documentar', 'para' => 'fim'],
            ],
        ];
    }
}
