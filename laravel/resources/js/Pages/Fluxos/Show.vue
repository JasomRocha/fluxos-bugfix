<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import Situacao from '../../Componentes/Situacao.vue';
const props = defineProps({ fluxo: Object, execucoes: Array });
const form = useForm({ dados: '{\n  "repositorio": "",\n  "descricao": "",\n  "comando_testes": "",\n  "bug": ""\n}' });
const apagar = () => confirm('Apagar este fluxo e suas execuções?') && router.delete(`/fluxos/${props.fluxo.id}`);
</script>

<template>
  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">{{ fluxo.nome }}</h1>
    <div class="flex gap-2 text-sm">
      <Link :href="`/fluxos/${fluxo.id}/edit`" class="rounded border px-3 py-1.5">Editar</Link>
      <button class="rounded border px-3 py-1.5 text-red-700" @click="apagar">Apagar</button>
    </div>
  </div>

  <section class="mb-6 rounded border bg-white p-4">
    <h2 class="mb-2 font-medium">Executar</h2>
    <form @submit.prevent="form.post(`/fluxos/${fluxo.id}/execucoes`)">
      <textarea v-model="form.dados" rows="7" spellcheck="false" class="w-full rounded border p-2 font-mono text-xs"></textarea>
      <p v-if="form.errors.dados" class="text-sm text-red-600">{{ form.errors.dados }}</p>
      <button class="mt-2 rounded bg-slate-900 px-4 py-1.5 text-white" :disabled="form.processing">Executar</button>
    </form>
  </section>

  <h2 class="mb-2 font-medium">Últimas execuções</h2>
  <ul class="divide-y rounded border bg-white">
    <li v-for="e in execucoes" :key="e.id" class="flex items-center justify-between px-4 py-2">
      <Link :href="`/execucoes/${e.id}`" class="hover:underline">#{{ e.id }}</Link>
      <Situacao :status="e.status" />
    </li>
    <li v-if="!execucoes.length" class="px-4 py-3 text-slate-500">Nenhuma execução.</li>
  </ul>
</template>
