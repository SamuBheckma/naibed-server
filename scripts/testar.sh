#!/usr/bin/env bash
# Roda a suíte PHPUnit dentro do contêiner (PHP 8.4 + SQLite em memória).
# Cria ./vendor (com dependências de desenvolvimento) fora da imagem de produção.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
cd "$PROJETO"
[ -f composer.lock ] || erro "composer.lock ausente. Rode scripts/gerar-lock.sh antes."

docker build --target base -t painel-pessoal:base .
docker run --rm --user "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer \
  -v "$PROJETO":/app -w /app painel-pessoal:base \
  sh -c 'composer install --no-interaction --no-progress --prefer-dist && php artisan package:discover --ansi && vendor/bin/phpunit --colors=always'
