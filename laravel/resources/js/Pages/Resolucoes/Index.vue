<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ q: String, resolucoes: Array });
const busca = ref(props.q);
const buscar = () => router.get('/resolucoes', { q: busca.value }, { preserveState: true, replace: true });
const data = (v) => new Date(v).toLocaleDateString('pt-BR');
</script>

<template>
  <div class="mb-4 flex items-center justify-between gap-3">
    <h1 class="text-xl font-semibold">Bugs resolvidos</h1>
    <form @submit.prevent="buscar"><input v-model="busca" placeholder="Buscar por título, causa ou origem" class="w-72 rounded border bg-white px-3 py-1.5 text-sm" /></form>
  </div>
  <ul class="divide-y rounded border bg-white">
    <li v-for="r in resolucoes" :key="r.id" class="px-4 py-3">
      <div class="flex items-center justify-between gap-3">
        <Link :href="`/resolucoes/${r.id}`" class="font-medium hover:underline">{{ r.titulo }}</Link>
        <span class="shrink-0 text-xs text-slate-500">{{ r.origem }} · {{ data(r.created_at) }}</span>
      </div>
      <p class="text-sm text-slate-600">{{ r.causa }}</p>
    </li>
    <li v-if="!resolucoes.length" class="px-4 py-3 text-slate-500">Nada encontrado.</li>
  </ul>
</template>
