<script setup>
defineProps({ status: String });
const cores = {
  executando: 'bg-blue-100 text-blue-800',
  aguardando: 'bg-amber-100 text-amber-800',
  concluida: 'bg-green-100 text-green-800',
  erro: 'bg-red-100 text-red-800',
};
const rotulos = { executando: 'Executando', aguardando: 'Aguardando aprovação', concluida: 'Concluída', erro: 'Erro' };
</script>

<template>
  <span class="rounded px-2 py-0.5 text-xs font-medium" :class="cores[status]">{{ rotulos[status] ?? status }}</span>
</template>
