import test from 'node:test';
import assert from 'node:assert/strict';
import { definicaoParaGrafo, grafoParaDefinicao, renomear, validar } from '../resources/js/Componentes/conversao.js';

const def = {
  inicio: 'e',
  nos: [
    { id: 'e', tipo: 'entrada' },
    { id: 't', tipo: 'comando', config: { comando: 'echo', chave: 'x' } },
    { id: 'a', tipo: 'aprovacao', config: { pergunta: '?' } },
    { id: 'f', tipo: 'fim' },
  ],
  arestas: [
    { de: 'e', para: 't' },
    { de: 't', para: 'a', quando: 'ok' },
    { de: 't', para: 'f', quando: 'falha' },
    { de: 'a', para: 'f' },
  ],
};

test('ida e volta preserva nos, config e arestas', () => {
  const g = definicaoParaGrafo(def);
  const volta = grafoParaDefinicao(g.nodes, g.edges, g.inicio);
  assert.deepEqual(volta.arestas, def.arestas);
  assert.deepEqual(volta.nos.map((n) => [n.id, n.tipo, n.config]), def.nos.map((n) => [n.id, n.tipo, n.config]));
  assert.equal(volta.inicio, 'e');
});

test('posicao automatica cresce com a profundidade', () => {
  const g = definicaoParaGrafo(def);
  const x = Object.fromEntries(g.nodes.map((n) => [n.id, n.position.x]));
  assert.ok(x.e < x.t && x.t < x.a);
});

test('posicao salva e respeitada', () => {
  const g = definicaoParaGrafo({ ...def, nos: def.nos.map((n) => ({ ...n, posicao: { x: 5, y: 9 } })) });
  assert.deepEqual(g.nodes[0].position, { x: 5, y: 9 });
});

test('renomear atualiza arestas e inicio', () => {
  const g = definicaoParaGrafo(def);
  const novo = renomear(g.nodes, g.edges, g.inicio, 'e', 'comeco');
  assert.equal(novo, 'comeco');
  assert.equal(g.edges[0].source, 'comeco');
  assert.equal(g.nodes[0].id, 'comeco');
});

test('config vazia nao vai para o JSON', () => {
  const g = definicaoParaGrafo(def);
  g.nodes[1].data.config.diretorio = '';
  assert.equal(grafoParaDefinicao(g.nodes, g.edges, 'e').nos[1].config.diretorio, undefined);
});

test('validar avisa saidas multiplas sem condicao', () => {
  const g = definicaoParaGrafo({ ...def, arestas: [{ de: 'e', para: 't' }, { de: 'e', para: 'f' }] });
  assert.equal(validar(g.nodes, g.edges, 'e').length, 1);
  assert.equal(validar(definicaoParaGrafo(def).nodes, definicaoParaGrafo(def).edges, 'e').length, 0);
});
