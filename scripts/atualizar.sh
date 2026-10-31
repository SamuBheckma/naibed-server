#!/usr/bin/env bash
# Atualização controlada: backup -> build -> migrações -> subir.
# Nada atualiza sozinho: você traz o código novo (git pull/cópia) e roda este script.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
cd "$PROJETO"
[ -f composer.lock ] || erro "composer.lock ausente."

"$PROJETO/scripts/backup.sh"
compose build
compose run --rm painel migrar
compose up -d
ok "atualizado. Verifique com scripts/verificar.sh"
