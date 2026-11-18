"""Tipos de no disponiveis. Cada fabrica recebe a config e devolve a funcao do no."""
import os
import shlex
import subprocess
import uuid
from typing import Callable

from langgraph.types import interrupt

from .estado import Estado


class _Modelo(dict):
    """Permite {chave} no texto sem falhar quando falta variavel."""

    def __missing__(self, chave):
        return "{" + chave + "}"


def preencher(texto: str, dados: dict) -> str:
    """Troca {variavel} e {variavel[campo]}. Se algo faltar, devolve o texto como esta, sem quebrar o fluxo."""
    try:
        return texto.format_map(_Modelo(dados))
    except (KeyError, IndexError, ValueError, AttributeError, TypeError):
        return texto


def no_entrada(config: dict):
    def executar(estado: Estado):
        return {"historico": ["entrada recebida"]}

    return executar


def no_comando(config: dict):
    """Roda um comando no diretorio do projeto. Sem shell, para evitar injecao."""
    def executar(estado: Estado):
        dados = estado.get("dados", {})
        argv = shlex.split(preencher(config["comando"], dados))
        cwd = preencher(config.get("diretorio", "."), dados)
        r = subprocess.run(argv, cwd=cwd, capture_output=True, text=True,
                           timeout=config.get("limite_segundos", 600))
        chave = config.get("chave", "comando")
        return {
            "dados": {f"{chave}_saida": (r.stdout + r.stderr)[-4000:], f"{chave}_codigo": r.returncode},
            "rota": "ok" if r.returncode == 0 else "falha",
            "historico": [f"{chave}: codigo {r.returncode}"],
        }

    return executar


def criar_modelo(config: dict):
    """Cria o modelo do agente (Ollama ou OpenAI). Os testes trocam esta funcao por um falso."""
    provedor = (config.get("provedor") or os.environ.get("MOTOR_PROVEDOR") or "ollama").lower()

    if provedor == "openai":
        from langchain_openai import ChatOpenAI

        chave = os.environ.get("OPENAI_API_KEY")
        if not chave:
            raise RuntimeError("defina OPENAI_API_KEY no .env para usar o provedor openai")
        nome = config.get("modelo") or os.environ.get("OPENAI_MODELO")
        if not nome:
            raise RuntimeError("defina OPENAI_MODELO ou 'modelo' na config do no")
        return ChatOpenAI(model=nome, api_key=chave, temperature=0, max_retries=2, timeout=120)

    if provedor != "ollama":
        raise RuntimeError(f"provedor desconhecido: {provedor} (use ollama ou openai)")

    from langchain_ollama import ChatOllama

    nome = config.get("modelo") or os.environ.get("OLLAMA_MODELO")
    if not nome:
        raise RuntimeError("defina OLLAMA_MODELO ou 'modelo' na config do no")
    return ChatOllama(
        model=nome,
        base_url=os.environ.get("OLLAMA_URL", "http://host.docker.internal:11434"),
        temperature=0,
        num_ctx=config.get("contexto", 16384),
    )


def no_agente(config: dict):
    """Agente de codigo com modelo local, preso ao diretorio do projeto."""
    def executar(estado: Estado):
        from .agente import criar_ferramentas, rodar_agente

        dados = estado.get("dados", {})
        diretorio = preencher(config.get("diretorio", "."), dados)
        ferramentas = criar_ferramentas(diretorio, config.get("comandos_permitidos"))
        resposta, resultado = rodar_agente(
            criar_modelo(config), preencher(config["prompt"], dados), ferramentas,
            max_turnos=config.get("max_turnos", 30),
            insistencias=config.get("insistencias", 2),
        )
        chave = config.get("chave", "agente")
        return {
            "dados": {f"{chave}_resposta": resposta},
            "rota": resultado,
            "historico": [f"{chave}: {resultado}"],
        }

    return executar


def no_aprovacao(config: dict):
    """Pausa o fluxo ate alguem aprovar ou recusar pela interface."""
    def executar(estado: Estado):
        dados = estado.get("dados", {})
        decisao = interrupt({"tipo": "aprovacao", "pergunta": preencher(config.get("pergunta", "Aprovar?"), dados)})
        aprovado = bool(decisao.get("aprovado")) if isinstance(decisao, dict) else bool(decisao)
        return {"rota": "aprovado" if aprovado else "recusado",
                "historico": ["aprovado" if aprovado else "recusado"]}

    return executar


def no_etapa_guiada(config: dict):
    """Pausa e entrega a instrucao a quem conduz o fluxo (o Claude Code, com o usuario acompanhando)."""
    def executar(estado: Estado):
        dados = estado.get("dados", {})
        resposta = interrupt({
            "tipo": "etapa_guiada",
            "titulo": preencher(config.get("titulo", "Etapa"), dados),
            "instrucao": preencher(config["instrucao"], dados),
            "campos": config.get("campos", []),
        })
        resposta = resposta if isinstance(resposta, dict) else {}
        sucesso = bool(resposta.get("sucesso", True))
        chave = config.get("chave", "etapa")
        return {
            "dados": {f"{chave}_resultado": resposta.get("resultado", {})},
            "rota": "ok" if sucesso else "falha",
            "historico": [f"{chave}: " + ("concluida" if sucesso else "falhou")],
        }

    return executar


def no_fim(config: dict):
    def executar(estado: Estado):
        return {"historico": ["fim"]}

    return executar


def _git(args: list[str], cwd: str):
    r = subprocess.run(["git", *args], cwd=cwd, capture_output=True, text=True, timeout=300)
    if r.returncode != 0:
        raise RuntimeError(f"git {args[0]} falhou: {(r.stderr or r.stdout)[-500:]}")
    return r.stdout.strip()


def no_git_preparar(config: dict):
    """Clona o repositorio em uma pasta de trabalho propria e cria a branch da correcao."""
    def executar(estado: Estado):
        dados = estado.get("dados", {})
        repo = preencher(config["repositorio"], dados)
        branch = preencher(config.get("branch", "correcao/{bug}"), dados)
        base = os.path.abspath(preencher(config.get("pasta_base", "trabalho"), dados))
        os.makedirs(base, exist_ok=True)
        destino = os.path.join(base, uuid.uuid4().hex[:8])
        _git(["clone", "--depth", "50", repo, destino], base)
        _git(["checkout", "-b", branch], destino)
        return {"dados": {"diretorio": destino, "branch": branch},
                "historico": [f"clonado em {destino}, branch {branch}"]}

    return executar


def no_abrir_pr(config: dict):
    """Faz commit das alteracoes, envia a branch e abre o PR com o gh."""
    def executar(estado: Estado):
        dados = estado.get("dados", {})
        d, branch = dados["diretorio"], dados["branch"]
        if not _git(["status", "--porcelain"], d):
            return {"rota": "sem_alteracao", "historico": ["nada para enviar"]}
        titulo = preencher(config.get("titulo", "Correcao do bug {bug}"), dados)
        corpo = preencher(config.get("corpo", "Correcao gerada pelo fluxo."), dados)
        _git(["add", "-A"], d)
        _git(["commit", "-m", titulo], d)
        _git(["push", "-u", "origin", branch], d)
        r = subprocess.run(["gh", "pr", "create", "--title", titulo, "--body", corpo, "--head", branch],
                           cwd=d, capture_output=True, text=True, timeout=120)
        if r.returncode != 0:
            raise RuntimeError(f"gh pr create falhou: {r.stderr[-500:]}")
        url = r.stdout.strip().splitlines()[-1]
        return {"dados": {"pr_url": url}, "rota": "ok", "historico": [f"PR aberto: {url}"]}

    return executar


TIPOS: dict[str, Callable[[dict], Callable]] = {
    "entrada": no_entrada,
    "comando": no_comando,
    "agente": no_agente,
    "aprovacao": no_aprovacao,
    "etapa_guiada": no_etapa_guiada,
    "fim": no_fim,
    "git_preparar": no_git_preparar,
    "abrir_pr": no_abrir_pr,
}
