"""API HTTP consumida pelo Laravel."""
import asyncio
import json
import os

from fastapi import FastAPI, Header, HTTPException
from fastapi.responses import StreamingResponse
from pydantic import BaseModel

from .modelos import Fluxo
from .servico import Servico

app = FastAPI(title="Motor de fluxos")
servico = Servico(os.environ.get("MOTOR_BANCO", "motor.sqlite"))
TOKEN = os.environ.get("MOTOR_TOKEN", "")


def autorizar(token: str | None):
    if TOKEN and token != TOKEN:
        raise HTTPException(401, "token invalido")


class PedidoExecucao(BaseModel):
    fluxo: Fluxo
    dados: dict = {}


class Decisao(BaseModel):
    aprovado: bool


class RespostaEtapa(BaseModel):
    sucesso: bool = True
    resultado: dict = {}


def resumo(ex):
    return {"id": ex.id, "status": ex.status, "pendencia": ex.pendencia, "erro": ex.erro,
            "dados": ex.estado.get("dados", {}), "historico": ex.estado.get("historico", [])}


@app.post("/execucoes")
def criar(p: PedidoExecucao, x_token: str | None = Header(default=None)):
    autorizar(x_token)
    try:
        return resumo(servico.iniciar(p.fluxo, p.dados))
    except ValueError as e:
        raise HTTPException(422, str(e))


@app.get("/execucoes/{ex_id}")
def consultar(ex_id: str, x_token: str | None = Header(default=None)):
    autorizar(x_token)
    ex = servico.obter(ex_id)
    if not ex:
        raise HTTPException(404, "execucao inexistente")
    return resumo(ex)


@app.post("/execucoes/{ex_id}/decisao")
def decidir(ex_id: str, d: Decisao, x_token: str | None = Header(default=None)):
    autorizar(x_token)
    try:
        return resumo(servico.retomar(ex_id, {"aprovado": d.aprovado}, "aprovacao"))
    except KeyError:
        raise HTTPException(404, "execucao inexistente")
    except ValueError as e:
        raise HTTPException(409, str(e))


@app.post("/execucoes/{ex_id}/resposta")
def responder(ex_id: str, r: RespostaEtapa, x_token: str | None = Header(default=None)):
    autorizar(x_token)
    try:
        return resumo(servico.retomar(ex_id, {"sucesso": r.sucesso, "resultado": r.resultado}, "etapa_guiada"))
    except KeyError:
        raise HTTPException(404, "execucao inexistente")
    except ValueError as e:
        raise HTTPException(409, str(e))


@app.get("/execucoes/{ex_id}/eventos")
async def eventos(ex_id: str, x_token: str | None = Header(default=None)):
    autorizar(x_token)
    ex = servico.obter(ex_id)
    if not ex:
        raise HTTPException(404, "execucao inexistente")

    async def fluxo_sse():
        enviados = 0
        while True:
            while enviados < len(ex.eventos):
                yield f"data: {json.dumps(ex.eventos[enviados], default=str)}\n\n"
                enviados += 1
            if ex.status != "executando":
                yield f"event: fim\ndata: {json.dumps(resumo(ex), default=str)}\n\n"
                return
            await asyncio.sleep(0.3)

    return StreamingResponse(fluxo_sse(), media_type="text/event-stream")
