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
    s.retomar(ex.id, True, assincrono=False)
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
    s2.retomar(ex.id, True, assincrono=False)
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
