<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ resolucao: Object });
const campos = [['Causa', 'causa'], ['O que mudou', 'alteracoes'], ['Testes', 'testes']];
</script>

<template>
  <div class="mb-1 text-sm text-slate-500"><Link href="/resolucoes" class="hover:underline">Resoluções</Link> / {{ resolucao.origem || 'sem origem' }}</div>
  <h1 class="mb-4 text-xl font-semibold">{{ resolucao.titulo }}</h1>

  <dl class="mb-6 space-y-4 rounded border bg-white p-4 text-sm">
    <div v-for="[rotulo, chave] in campos" :key="chave" v-show="resolucao[chave]">
      <dt class="text-xs font-medium uppercase text-slate-500">{{ rotulo }}</dt>
      <dd class="whitespace-pre-wrap">{{ resolucao[chave] }}</dd>
    </div>
    <div v-if="resolucao.arquivos?.length">
      <dt class="text-xs font-medium uppercase text-slate-500">Arquivos</dt>
      <dd><code v-for="a in resolucao.arquivos" :key="a" class="mr-2 rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ a }}</code></dd>
    </div>
    <div v-if="resolucao.branch || resolucao.pr_url" class="flex flex-wrap gap-6">
      <div v-if="resolucao.branch"><dt class="text-xs font-medium uppercase text-slate-500">Branch</dt><dd><code>{{ resolucao.branch }}</code></dd></div>
      <div v-if="resolucao.pr_url"><dt class="text-xs font-medium uppercase text-slate-500">PR</dt><dd class="break-all">{{ resolucao.pr_url }}</dd></div>
    </div>
  </dl>

  <section v-if="resolucao.documentacao">
    <h2 class="mb-2 font-medium">Documentação</h2>
    <pre class="overflow-x-auto whitespace-pre-wrap rounded border bg-white p-4 text-sm">{{ resolucao.documentacao }}</pre>
  </section>
  <Link v-if="resolucao.execucao_id" :href="`/execucoes/${resolucao.execucao_id}`" class="mt-4 inline-block text-sm text-slate-500 hover:underline">Ver a execução #{{ resolucao.execucao_id }}</Link>
</template>
