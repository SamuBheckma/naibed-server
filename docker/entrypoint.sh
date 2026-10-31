#!/bin/sh
# Entrada do contêiner. Quatro modos, separados de propósito:
#   servir   (padrão) executa o painel. NÃO cria banco, NÃO migra, NÃO mexe no administrador.
#   migrar   cria o arquivo do banco se faltar e aplica migrações pendentes (não destrutivo).
#   artisan  executa um comando artisan (ex.: painel:admin).
#   backup   gera cópia consistente do SQLite em /backups.
set -eu
umask 077
cd /app

erro() { echo "[painel] ERRO: $*" >&2; exit 1; }
aviso() { echo "[painel] aviso: $*" >&2; }
info() { echo "[painel] $*"; }

exigir_chave() {
  [ -n "${APP_KEY:-}" ] || erro "APP_KEY vazia. Rode scripts/preparar.sh no host (gera a chave uma única vez, sem sobrescrever)."
}

preparar_pastas() {
  mkdir -p storage/app/private storage/framework/cache/data storage/framework/sessions \
           storage/framework/views storage/logs bootstrap/cache
  banco="${DB_DATABASE:-}"
  [ -n "$banco" ] || erro "DB_DATABASE não definido."
  pasta_banco="$(dirname "$banco")"
  [ -d "$pasta_banco" ] || erro "Pasta do banco inexistente: $pasta_banco. Rode scripts/preparar.sh no host."
  [ -w "$pasta_banco" ] || erro "Sem permissão de escrita em $pasta_banco (o dono deve ser o UID do contêiner)."
  [ -w storage ] || erro "Sem permissão de escrita em storage/ (verifique o dono do volume)."
}

modo="${1:-servir}"

case "$modo" in
  servir)
    exigir_chave
    preparar_pastas
    [ -f "$DB_DATABASE" ] || erro "Banco ainda não criado. Rode uma vez: docker compose run --rm painel migrar"
    if php artisan migrate:status 2>/dev/null | grep -q "Pending"; then
      aviso "há migrações pendentes. Rode: docker compose run --rm painel migrar"
    fi
    # Limpa caches de configuração antigos (sem apagar o cache de dados nem as sessões).
    for c in config:clear route:clear event:clear view:clear; do
      php artisan "$c" >/dev/null 2>&1 || true
    done
    for c in config:cache route:cache event:cache view:cache; do
      php artisan "$c" >/dev/null 2>&1 || aviso "falha em 'artisan $c'; seguindo sem esse cache."
    done
    info "iniciando FrankenPHP (modo convencional) em :8081"
    exec frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
    ;;
  migrar)
    exigir_chave
    preparar_pastas
    [ -f "$DB_DATABASE" ] || { : > "$DB_DATABASE"; info "arquivo do banco criado: $DB_DATABASE"; }
    php artisan migrate --force
    php artisan migrate:status
    ;;
  artisan)
    shift
    exigir_chave
    preparar_pastas
    exec php artisan "$@"
    ;;
  backup)
    exigir_chave
    preparar_pastas
    exec php artisan painel:backup --destino=/backups
    ;;
  *)
    exec "$@"
    ;;
esac
