# syntax=docker/dockerfile:1
#
# Imagens base. Depois do primeiro build bem-sucedido, fixe cada uma por digest
# (veja README, "Fixar versões"), p.ex.: dunglas/frankenphp@sha256:...
# Nada atualiza sozinho: só muda quando você reconstruir a imagem de propósito.
ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1-php8.4-bookworm
ARG COMPOSER_IMAGE=composer:2
ARG PUID=1000
ARG PGID=1000

FROM ${COMPOSER_IMAGE} AS composer-bin

# ---------------------------------------------------------------------------
# base: PHP + FrankenPHP + extensões + Composer (usado também para gerar o
# composer.lock e rodar os testes: veja scripts/gerar-lock.sh e scripts/testar.sh)
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS base
RUN install-php-extensions zip pdo_sqlite opcache
COPY --from=composer-bin /usr/bin/composer /usr/bin/composer
WORKDIR /app

# ---------------------------------------------------------------------------
# build: dependências de produção (exige composer.lock)
# ---------------------------------------------------------------------------
FROM base AS build
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts \
        --prefer-dist --optimize-autoloader
COPY . .
RUN mkdir -p bootstrap/cache storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs \
 && composer dump-autoload --no-dev --optimize --no-interaction --no-scripts \
 && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= php artisan package:discover --ansi

# ---------------------------------------------------------------------------
# runtime: imagem final, sem Composer nem dependências de desenvolvimento
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS runtime
ARG PUID
ARG PGID

RUN install-php-extensions pdo_sqlite opcache \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-painel.ini"

# Execução sem privilégios: remove a capability de porta baixa do binário
# (a aplicação escuta em 8081, não precisa dela) para funcionar com cap_drop ALL.
RUN setcap -r /usr/local/bin/frankenphp || true

WORKDIR /app
COPY --from=build --chown=${PUID}:${PGID} /app /app
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/painel-entrypoint

# Caddy grava configuração/estado em /tmp (tmpfs no compose): nada persistente.
ENV XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data \
    APP_ENV=production \
    APP_DEBUG=false

USER ${PUID}:${PGID}
EXPOSE 8081
ENTRYPOINT ["painel-entrypoint"]
CMD ["servir"]
