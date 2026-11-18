<script setup>
import { useForm } from '@inertiajs/vue3';
const props = defineProps({ fluxo: Object, modelo: Object });
const form = useForm({
  nome: props.fluxo?.nome ?? '',
  definicao: JSON.stringify(props.fluxo?.definicao ?? props.modelo ?? {}, null, 2),
});
const enviar = () => (props.fluxo ? form.put(`/fluxos/${props.fluxo.id}`) : form.post('/fluxos'));
</script>

<template>
  <h1 class="mb-4 text-xl font-semibold">{{ fluxo ? 'Editar fluxo' : 'Novo fluxo' }}</h1>
  <form class="space-y-3" @submit.prevent="enviar">
    <input v-model="form.nome" placeholder="Nome do fluxo" class="w-full rounded border bg-white px-3 py-2" />
    <p v-if="form.errors.nome" class="text-sm text-red-600">{{ form.errors.nome }}</p>
    <textarea v-model="form.definicao" rows="24" spellcheck="false"
      class="w-full rounded border bg-white p-3 font-mono text-xs"></textarea>
    <p v-if="form.errors.definicao" class="text-sm text-red-600">{{ form.errors.definicao }}</p>
    <p class="text-xs text-slate-500">Editor JSON provisório. O editor visual entra na próxima etapa.</p>
    <button class="rounded bg-slate-900 px-4 py-2 text-white" :disabled="form.processing">Salvar</button>
  </form>
</template>
