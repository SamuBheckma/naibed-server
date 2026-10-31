#!/usr/bin/env bash
# Verificações pós-instalação. Só LÊ o estado; não altera nenhum serviço.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
cd "$PROJETO"
falhas=0
falha() { aviso "$*"; falhas=$((falhas+1)); }

compose config -q && ok "compose.yaml válido" || falha "compose.yaml inválido"

# Porta publicada só em loopback
publicado="$(docker port painel-pessoal 8081/tcp 2>/dev/null || true)"
if [ -z "$publicado" ]; then falha "contêiner não está publicando 8081"
elif echo "$publicado" | grep -qv '^127\.0\.0\.1:8081$'; then falha "porta publicada fora do loopback: $publicado"
else ok "porta publicada apenas em $publicado"; fi
if command -v ss >/dev/null; then
  if ss -H -ltn "sport = :$PORTA" | awk '{print $4}' | grep -qv '^127\.0\.0\.1:'; then
    falha "8081 escutando em endereço não-loopback"
  else ok "8081 só em 127.0.0.1"; fi
fi

base="http://127.0.0.1:$PORTA"
codigo() { curl -s -o /dev/null -w '%{http_code}' "$1"; }

[ "$(codigo "$base/up")" = "200" ] && ok "/up responde 200" || falha "/up não respondeu 200"
[ "$(codigo "$base/entrar")" = "200" ] && ok "tela de login responde 200" || falha "login indisponível"
[ "$(codigo "$base/")" = "302" ] && ok "início exige login (302)" || falha "início não redirecionou"
[ "$(codigo "$base/projetos")" = "302" ] && ok "projetos exige login (302)" || falha "projetos não redirecionou"

for caminho in /.env /.git/config /database/data/database.sqlite /database/database.sqlite \
               /storage/logs/laravel.log /composer.json /composer.lock /artisan /vendor/autoload.php /backups/; do
  c="$(codigo "$base$caminho")"
  if [ "$c" = "404" ]; then ok "$caminho -> 404"; else falha "$caminho -> $c (esperado 404)"; fi
done

# Cabeçalhos e recursos externos
cab="$(curl -s -D - -o /dev/null "$base/entrar")"
for h in content-security-policy x-content-type-options x-frame-options referrer-policy; do
  if echo "$cab" | grep -qi "^$h:"; then ok "cabeçalho $h"; else falha "faltou cabeçalho $h"; fi
done
if echo "$cab" | grep -i '^set-cookie:' | grep -qi 'httponly'; then ok "cookie HttpOnly"; else falha "cookie sem HttpOnly"; fi
if echo "$cab" | grep -i '^set-cookie:' | grep -qi 'samesite=lax'; then ok "cookie SameSite=Lax"; else falha "cookie sem SameSite=Lax"; fi

html="$(curl -s "$base/entrar"; curl -s "$base/assets/css/painel.css")"
if echo "$html" | grep -Eqi "(src|href|action)=[\"']?(https?:)?//|@import|url\([\"']?https?:"; then
  falha "possível referência externa encontrada"
else ok "nenhuma referência externa em HTML/CSS"; fi

# Serviços existentes continuam rodando (só consulta)
for s in site-onion jellyfin; do
  if docker ps --format '{{.Names}}' | grep -qx "$s"; then ok "contêiner '$s' em execução"
  else aviso "contêiner '$s' não aparece em 'docker ps' (se o nome for outro, confira manualmente; o Jellyfin pode rodar fora do Docker)"; fi
done

echo
if [ "$falhas" -eq 0 ]; then ok "todas as verificações passaram"; else aviso "$falhas verificação(ões) com problema"; exit 1; fi
