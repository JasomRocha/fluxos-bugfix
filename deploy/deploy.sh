#!/usr/bin/env bash
# Envia o projeto para a VM e sobe os containers.
# Uso: deploy/deploy.sh ubuntu@IP dominio.exemplo.com.br
set -euo pipefail
ALVO="${1:?uso: deploy.sh usuario@ip dominio}"; DOMINIO="${2:?informe o dominio}"
cd "$(dirname "$0")/.."

rsync -az --delete \
  --exclude .git --exclude .env --exclude '.venv' --exclude node_modules --exclude vendor \
  --exclude '*.sqlite*' --exclude 'laravel/public/build' --exclude 'laravel/CLAUDE.md' --exclude 'laravel/AGENTS.md' \
  ./ "$ALVO:fluxos/"

# Segredos novos no servidor, na primeira vez. Nao reaproveita os do computador.
ssh "$ALVO" "cd fluxos && [ -f .env ] || {
  umask 077
  { echo APP_KEY=base64:\$(openssl rand -base64 32); echo MOTOR_TOKEN=\$(openssl rand -hex 24);
    echo MCP_TOKEN=\$(openssl rand -hex 32); echo DOMINIO=$DOMINIO; } > .env; echo '.env criado'; }
  grep -q '^DOMINIO=' .env || echo DOMINIO=$DOMINIO >> .env
  test -f /etc/fluxos/tls/origin.pem || echo 'AVISO: falta /etc/fluxos/tls/origin.pem e origin.key'
  docker compose -f docker-compose.yml -f deploy/docker-compose.prod.yml up -d --build
  docker compose ps"
