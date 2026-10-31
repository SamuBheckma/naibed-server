#!/usr/bin/env bash
# Gera (ou atualiza, se pedido) o composer.lock dentro da MESMA imagem PHP do painel.
# Uso: scripts/gerar-lock.sh            -> resolve e grava composer.lock se não existir
#      scripts/gerar-lock.sh atualizar  -> recalcula as versões (faça backup antes)
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
cd "$PROJETO"

if [ -f composer.lock ] && [ "${1:-}" != "atualizar" ]; then
  ok "composer.lock já existe; nada a fazer (use 'atualizar' para recalcular)."
  exit 0
fi

sudo docker build --target base -t painel-pessoal:base .
sudo docker run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
  -v "$PROJETO":/app -w /app painel-pessoal:base \
  composer update --no-interaction --no-progress --no-scripts --prefer-dist
ok "composer.lock gravado. Versione-o junto com o código."
