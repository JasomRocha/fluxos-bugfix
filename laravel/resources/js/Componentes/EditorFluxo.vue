<script setup>
import { VueFlow } from '@vue-flow/core';
import { Background } from '@vue-flow/background';
import { Controls } from '@vue-flow/controls';
import { computed, ref, watch } from 'vue';
import { ROTAS, TIPOS } from './tipos.js';
import { definicaoParaGrafo, grafoParaDefinicao, renomear, validar } from './conversao.js';

const props = defineProps({ modelValue: { type: Object, required: true } });
const emit = defineEmits(['update:modelValue']);

const inicial = definicaoParaGrafo(props.modelValue);
const nodes = ref(inicial.nodes);
const edges = ref(inicial.edges);
const inicio = ref(inicial.inicio);
const selecionado = ref(null); // { tipo: 'no' | 'aresta', id }

const no = computed(() => (selecionado.value?.tipo === 'no' ? nodes.value.find((n) => n.id === selecionado.value.id) : null));
const aresta = computed(() => (selecionado.value?.tipo === 'aresta' ? edges.value.find((e) => e.id === selecionado.value.id) : null));
const avisos = computed(() => validar(nodes.value, edges.value, inicio.value));
const sugestoes = computed(() => {
  const origem = aresta.value && nodes.value.find((n) => n.id === aresta.value.source);
  return origem ? ROTAS[origem.data.tipo] ?? [] : [];
});

// devolve ao formulario sempre que algo muda
watch([nodes, edges, inicio], () => emit('update:modelValue', grafoParaDefinicao(nodes.value, edges.value, inicio.value)), { deep: true });

// destaca o no inicial
watch(inicio, (novo) => nodes.value.forEach((n) => (n.class = n.id === novo ? 'no-inicio' : '')), { immediate: true });

function adicionar(tipo) {
  let i = 1;
  while (nodes.value.some((n) => n.id === `${tipo}_${i}`)) i++;
  const id = `${tipo}_${i}`;
  const base = nodes.value.length;
  nodes.value.push({
    id,
    position: { x: 40 + (base % 4) * 40, y: 40 + base * 30 },
    data: { tipo, config: {} },
    label: `${TIPOS[tipo].rotulo}\n${id}`,
    class: '',
  });
  if (!inicio.value || !nodes.value.some((n) => n.id === inicio.value)) inicio.value = id;
  selecionado.value = { tipo: 'no', id };
}

function conectar({ source, target }) {
  if (source === target) return;
  edges.value.push({ id: `a${Date.now()}`, source, target, label: '', data: { quando: '' } });
}

function mudarQuando(valor) {
  aresta.value.data.quando = valor;
  aresta.value.label = valor;
}

function mudarNome(evento) {
  const novo = evento.target.value.trim().replace(/\s+/g, '_');
  const antigo = selecionado.value.id;
  if (!novo || novo === antigo || nodes.value.some((n) => n.id === novo)) {
    evento.target.value = antigo;
    return;
  }
  inicio.value = renomear(nodes.value, edges.value, inicio.value, antigo, novo);
  selecionado.value = { tipo: 'no', id: novo };
}

function removerNo() {
  const id = no.value.id;
  nodes.value = nodes.value.filter((n) => n.id !== id);
  edges.value = edges.value.filter((e) => e.source !== id && e.target !== id);
  if (inicio.value === id) inicio.value = nodes.value[0]?.id ?? '';
  selecionado.value = null;
}

function removerAresta() {
  edges.value = edges.value.filter((e) => e.id !== aresta.value.id);
  selecionado.value = null;
}

function mudarCampo(campo, valor) {
  const v = campo.numero ? (valor === '' ? '' : Number(valor)) : valor;
  no.value.data.config[campo.chave] = v;
}
</script>

<template>
  <div class="grid gap-3 lg:grid-cols-[1fr_300px]">
    <div>
      <div class="mb-2 flex flex-wrap gap-1.5">
        <button
          v-for="(t, chave) in TIPOS" :key="chave" type="button"
          class="rounded border bg-white px-2.5 py-1 text-xs hover:bg-slate-100" @click="adicionar(chave)"
        >+ {{ t.rotulo }}</button>
      </div>
      <div class="h-[520px] rounded border bg-white">
        <VueFlow
          v-model:nodes="nodes" v-model:edges="edges"
          :default-viewport="{ zoom: 0.9 }" :min-zoom="0.3" fit-view-on-init
          @connect="conectar"
          @node-click="({ node }) => (selecionado = { tipo: 'no', id: node.id })"
          @edge-click="({ edge }) => (selecionado = { tipo: 'aresta', id: edge.id })"
          @pane-click="selecionado = null"
        >
          <Background />
          <Controls />
        </VueFlow>
      </div>
      <ul v-if="avisos.length" class="mt-2 space-y-1 text-xs text-amber-700">
        <li v-for="a in avisos" :key="a">⚠ {{ a }}</li>
      </ul>
      <p class="mt-2 text-xs text-slate-500">Arraste do ponto de um nó até outro para ligar. Clique em um nó ou aresta para editar. Backspace apaga o item selecionado.</p>
    </div>

    <aside class="rounded border bg-white p-3 text-sm">
      <template v-if="no">
        <h3 class="mb-2 font-medium">{{ TIPOS[no.data.tipo]?.rotulo }}</h3>
        <label class="mb-1 block text-xs text-slate-500">Nome do nó</label>
        <input :value="no.id" class="mb-3 w-full rounded border px-2 py-1" @change="mudarNome" />
        <div v-for="c in TIPOS[no.data.tipo].campos" :key="c.chave" class="mb-3">
          <label class="mb-1 block text-xs text-slate-500">{{ c.rotulo }}</label>
          <textarea v-if="c.area" :value="no.data.config[c.chave] ?? ''" rows="4" :placeholder="c.dica"
            class="w-full rounded border px-2 py-1 font-mono text-xs" @input="mudarCampo(c, $event.target.value)"></textarea>
          <input v-else :value="no.data.config[c.chave] ?? ''" :type="c.numero ? 'number' : 'text'" :placeholder="c.dica"
            class="w-full rounded border px-2 py-1 font-mono text-xs" @input="mudarCampo(c, $event.target.value)" />
        </div>
        <p v-if="TIPOS[no.data.tipo].campos.length" class="mb-3 text-xs text-slate-500">Use {variavel} para valores dos dados da execução.</p>
        <div class="flex gap-2">
          <button type="button" class="rounded border px-2 py-1 text-xs" :disabled="inicio === no.id" @click="inicio = no.id">
            {{ inicio === no.id ? 'É o início' : 'Definir como início' }}
          </button>
          <button type="button" class="rounded border px-2 py-1 text-xs text-red-700" @click="removerNo">Remover</button>
        </div>
      </template>

      <template v-else-if="aresta">
        <h3 class="mb-2 font-medium">Ligação</h3>
        <p class="mb-2 text-xs text-slate-500">{{ aresta.source }} → {{ aresta.target }}</p>
        <label class="mb-1 block text-xs text-slate-500">Seguir quando a rota for</label>
        <input :value="aresta.data.quando" placeholder="vazio = caminho padrão" class="mb-2 w-full rounded border px-2 py-1"
          @input="mudarQuando($event.target.value.trim())" />
        <div class="mb-3 flex flex-wrap gap-1">
          <button v-for="s in sugestoes" :key="s" type="button" class="rounded bg-slate-100 px-2 py-0.5 text-xs" @click="mudarQuando(s)">{{ s }}</button>
        </div>
        <button type="button" class="rounded border px-2 py-1 text-xs text-red-700" @click="removerAresta">Remover ligação</button>
      </template>

      <p v-else class="text-slate-500">Selecione um nó ou uma ligação para editar.</p>
    </aside>
  </div>
</template>

<style>
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
@import '@vue-flow/controls/dist/style.css';

.vue-flow__node-default { white-space: pre-line; text-align: center; font-size: 12px; min-width: 130px; }
.vue-flow__node.no-inicio { border-color: #15803d; box-shadow: 0 0 0 2px #bbf7d0; }
.vue-flow__edge-text { font-size: 11px; }
</style>
