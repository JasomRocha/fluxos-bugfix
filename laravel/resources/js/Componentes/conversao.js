// Converte entre o JSON do fluxo (motor) e o grafo do editor visual.
import { TIPOS } from './tipos.js';

export const rotulo = (id, tipo) => `${TIPOS[tipo]?.rotulo ?? tipo}\n${id}`;

export function definicaoParaGrafo(def) {
  const nos = def.nos ?? [];
  const arestas = def.arestas ?? [];

  // posicao automatica por profundidade a partir do inicio
  const prof = { [def.inicio]: 0 };
  const fila = [def.inicio];
  while (fila.length) {
    const atual = fila.shift();
    for (const a of arestas) {
      if (a.de === atual && !(a.para in prof)) {
        prof[a.para] = prof[atual] + 1;
        fila.push(a.para);
      }
    }
  }
  const usados = {};

  const nodes = nos.map((n) => {
    let pos = n.posicao;
    if (!pos) {
      const d = prof[n.id] ?? 0;
      usados[d] = (usados[d] ?? -1) + 1;
      pos = { x: d * 260, y: usados[d] * 130 };
    }
    return {
      id: n.id,
      position: { x: pos.x, y: pos.y },
      data: { tipo: n.tipo, config: { ...(n.config ?? {}) } },
      label: rotulo(n.id, n.tipo),
      class: n.id === def.inicio ? 'no-inicio' : '',
    };
  });

  const edges = arestas.map((a, i) => ({
    id: `a${i}`,
    source: a.de,
    target: a.para,
    label: a.quando ?? '',
    data: { quando: a.quando ?? '' },
  }));

  return { nodes, edges, inicio: def.inicio };
}

export function grafoParaDefinicao(nodes, edges, inicio) {
  return {
    inicio,
    nos: nodes.map((n) => {
      const config = Object.fromEntries(
        Object.entries(n.data.config ?? {}).filter(([, v]) => v !== '' && v !== null && v !== undefined),
      );
      const no = { id: n.id, tipo: n.data.tipo };
      if (Object.keys(config).length) no.config = config;
      no.posicao = { x: Math.round(n.position.x), y: Math.round(n.position.y) };
      return no;
    }),
    arestas: edges.map((e) => {
      const a = { de: e.source, para: e.target };
      const quando = e.data?.quando;
      if (quando) a.quando = quando;
      return a;
    }),
  };
}

// Renomeia um no e acerta arestas e inicio. Devolve o novo inicio.
export function renomear(nodes, edges, inicio, antigo, novo) {
  for (const n of nodes) {
    if (n.id === antigo) {
      n.id = novo;
      n.label = rotulo(novo, n.data.tipo);
    }
  }
  for (const e of edges) {
    if (e.source === antigo) e.source = novo;
    if (e.target === antigo) e.target = novo;
  }
  return inicio === antigo ? novo : inicio;
}

// Avisos simples para mostrar ao usuario antes de salvar.
export function validar(nodes, edges, inicio) {
  const avisos = [];
  const ids = nodes.map((n) => n.id);
  if (!ids.includes(inicio)) avisos.push('Defina o nó de início.');
  if (new Set(ids).size !== ids.length) avisos.push('Há nós com o mesmo nome.');
  for (const n of nodes) {
    const saidas = edges.filter((e) => e.source === n.id);
    if (saidas.length > 1 && saidas.every((e) => !e.data?.quando)) {
      avisos.push(`"${n.id}" tem várias saídas sem condição.`);
    }
  }
  return avisos;
}
