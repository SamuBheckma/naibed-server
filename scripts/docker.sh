#!/usr/bin/env bash
# Cliente Docker com elevação apenas quando necessária.
source "$(dirname -- "${BASH_SOURCE[0]}")/_comum.sh"
cd "$PROJETO"
docker "$@"
