"""Formato JSON do fluxo, o mesmo que o editor visual vai gravar."""
from typing import Any, Optional

from pydantic import BaseModel, Field, model_validator


class No(BaseModel):
    id: str
    tipo: str
    config: dict[str, Any] = Field(default_factory=dict)


class Aresta(BaseModel):
    de: str
    para: str
    quando: Optional[str] = None  # vazio = aresta padrao


class Fluxo(BaseModel):
    nos: list[No]
    arestas: list[Aresta] = Field(default_factory=list)
    inicio: str

    @model_validator(mode="after")
    def validar(self):
        ids = [n.id for n in self.nos]
        if len(ids) != len(set(ids)):
            raise ValueError("ids de nos repetidos")
        if self.inicio not in ids:
            raise ValueError("no inicial inexistente")
        for a in self.arestas:
            if a.de not in ids or a.para not in ids:
                raise ValueError(f"aresta aponta para no inexistente: {a.de} -> {a.para}")
        return self
