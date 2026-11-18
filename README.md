# Fluxos de correcao de bugs

Painel Laravel (fluxos, execucoes, aprovacao) + motor LangGraph que roda os fluxos com um modelo local do Ollama.

## Subir

    cp .env.example .env   # preencha APP_KEY, MOTOR_TOKEN, MCP_TOKEN, OLLAMA_MODELO
    docker compose up -d --build
    docker compose exec laravel php artisan usuario:criar EMAIL SENHA

Painel em http://localhost:8080.

## Conectar o Claude Code (servidor MCP)

O painel expoe um servidor MCP em `/mcp`, protegido por `MCP_TOKEN`. Assim o Claude Code
(sua assinatura) cria, executa e aprova fluxos sem nenhuma chave de API.

    claude mcp add --transport http fluxos http://localhost:8080/mcp \
      --header "Authorization: Bearer $(grep ^MCP_TOKEN= .env | cut -d= -f2)"

Ferramentas: `tipos_de_no`, `listar_fluxos`, `ver_fluxo`, `criar_fluxo`, `atualizar_fluxo`,
`executar_fluxo`, `listar_execucoes`, `consultar_execucao`, `decidir_execucao`.

`decidir_execucao` pode abrir PR: peca ao Claude Code para mostrar o historico e esperar sua decisao.

## Testes

    (cd engine && .venv/bin/pytest -q)
    (cd laravel && node --test tests-js/)
    docker run --rm -u $(id -u):$(id -g) --entrypoint php -v "$PWD/laravel":/var/www/html -w /var/www/html \
      -e APP_ENV=testing -e DB_DATABASE=:memory: -e AUTORUN_ENABLED=false fluxos-bugfix-laravel artisan test
