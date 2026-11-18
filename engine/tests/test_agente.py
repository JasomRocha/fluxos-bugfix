import sys

from langchain_core.messages import AIMessage

from app import nos
from app.agente import criar_ferramentas, rodar_agente
from app.modelos import Fluxo
from app.servico import Servico


class ModeloFalso:
    """Devolve respostas prontas, em ordem, simulando um modelo que usa ferramentas."""

    def __init__(self, respostas):
        self.respostas = list(respostas)
        self.vistas = []

    def bind_tools(self, ferramentas):
        return self

    def invoke(self, msgs):
        self.vistas.append(list(msgs))
        return self.respostas.pop(0)


def chamada(nome, **args):
    return AIMessage(content="", tool_calls=[{"name": nome, "args": args, "id": f"c{len(args)}{nome}"}])


def projeto(tmp_path):
    (tmp_path / "calc.py").write_text("def soma(a, b):\n    return a - b\n")
    (tmp_path / "test_calc.py").write_text("from calc import soma\n\ndef test_soma():\n    assert soma(2, 3) == 5\n")
    return tmp_path


def test_agente_corrige_bug_com_ferramentas(tmp_path):
    p = projeto(tmp_path)
    m = ModeloFalso([
        chamada("buscar", expressao="return"),
        chamada("substituir", caminho="calc.py", antigo="a - b", novo="a + b"),
        chamada("executar", comando=f"{sys.executable} -m pytest -q"),
        AIMessage(content="Troquei a subtracao por soma."),
    ])
    ferr = criar_ferramentas(str(p), permitidos=[sys.executable])
    resposta, res = rodar_agente(m, "corrija", ferr)
    assert res == "ok" and "soma" in resposta
    assert "a + b" in (p / "calc.py").read_text()
    ultima_saida = m.vistas[-1][-1].content
    assert "codigo 0" in ultima_saida


def test_agente_nao_sai_do_projeto(tmp_path):
    p = projeto(tmp_path)
    fora = tmp_path.parent / "segredo.txt"
    fora.write_text("x")
    ferr = {f.name: f for f in criar_ferramentas(str(p))}
    for caminho in ("../segredo.txt", "/etc/passwd"):
        try:
            ferr["ler"].invoke({"caminho": caminho})
            assert False, caminho
        except ValueError as e:
            assert "fora do projeto" in str(e)
    try:
        ferr["escrever"].invoke({"caminho": ".git/hooks/x", "conteudo": "x"})
        assert False
    except ValueError:
        pass


def test_comandos_perigosos_sao_recusados(tmp_path):
    ferr = {f.name: f for f in criar_ferramentas(str(projeto(tmp_path)))}
    for cmd in ("rm -rf .", "curl http://x", "git push origin main", "git config user.name x", "bash -c ls"):
        assert ferr["executar"].invoke({"comando": cmd}).startswith("erro"), cmd
    assert ferr["executar"].invoke({"comando": "git status"}).startswith("codigo")  # leitura liberada


def test_comando_do_agente_nao_ve_tokens(tmp_path, monkeypatch):
    monkeypatch.setenv("GH_TOKEN", "segredo-123")
    monkeypatch.setenv("MOTOR_TOKEN", "segredo-456")
    ferr = {f.name: f for f in criar_ferramentas(str(projeto(tmp_path)), permitidos=[sys.executable])}
    saida = ferr["executar"].invoke({"comando": f"{sys.executable} -c 'import os;print(os.environ.get(\"GH_TOKEN\"), os.environ.get(\"MOTOR_TOKEN\"))'"})
    assert "None None" in saida and "segredo" not in saida


def test_substituir_exige_trecho_unico(tmp_path):
    p = projeto(tmp_path)
    (p / "d.txt").write_text("a a")
    ferr = {f.name: f for f in criar_ferramentas(str(p))}
    assert "2 vezes" in ferr["substituir"].invoke({"caminho": "d.txt", "antigo": "a", "novo": "b"})
    assert (p / "d.txt").read_text() == "a a"


def test_limite_de_turnos(tmp_path):
    m = ModeloFalso([chamada("listar") for _ in range(3)])
    _, res = rodar_agente(m, "x", criar_ferramentas(str(projeto(tmp_path))), max_turnos=3)
    assert res == "limite"


def test_erro_de_ferramenta_volta_para_o_modelo(tmp_path):
    m = ModeloFalso([chamada("ler", caminho="../x"), AIMessage(content="ok")])
    rodar_agente(m, "x", criar_ferramentas(str(projeto(tmp_path))), insistencias=0)
    assert "fora do projeto" in m.vistas[1][-1].content


def test_no_agente_dentro_de_um_fluxo(tmp_path, monkeypatch):
    p = projeto(tmp_path)
    falso = ModeloFalso([
        chamada("substituir", caminho="calc.py", antigo="a - b", novo="a + b"),
        AIMessage(content="feito"),
    ])
    monkeypatch.setattr(nos, "criar_modelo", lambda config: falso)
    f = {"inicio": "a", "nos": [
        {"id": "a", "tipo": "agente", "config": {"prompt": "Corrija {bug}", "diretorio": "{diretorio}"}},
        {"id": "t", "tipo": "comando", "config": {"comando": f"{sys.executable} -m pytest -q", "diretorio": "{diretorio}"}},
    ], "arestas": [{"de": "a", "para": "t", "quando": "ok"}]}
    ex = Servico().iniciar(Fluxo(**f), {"bug": "soma", "diretorio": str(p)}, assincrono=False)
    assert ex.status == "concluida", ex.erro
    assert ex.estado["dados"]["agente_resposta"] == "feito"
    assert ex.estado["dados"]["comando_codigo"] == 0
    assert "Corrija soma" in falso.vistas[0][1].content


def test_modelo_que_so_descreve_e_instigado_a_agir(tmp_path):
    p = projeto(tmp_path)
    m = ModeloFalso([
        AIMessage(content="Vou buscar o codigo."),  # parou sem chamar ferramenta
        chamada("substituir", caminho="calc.py", antigo="a - b", novo="a + b"),
        AIMessage(content="corrigido"),
    ])
    _, res = rodar_agente(m, "x", criar_ferramentas(str(p)))
    assert res == "ok" and "a + b" in (p / "calc.py").read_text()
    assert "ainda nao alterou" in m.vistas[1][-1].content


def test_sem_alteracao_apos_as_insistencias(tmp_path):
    m = ModeloFalso([AIMessage(content="so conversa") for _ in range(3)])
    _, res = rodar_agente(m, "x", criar_ferramentas(str(projeto(tmp_path))), insistencias=2)
    assert res == "sem_alteracao"
    assert m.respostas == []  # consumiu as 3 respostas: 1 inicial + 2 insistencias


def test_substituir_que_falha_nao_conta_como_alteracao(tmp_path):
    m = ModeloFalso([chamada("substituir", caminho="calc.py", antigo="inexistente", novo="x"),
                     AIMessage(content="pronto")])
    _, res = rodar_agente(m, "x", criar_ferramentas(str(projeto(tmp_path))), insistencias=0)
    assert res == "sem_alteracao"


def test_escrever_nao_cria_pastas_inventadas(tmp_path):
    p = projeto(tmp_path)
    ferr = {f.name: f for f in criar_ferramentas(str(p))}
    assert ferr["escrever"].invoke({"caminho": "loja/precos.py", "conteudo": "x"}).startswith("erro")
    assert not (p / "loja").exists()
    assert ferr["escrever"].invoke({"caminho": "novo.py", "conteudo": "x"}) == "ok"


def test_substituir_em_arquivo_inexistente_orienta(tmp_path):
    ferr = {f.name: f for f in criar_ferramentas(str(projeto(tmp_path)))}
    saida = ferr["substituir"].invoke({"caminho": "loja/x.py", "antigo": "a", "novo": "b"})
    assert "nao existe" in saida and "listar" in saida


def test_buscar_padrao_errado_orienta(tmp_path):
    ferr = {f.name: f for f in criar_ferramentas(str(projeto(tmp_path)))}
    assert "listar" in ferr["buscar"].invoke({"expressao": "soma", "padrao": "src"})
    assert "calc.py:1" in ferr["buscar"].invoke({"expressao": "def soma"})


def test_listar_sem_resultado_orienta(tmp_path):
    ferr = {f.name: f for f in criar_ferramentas(str(projeto(tmp_path)))}
    saida = ferr["listar"].invoke({"padrao": "tests/**/*_test.py"})
    assert saida and "sem argumentos" in saida


def test_chamada_repetida_recebe_aviso(tmp_path):
    m = ModeloFalso([chamada("listar", padrao="x*"), chamada("listar", padrao="x*"), AIMessage(content="fim")])
    rodar_agente(m, "x", criar_ferramentas(str(projeto(tmp_path))), insistencias=0)
    assert "AVISO" not in m.vistas[1][-1].content   # 1a vez: sem aviso
    assert "AVISO" in m.vistas[2][-1].content        # 2a vez igual: com aviso
