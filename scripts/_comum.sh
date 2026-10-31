#!/usr/bin/env bash
# Funções compartilhadas pelos scripts. Não execute diretamente.
set -euo pipefail

PROJETO="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
DADOS="${PAINEL_DADOS:-/var/lib/painel-pessoal}"
PORTA=8081

erro()  { printf '\033[31m[erro]\033[0m %s\n' "$*" >&2; exit 1; }
aviso() { printf '\033[33m[aviso]\033[0m %s\n' "$*" >&2; }
ok()    { printf '\033[32m[ok]\033[0m %s\n' "$*"; }
info()  { printf '[..] %s\n' "$*"; }

como_root() { if [ "$(id -u)" -eq 0 ]; then "$@"; else sudo "$@"; fi; }
compose()   { (cd "$PROJETO" && docker compose "$@"); }
