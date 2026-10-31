#!/usr/bin/env bash
# Restaura o banco a partir de um backup:
#   scripts/restaurar.sh /var/lib/painel-pessoal/backups/banco-AAAAMMDD-HHMMSS.sqlite
# Para o painel, guarda o banco atual ao lado (.antes-da-restauracao-*), troca o arquivo e sobe de novo.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
umask 077
origem="${1:-}"
[ -n "$origem" ] && [ -f "$origem" ] || erro "Informe o arquivo de backup (.sqlite). Uso: $0 CAMINHO"

alvo="$DADOS/database/database.sqlite"
read -r -p "Isto SUBSTITUI o banco atual por '$origem'. Digite 'sim' para continuar: " resp
[ "$resp" = "sim" ] || erro "Cancelado."

compose stop painel || true
if [ -f "$alvo" ]; then
  cp "$alvo" "$alvo.antes-da-restauracao-$(date +%Y%m%d-%H%M%S)"
fi
rm -f "$alvo-wal" "$alvo-shm"
como_root install -o 1000 -g 1000 -m 600 "$origem" "$alvo"

compose run --rm painel migrar   # aplica migrações novas, se o backup for antigo
compose up -d
ok "banco restaurado. Teste o login e confira os dados."
