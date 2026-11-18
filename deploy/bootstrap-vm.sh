#!/usr/bin/env bash
# Prepara uma VM Ubuntu 24.04 nova. Rode na VM: sudo bash bootstrap-vm.sh
set -euo pipefail
apt-get update -y
apt-get install -y docker.io docker-compose-v2 rsync iptables-persistent
systemctl enable --now docker
usermod -aG docker ubuntu
# As imagens Ubuntu da Oracle trazem iptables fechando tudo menos o SSH: libera 80 e 443.
for p in 80 443; do
  iptables -C INPUT -p tcp --dport "$p" -j ACCEPT 2>/dev/null || iptables -I INPUT 5 -p tcp --dport "$p" -j ACCEPT
done
netfilter-persistent save
mkdir -p /etc/fluxos/tls && chmod 750 /etc/fluxos/tls
echo "ok: docker $(docker --version | cut -d' ' -f3), compose $(docker compose version --short)"
