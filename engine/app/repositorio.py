"""Guarda execucoes em SQLite para sobreviver a reinicios."""
import json
import sqlite3
import threading


class Repositorio:
    def __init__(self, caminho: str):
        self.con = sqlite3.connect(caminho, check_same_thread=False)
        self.trava = threading.Lock()
        with self.trava:
            self.con.execute(
                "create table if not exists execucoes (id text primary key, fluxo text not null,"
                " status text not null, pendencia text, erro text, eventos text not null default '[]',"
                " estado text not null default '{}')"
            )
            self.con.commit()

    def criar(self, ex_id: str, fluxo: dict, status: str):
        with self.trava:
            self.con.execute("insert into execucoes (id, fluxo, status) values (?,?,?)",
                             (ex_id, json.dumps(fluxo), status))
            self.con.commit()

    def salvar(self, ex):
        with self.trava:
            self.con.execute(
                "update execucoes set status=?, pendencia=?, erro=?, eventos=?, estado=? where id=?",
                (ex.status, json.dumps(ex.pendencia), ex.erro,
                 json.dumps(ex.eventos, default=str), json.dumps(ex.estado, default=str), ex.id))
            self.con.commit()

    def carregar(self, ex_id: str):
        with self.trava:
            return self.con.execute(
                "select fluxo, status, pendencia, erro, eventos, estado from execucoes where id=?",
                (ex_id,)).fetchone()
