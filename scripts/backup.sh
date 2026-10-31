#!/usr/bin/env bash
# Backup consistente:
#  - banco: cópia via VACUUM INTO (segura com o painel em uso) + verificação de integridade;
#  - arquivos da aplicação (storage/app) e .env (contém a APP_KEY) em arquivos separados, modo 600.
# Os backups ficam em $DADOS/backups. Copie-os também para FORA deste servidor.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
umask 077
carimbo="$(date +%Y%m%d-%H%M%S)"

if docker ps --format '{{.Names}}' | grep -qx painel-pessoal; then
  compose exec -T painel php artisan painel:backup --destino=/backups
else
  compose run --rm -T painel backup
fi

tar -C "$DADOS" -czf "$DADOS/backups/arquivos-$carimbo.tar.gz" storage/app
cp "$PROJETO/.env" "$DADOS/backups/env-$carimbo.bak"
chmod 600 "$DADOS/backups/arquivos-$carimbo.tar.gz" "$DADOS/backups/env-$carimbo.bak"

ok "backup concluído em $DADOS/backups (banco-*.sqlite, arquivos-$carimbo.tar.gz, env-$carimbo.bak)"
