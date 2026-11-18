<script setup>
import { Link } from '@inertiajs/vue3';
defineProps({ fluxos: Array });
</script>

<template>
  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-xl font-semibold">Fluxos</h1>
    <Link href="/fluxos/create" class="rounded bg-slate-900 px-3 py-1.5 text-sm text-white">Novo fluxo</Link>
  </div>
  <p v-if="!fluxos.length" class="text-slate-500">Nenhum fluxo ainda.</p>
  <ul class="divide-y rounded border bg-white">
    <li v-for="f in fluxos" :key="f.id" class="flex items-center justify-between px-4 py-3">
      <Link :href="`/fluxos/${f.id}`" class="font-medium hover:underline">{{ f.nome }}</Link>
      <span class="text-sm text-slate-500">{{ f.execucoes_count }} execuções</span>
    </li>
  </ul>
</template>
