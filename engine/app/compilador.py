"""Transforma o JSON do fluxo em um grafo do LangGraph."""
from langgraph.graph import END, StateGraph

from .estado import Estado
from .modelos import Fluxo
from .nos import TIPOS


def _limpar_rota(funcao):
    """Garante que a rota de um no nao vaze para o proximo."""
    def executar(estado):
        saida = dict(funcao(estado) or {})
        saida.setdefault("rota", "")
        return saida

    return executar


def compilar(fluxo: Fluxo, checkpointer=None):
    for n in fluxo.nos:
        if n.tipo not in TIPOS:
            raise ValueError(f"tipo de no desconhecido: {n.tipo}")

    g = StateGraph(Estado)
    for n in fluxo.nos:
        g.add_node(n.id, _limpar_rota(TIPOS[n.tipo](n.config)))
    g.set_entry_point(fluxo.inicio)

    saidas: dict[str, list] = {n.id: [] for n in fluxo.nos}
    for a in fluxo.arestas:
        saidas[a.de].append(a)

    for no_id, arestas in saidas.items():
        if not arestas:
            g.add_edge(no_id, END)
        elif len(arestas) == 1 and arestas[0].quando is None:
            g.add_edge(no_id, arestas[0].para)
        else:
            mapa = {(a.quando or "*"): a.para for a in arestas}

            def escolher(estado, mapa=mapa):
                # rota explicita, senao a aresta padrao, senao encerra
                destino = mapa.get(estado.get("rota", ""), mapa.get("*"))
                return destino if destino else END

            destinos = {a.para: a.para for a in arestas}
            g.add_conditional_edges(no_id, escolher, {**destinos, END: END})

    return g.compile(checkpointer=checkpointer)
