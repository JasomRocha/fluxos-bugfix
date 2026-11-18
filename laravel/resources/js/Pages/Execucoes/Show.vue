<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted } from 'vue';
import Situacao from '../../Componentes/Situacao.vue';

const props = defineProps({ execucao: Object });
const emAndamento = computed(() => ['executando', 'aguardando'].includes(props.execucao.status));

let relogio;
onMounted(() => {
  relogio = setInterval(() => emAndamento.value && router.reload({ only: ['execucao'] }), 2500);
});
onBeforeUnmount(() => clearInterval(relogio));

const decidir = (aprovado) => router.post(`/execucoes/${props.execucao.id}/decisao`, { aprovado });
</script>

<template>
  <div class="mb-4 flex items-center gap-3">
    <h1 class="text-xl font-semibold">Execução #{{ execucao.id }}</h1>
    <Situacao :status="execucao.status" />
    <Link :href="`/fluxos/${execucao.fluxo_id}`" class="text-sm text-slate-500 hover:underline">{{ execucao.fluxo?.nome }}</Link>
  </div>

  <section v-if="execucao.status === 'aguardando'" class="mb-6 rounded border border-amber-300 bg-amber-50 p-4">
    <p class="mb-3 font-medium">{{ execucao.pendencia?.pergunta }}</p>
    <div class="flex gap-2">
      <button class="rounded bg-green-700 px-4 py-1.5 text-white" @click="decidir(true)">Aprovar</button>
      <button class="rounded bg-red-700 px-4 py-1.5 text-white" @click="decidir(false)">Recusar</button>
    </div>
  </section>

  <p v-if="execucao.erro" class="mb-6 rounded border border-red-300 bg-red-50 p-3 text-sm text-red-800">{{ execucao.erro }}</p>

  <section class="mb-6">
    <h2 class="mb-2 font-medium">Histórico</h2>
    <ol class="list-decimal space-y-1 rounded border bg-white py-3 pl-8 pr-4 text-sm">
      <li v-for="(h, i) in execucao.historico" :key="i">{{ h }}</li>
      <li v-if="!execucao.historico?.length" class="list-none text-slate-500">Sem eventos ainda.</li>
    </ol>
  </section>

  <section>
    <h2 class="mb-2 font-medium">Dados</h2>
    <pre class="overflow-x-auto rounded border bg-white p-3 text-xs">{{ JSON.stringify(execucao.dados, null, 2) }}</pre>
  </section>
</template>
