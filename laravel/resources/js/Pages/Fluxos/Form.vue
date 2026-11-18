<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import EditorFluxo from '../../Componentes/EditorFluxo.vue';

const props = defineProps({ fluxo: Object, modelo: Object });

const definicao = ref(props.fluxo?.definicao ?? props.modelo ?? { inicio: '', nos: [], arestas: [] });
const aba = ref('visual');
const texto = ref('');
const erroJson = ref('');
const chaveEditor = ref(0); // recria o editor ao voltar do JSON

const form = useForm({ nome: props.fluxo?.nome ?? '', definicao: '' });

function irPara(destino) {
  if (destino === aba.value) return;
  if (destino === 'json') {
    texto.value = JSON.stringify(definicao.value, null, 2);
  } else {
    try {
      definicao.value = JSON.parse(texto.value);
      chaveEditor.value++;
    } catch (e) {
      erroJson.value = 'JSON inválido: ' + e.message;
      return;
    }
  }
  erroJson.value = '';
  aba.value = destino;
}

function enviar() {
  const corpo = aba.value === 'json' ? texto.value : JSON.stringify(definicao.value);
  form.transform((d) => ({ ...d, definicao: corpo }));
  props.fluxo ? form.put(`/fluxos/${props.fluxo.id}`) : form.post('/fluxos');
}
</script>

<template>
  <h1 class="mb-4 text-xl font-semibold">{{ fluxo ? 'Editar fluxo' : 'Novo fluxo' }}</h1>
  <form class="space-y-3" @submit.prevent="enviar">
    <input v-model="form.nome" placeholder="Nome do fluxo" class="w-full rounded border bg-white px-3 py-2" />
    <p v-if="form.errors.nome" class="text-sm text-red-600">{{ form.errors.nome }}</p>

    <div class="flex gap-1 text-sm">
      <button type="button" class="rounded px-3 py-1" :class="aba === 'visual' ? 'bg-slate-900 text-white' : 'border bg-white'" @click="irPara('visual')">Visual</button>
      <button type="button" class="rounded px-3 py-1" :class="aba === 'json' ? 'bg-slate-900 text-white' : 'border bg-white'" @click="irPara('json')">JSON</button>
    </div>

    <EditorFluxo v-if="aba === 'visual'" :key="chaveEditor" v-model="definicao" />
    <textarea v-else v-model="texto" rows="24" spellcheck="false" class="w-full rounded border bg-white p-3 font-mono text-xs"></textarea>

    <p v-if="erroJson" class="text-sm text-red-600">{{ erroJson }}</p>
    <p v-if="form.errors.definicao" class="text-sm text-red-600">{{ form.errors.definicao }}</p>
    <button class="rounded bg-slate-900 px-4 py-2 text-white" :disabled="form.processing">Salvar</button>
  </form>
</template>
