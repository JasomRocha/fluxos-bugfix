"""Executa fluxos, guarda o progresso e permite retomar apos aprovacao."""
import sqlite3
import threading
import uuid
from dataclasses import dataclass, field

from langgraph.checkpoint.sqlite import SqliteSaver
from langgraph.types import Command

from .compilador import compilar
from .modelos import Fluxo
from .repositorio import Repositorio


@dataclass
class Execucao:
    id: str
    status: str = "executando"  # executando | aguardando | concluida | erro
    eventos: list[dict] = field(default_factory=list)
    pendencia: dict | None = None
    erro: str | None = None
    estado: dict = field(default_factory=dict)


class Servico:
    def __init__(self, caminho: str = ":memory:"):
        self.repo = Repositorio(caminho)
        con = sqlite3.connect(caminho, check_same_thread=False)
        self.memoria = SqliteSaver(con)
        self.cache: dict[str, Execucao] = {}
        self.trava = threading.Lock()

    def _grafo(self, ex_id: str, fluxo: Fluxo):
        return compilar(fluxo, self.memoria)

    def obter(self, ex_id: str) -> Execucao | None:
        if ex_id in self.cache:
            return self.cache[ex_id]
        linha = self.repo.carregar(ex_id)
        if not linha:
            return None
        import json
        _, status, pend, erro, eventos, estado = linha
        ex = Execucao(ex_id, status, json.loads(eventos), json.loads(pend) if pend else None,
                      erro, json.loads(estado))
        if ex.status == "executando":  # o processo caiu no meio
            ex.status, ex.erro = "erro", "interrompida por reinicio do motor"
        self.cache[ex_id] = ex
        return ex

    def iniciar(self, fluxo: Fluxo, dados: dict, assincrono: bool = True) -> Execucao:
        ex = Execucao(id=uuid.uuid4().hex)
        self.cache[ex.id] = ex
        self.repo.criar(ex.id, fluxo.model_dump(), ex.status)
        self._disparar(ex, fluxo, {"dados": dados, "historico": []}, assincrono)
        return ex

    def retomar(self, ex_id: str, aprovado: bool, assincrono: bool = True) -> Execucao:
        ex = self.obter(ex_id)
        if ex is None:
            raise KeyError(ex_id)
        if ex.status != "aguardando":
            raise ValueError("execucao nao esta aguardando aprovacao")
        import json
        fluxo = Fluxo(**json.loads(self.repo.carregar(ex_id)[0]))
        ex.status, ex.pendencia = "executando", None
        self._disparar(ex, fluxo, Command(resume={"aprovado": aprovado}), assincrono)
        return ex

    def _disparar(self, ex: Execucao, fluxo: Fluxo, entrada, assincrono: bool):
        alvo = lambda: self._rodar(ex, fluxo, entrada)
        if assincrono:
            threading.Thread(target=alvo, daemon=True).start()
        else:
            alvo()

    def _rodar(self, ex: Execucao, fluxo: Fluxo, entrada):
        grafo = self._grafo(ex.id, fluxo)
        cfg = {"configurable": {"thread_id": ex.id}}
        try:
            for parte in grafo.stream(entrada, cfg, stream_mode="updates"):
                for no, saida in parte.items():
                    if no == "__interrupt__":
                        ex.pendencia = saida[0].value
                        continue
                    with self.trava:
                        ex.eventos.append({"no": no, "saida": saida})
            instantaneo = grafo.get_state(cfg)
            ex.estado = instantaneo.values
            ex.status = "aguardando" if instantaneo.next else "concluida"
        except Exception as e:  # noqa: BLE001
            ex.status, ex.erro = "erro", f"{type(e).__name__}: {e}"
        self.repo.salvar(ex)
