#!/usr/bin/env bash
# Preparação inicial (rode UMA vez; pode repetir sem danos).
#  - confere que a porta 8081 está livre;
#  - cria as pastas persistentes com o dono certo (UID/GID 1000);
#  - cria .env a partir do exemplo e gera a APP_KEY somente se estiver vazia.
# Não toca em site-onion, Jellyfin, Tor nem nas pastas de mídia.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"

command -v openssl >/dev/null || erro "openssl não encontrado."
command -v docker  >/dev/null || erro "docker não encontrado."

# 1) Porta livre? (só consulta; não altera nada)
if command -v ss >/dev/null; then
  if [ -n "$(ss -H -ltn "sport = :$PORTA" 2>/dev/null)" ]; then
    erro "A porta $PORTA já está em uso. Descubra quem usa com: ss -ltnp 'sport = :$PORTA'"
  fi
  ok "porta $PORTA livre"
else
  aviso "comando 'ss' ausente; confirme manualmente que a porta $PORTA está livre."
fi

# 2) Pastas persistentes (dados fora da camada descartável do contêiner)
for d in database storage backups; do
  como_root install -d -o 1000 -g 1000 -m 700 "$DADOS/$d"
done
ok "pastas em $DADOS (dono 1000:1000, modo 700)"

# 3) .env e APP_KEY (nunca sobrescreve uma chave existente)
cd "$PROJETO"
if [ ! -f .env ]; then
  cp .env.example .env
  chmod 600 .env
  ok ".env criado a partir de .env.example"
fi

chave_atual="$(grep -E '^APP_KEY=' .env | head -n1 | cut -d= -f2- || true)"
if [ -z "$chave_atual" ]; then
  nova="base64:$(openssl rand -base64 32)"
  if grep -qE '^APP_KEY=' .env; then
    sed -i "s|^APP_KEY=.*|APP_KEY=${nova}|" .env
  else
    printf 'APP_KEY=%s\n' "$nova" >> .env
  fi
  ok "APP_KEY gerada (guarde um backup do .env)"
else
  ok "APP_KEY já existe; mantida sem alterações"
fi

cat <<'MSG'

Próximos passos:
  1) scripts/gerar-lock.sh        (apenas na primeira vez: gera composer.lock)
  2) docker compose build
  3) docker compose run --rm painel migrar
  4) docker compose run --rm painel artisan painel:admin
  5) docker compose up -d
MSG
