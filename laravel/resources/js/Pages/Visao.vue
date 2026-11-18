<script setup>
import { Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted } from 'vue';
import Situacao from '../Componentes/Situacao.vue';

defineProps({ contagem: Object, abertas: Array, resolucoes: Array });

let relogio;
onMounted(() => (relogio = setInterval(() => router.reload({ only: ['contagem', 'abertas', 'resolucoes'] }), 5000)));
onBeforeUnmount(() => clearInterval(relogio));

const rotuloPendencia = (p) =>
  p?.tipo === 'etapa_guiada' ? `Etapa para o Claude Code: ${p.titulo}` : p?.tipo === 'aprovacao' ? `Aguarda sua decisão: ${p.pergunta}` : '';
const data = (v) => new Date(v).toLocaleString('pt-BR');
</script>

<template>
  <h1 class="mb-4 text-xl font-semibold">Visão geral</h1>

  <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
    <div class="rounded border bg-white p-4"><div class="text-2xl font-semibold">{{ contagem.em_andamento }}</div><div class="text-xs text-slate-500">Em andamento</div></div>
    <div class="rounded border bg-white p-4"><div class="text-2xl font-semibold text-amber-700">{{ contagem.aguardando }}</div><div class="text-xs text-slate-500">Aguardando</div></div>
    <div class="rounded border bg-white p-4"><div class="text-2xl font-semibold text-green-700">{{ contagem.resolvidas }}</div><div class="text-xs text-slate-500">Bugs resolvidos</div></div>
    <div class="rounded border bg-white p-4"><div class="text-2xl font-semibold text-red-700">{{ contagem.com_erro }}</div><div class="text-xs text-slate-500">Com erro</div></div>
  </div>

  <section class="mb-6">
    <h2 class="mb-2 font-medium">Em aberto</h2>
    <ul class="divide-y rounded border bg-white">
      <li v-for="e in abertas" :key="e.id" class="flex items-center justify-between gap-3 px-4 py-3">
        <div class="min-w-0">
          <Link :href="`/execucoes/${e.id}`" class="font-medium hover:underline">#{{ e.id }} {{ e.dados?.bug ?? e.fluxo }}</Link>
          <p class="truncate text-sm text-slate-500">{{ rotuloPendencia(e.pendencia) || e.dados?.descricao }}</p>
        </div>
        <Situacao :status="e.status" />
      </li>
      <li v-if="!abertas.length" class="px-4 py-3 text-slate-500">Nada em aberto.</li>
    </ul>
  </section>

  <section>
    <div class="mb-2 flex items-center justify-between"><h2 class="font-medium">Últimas resoluções</h2><Link href="/resolucoes" class="text-sm text-slate-500 hover:underline">ver todas</Link></div>
    <ul class="divide-y rounded border bg-white">
      <li v-for="r in resolucoes" :key="r.id" class="px-4 py-3">
        <div class="flex items-center justify-between gap-3">
          <Link :href="`/resolucoes/${r.id}`" class="font-medium hover:underline">{{ r.titulo }}</Link>
          <span class="shrink-0 text-xs text-slate-500">{{ data(r.created_at) }}</span>
        </div>
        <p class="text-sm text-slate-600">{{ r.causa }}</p>
      </li>
      <li v-if="!resolucoes.length" class="px-4 py-3 text-slate-500">Nenhuma resolução registrada ainda.</li>
    </ul>
  </section>
</template>
