"""Estado compartilhado entre os nos de um fluxo."""
import operator
from typing import Annotated, Any, TypedDict


def juntar(a: dict, b: dict) -> dict:
    return {**(a or {}), **(b or {})}


class Estado(TypedDict, total=False):
    dados: Annotated[dict[str, Any], juntar]  # variaveis do fluxo
    historico: Annotated[list[str], operator.add]  # trilha legivel
    rota: str  # escolhe a proxima aresta condicional
