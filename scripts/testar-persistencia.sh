#!/usr/bin/env bash
# Prova que os dados sobrevivem à recriação do contêiner: conta usuários e projetos,
# recria o contêiner (--force-recreate) e confere que os números continuam iguais.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
cd "$PROJETO"

contar() {
  compose exec -T painel php -r '
    $p = new PDO("sqlite:" . getenv("DB_DATABASE"));
    echo $p->query("select count(*) from users")->fetchColumn(), ":", $p->query("select count(*) from projetos")->fetchColumn();'
}

antes="$(contar)"; info "antes de recriar (usuarios:projetos) = $antes"
compose up -d --force-recreate painel
for _ in $(seq 1 30); do
  [ "$(docker inspect -f '{{.State.Health.Status}}' painel-pessoal 2>/dev/null)" = "healthy" ] && break
  sleep 2
done
depois="$(contar)"; info "depois de recriar = $depois"

if [ "$antes" = "$depois" ]; then ok "dados persistiram após recriar o contêiner"; else erro "contagens diferentes: antes=$antes depois=$depois"; fi
