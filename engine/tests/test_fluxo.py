import sys

from fastapi.testclient import TestClient

from app.api import app
from app.compilador import compilar
from app.modelos import Fluxo
from app.servico import Servico

PY = sys.executable

FLUXO = {
    "inicio": "e",
    "nos": [
        {"id": "e", "tipo": "entrada"},
        {"id": "t", "tipo": "comando", "config": {"comando": f"{PY} -c 'print(1)'", "chave": "teste"}},
        {"id": "a", "tipo": "aprovacao", "config": {"pergunta": "Abrir PR do bug {bug}?"}},
        {"id": "f", "tipo": "fim"},
        {"id": "r", "tipo": "fim"},
    ],
    "arestas": [
        {"de": "e", "para": "t"},
        {"de": "t", "para": "a", "quando": "ok"},
        {"de": "t", "para": "r", "quando": "falha"},
        {"de": "a", "para": "f", "quando": "aprovado"},
        {"de": "a", "para": "r", "quando": "recusado"},
    ],
}


def test_fluxo_pausa_e_conclui_com_aprovacao():
    s = Servico()
    ex = s.iniciar(Fluxo(**FLUXO), {"bug": "123"}, assincrono=False)
    assert ex.status == "aguardando"
    assert ex.pendencia["pergunta"] == "Abrir PR do bug 123?"
    s.retomar(ex.id, {"aprovado": True}, "aprovacao", assincrono=False)
    assert ex.status == "concluida"
    assert ex.estado["historico"][-2:] == ["aprovado", "fim"]


def test_comando_com_falha_segue_aresta_falha():
    f = {**FLUXO, "nos": [dict(n) for n in FLUXO["nos"]]}
    f["nos"][1]["config"] = {"comando": f"{PY} -c 'raise SystemExit(3)'", "chave": "teste"}
    s = Servico()
    ex = s.iniciar(Fluxo(**f), {}, assincrono=False)
    assert ex.status == "concluida"
    assert ex.estado["dados"]["teste_codigo"] == 3
    assert "aprovado" not in ex.estado["historico"]


def test_tipo_desconhecido_e_rejeitado():
    f = {"inicio": "x", "nos": [{"id": "x", "tipo": "inexistente"}]}
    try:
        compilar(Fluxo(**f))
        assert False
    except ValueError as e:
        assert "desconhecido" in str(e)


def test_api_ponta_a_ponta():
    c = TestClient(app)
    r = c.post("/execucoes", json={"fluxo": FLUXO, "dados": {"bug": "9"}})
    assert r.status_code == 200
    ex_id = r.json()["id"]
    import time
    for _ in range(50):
        if c.get(f"/execucoes/{ex_id}").json()["status"] == "aguardando":
            break
        time.sleep(0.1)
    assert c.get(f"/execucoes/{ex_id}").json()["status"] == "aguardando"
    c.post(f"/execucoes/{ex_id}/decisao", json={"aprovado": False})
    for _ in range(50):
        if c.get(f"/execucoes/{ex_id}").json()["status"] == "concluida":
            break
        time.sleep(0.1)
    assert c.get(f"/execucoes/{ex_id}").json()["status"] == "concluida"


def test_execucao_sobrevive_a_reinicio(tmp_path):
    banco = str(tmp_path / "m.sqlite")
    s1 = Servico(banco)
    ex = s1.iniciar(Fluxo(**FLUXO), {"bug": "7"}, assincrono=False)
    assert ex.status == "aguardando"
    s2 = Servico(banco)  # novo processo, mesmo arquivo
    assert s2.obter(ex.id).status == "aguardando"
    s2.retomar(ex.id, {"aprovado": True}, "aprovacao", assincrono=False)
    assert s2.obter(ex.id).status == "concluida"


def test_git_preparar_e_comando_no_clone(tmp_path):
    import subprocess
    origem = tmp_path / "origem"
    origem.mkdir()
    for c in (["init", "-q"], ["config", "user.email", "t@t"], ["config", "user.name", "t"]):
        subprocess.run(["git", *c], cwd=origem, check=True)
    (origem / "a.txt").write_text("x")
    subprocess.run(["git", "add", "."], cwd=origem, check=True)
    subprocess.run(["git", "commit", "-qm", "i"], cwd=origem, check=True)
    f = {"inicio": "g", "nos": [
        {"id": "g", "tipo": "git_preparar",
         "config": {"repositorio": str(origem), "pasta_base": str(tmp_path / "w")}},
        {"id": "c", "tipo": "comando",
         "config": {"comando": "git rev-parse --abbrev-ref HEAD", "diretorio": "{diretorio}", "chave": "b"}},
    ], "arestas": [{"de": "g", "para": "c"}]}
    ex = Servico().iniciar(Fluxo(**f), {"bug": "42"}, assincrono=False)
    assert ex.status == "concluida", ex.erro
    assert ex.estado["dados"]["b_saida"].strip() == "correcao/42"


GUIADO = {
    "inicio": "e",
    "nos": [
        {"id": "e", "tipo": "entrada"},
        {"id": "i", "tipo": "etapa_guiada", "config": {"chave": "invest", "titulo": "Investigar", "instrucao": "Investigue o bug {bug}", "campos": ["causa"]}},
        {"id": "a", "tipo": "aprovacao", "config": {"pergunta": "Causa: {invest_resultado[causa]}. Seguir?"}},
        {"id": "f", "tipo": "fim"},
        {"id": "x", "tipo": "fim"},
    ],
    "arestas": [
        {"de": "e", "para": "i"},
        {"de": "i", "para": "a", "quando": "ok"},
        {"de": "i", "para": "x", "quando": "falha"},
        {"de": "a", "para": "f"},
    ],
}


def test_etapa_guiada_pausa_e_usa_o_resultado_nas_etapas_seguintes():
    s = Servico()
    ex = s.iniciar(Fluxo(**GUIADO), {"bug": "B-1"}, assincrono=False)
    assert ex.status == "aguardando"
    assert ex.pendencia["tipo"] == "etapa_guiada" and ex.pendencia["instrucao"] == "Investigue o bug B-1"
    s.retomar(ex.id, {"sucesso": True, "resultado": {"causa": "sinal trocado"}}, "etapa_guiada", assincrono=False)
    assert ex.status == "aguardando" and ex.pendencia["tipo"] == "aprovacao"
    assert ex.pendencia["pergunta"] == "Causa: sinal trocado. Seguir?"


def test_etapa_com_falha_segue_a_rota_falha():
    s = Servico()
    ex = s.iniciar(Fluxo(**GUIADO), {"bug": "B-2"}, assincrono=False)
    s.retomar(ex.id, {"sucesso": False, "resultado": {}}, "etapa_guiada", assincrono=False)
    assert ex.status == "concluida" and "invest: falhou" in ex.estado["historico"]


def test_tipo_errado_de_resposta_e_recusado():
    s = Servico()
    ex = s.iniciar(Fluxo(**GUIADO), {"bug": "B-3"}, assincrono=False)
    for tipo, resp in (("aprovacao", {"aprovado": True}),):
        try:
            s.retomar(ex.id, resp, tipo, assincrono=False)
            assert False
        except ValueError as e:
            assert "etapa_guiada" in str(e)
    assert ex.status == "aguardando"


def test_api_resposta_de_etapa():
    c = TestClient(app)
    ex_id = c.post("/execucoes", json={"fluxo": GUIADO, "dados": {"bug": "B-4"}}).json()["id"]
    import time
    for _ in range(50):
        if c.get(f"/execucoes/{ex_id}").json()["status"] == "aguardando":
            break
        time.sleep(0.1)
    assert c.post(f"/execucoes/{ex_id}/decisao", json={"aprovado": True}).status_code == 409  # pendencia e uma etapa
    r = c.post(f"/execucoes/{ex_id}/resposta", json={"sucesso": True, "resultado": {"causa": "x"}})
    assert r.status_code == 200


def test_preencher_nao_quebra_quando_falta_campo():
    from app.nos import preencher
    assert preencher("Causa: {x_resultado[causa]}", {"x_resultado": {}}) == "Causa: {x_resultado[causa]}"
    assert preencher("Bug {bug}", {"bug": "1"}) == "Bug 1"
    assert preencher("Bug {nada}", {}) == "Bug {nada}"


def test_pendencia_antiga_sem_tipo_vale_como_aprovacao():
    s = Servico()
    ex = s.iniciar(Fluxo(**FLUXO), {"bug": "1"}, assincrono=False)
    ex.pendencia = {"pergunta": "Abrir PR do bug 1?"}  # formato anterior, sem tipo
    s.retomar(ex.id, {"aprovado": True}, "aprovacao", assincrono=False)
    assert ex.status == "concluida"
