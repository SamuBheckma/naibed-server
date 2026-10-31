# Revisão desta entrega

Projeto refatorado a partir do ZIP enviado. O original não foi alterado.

- Scripts elevam apenas comandos Docker com sudo se o usuário não acessa o daemon.
  Execute os scripts como naibed, sem sudo na frente do script inteiro.
- Use scripts/docker.sh compose no lugar de docker compose nos comandos manuais.
- Senhas bcrypt: mínimo 12 caracteres, máximo 72 bytes, inclusive no login.
- Extensões mbstring no runtime; DOM/XML/XMLWriter também na imagem de testes.
- Restauração valida integridade antes de parar, exige parada confirmada e preserva
  banco e arquivos WAL/SHM anteriores em diretório próprio. Falhas de migração
  deixam o painel parado para diagnóstico. Não há rollback automático.
- Atualização para o painel antes das migrações; exige janela de manutenção.
- ZIP contém uma única pasta do projeto, sem a duplicação anterior.

Validação executada nesta revisão: sintaxe de todos os scripts Bash e entrypoint
POSIX, JSON do Composer e teste simulado de falha de parada na restauração
(confirmado que o banco original não é substituído).
PHPUnit, imagem Docker, extensões em execução, login, visual e persistência real
continuam NÃO testados aqui: ambiente sem PHP, Composer e Docker.
composer.lock continua ausente; deve ser gerado no Debian antes do build final.
As tags das imagens ainda precisam ser confirmadas e fixadas por digest após build.
Não publique pelo Tor antes de passar pelos testes e criar o administrador.

## Primeiro passo no Debian

Extraia em pasta nova, entre nela como naibed e execute:

```bash
scripts/preparar.sh
scripts/gerar-lock.sh
scripts/testar.sh
```

Pare no primeiro erro e examine a saída antes de continuar.
A preparação exige a porta 8081 livre: não a execute com o painel já rodando.

---

# Painel pessoal

Central pessoal privada em **Laravel 13 + FrankenPHP (modo convencional) + SQLite**, num único contêiner.
Visual herdado do antigo painel Sentinela (cores, tipografia, barra lateral, cards, responsivo), sem ofertas, lojas, preços, monitores ou simulações.

> **Estado da validação:** este projeto foi escrito num ambiente **sem PHP, Composer nem Docker**. Os testes PHPUnit existem, mas **não foram executados**. Veja "Testes e limitações" no fim e rode `scripts/testar.sh` no Debian antes de confiar.

## Requisitos
Debian com Docker e Docker Compose, usuário com UID/GID 1000 (`naibed`), porta `127.0.0.1:8081` livre. Não toca em `site-onion` (8080), Jellyfin (8096), Tor nem em `/home/naibed/midia`.

## Instalação (nesta ordem)
```bash
cd /home/naibed/painel-pessoal
scripts/preparar.sh                      # confere porta 8081, cria /var/lib/painel-pessoal, cria .env e APP_KEY (uma vez)
scripts/gerar-lock.sh                    # só na 1ª vez: gera composer.lock (versionar!)
scripts/testar.sh                        # roda a suíte PHPUnit no contêiner
scripts/docker.sh compose build
scripts/docker.sh compose run --rm painel migrar    # cria o banco e aplica migrações (não destrutivo)
scripts/docker.sh compose run --rm painel artisan painel:admin
scripts/docker.sh compose up -d                     # execução normal; restart: unless-stopped
scripts/verificar.sh
```
Separação deliberada: `servir` (padrão) **nunca** cria banco, migra ou mexe no administrador; isso é feito só por `migrar` e `artisan`.

## Administrador
- Criar: `scripts/docker.sh compose run --rm painel artisan painel:admin` (pergunta e-mail, nome e senha oculta com confirmação, mín. 12 caracteres).
- Redefinir: `scripts/docker.sh compose run --rm painel artisan painel:admin --redefinir` (encerra as sessões abertas).
- A senha **nunca** é aceita como argumento. Só existe um administrador; não há cadastro nem recuperação por e-mail.

## Primeiro acesso (túnel SSH)
No computador: `ssh -L 8081:127.0.0.1:8081 naibed@IP_DO_SERVIDOR` e abra `http://127.0.0.1:8081`.

## Atalhos
Configurados no `.env` do servidor (sem editor no navegador); depois `scripts/docker.sh compose up -d --force-recreate`.
- `PAINEL_JELLYFIN_URL=` deve ser um endereço que abra **no dispositivo do visitante** (ex.: o `.onion` do Jellyfin). `localhost`, `127.x`, `::1`, `0.0.0.0`, `127.1` etc. são rejeitados. Vazio → card "Endereço não configurado", sem link.
- Extras: `PAINEL_ATALHOS='[{"titulo":"Wiki","descricao":"Notas","url":"https://exemplo.onion/"}]'`. Só `http`/`https`, sem usuário/senha embutidos.
- Nome do painel: `PAINEL_NOME`.

## Cookies e HTTPS
`SESSION_SECURE_COOKIE=false` por padrão: túnel SSH e onion são HTTP, e um cookie `Secure` não seria reenviado, quebrando o login. Cookie é sempre `HttpOnly` e `SameSite=Lax`. Só ative `true` com HTTPS de ponta a ponta. Não há HSTS.

## Backup
`scripts/backup.sh` gera em `/var/lib/painel-pessoal/backups`: `banco-*.sqlite` (via `VACUUM INTO`, consistente com o painel em uso e com `integrity_check`), `arquivos-*.tar.gz` (storage/app) e `env-*.bak` (contém a APP_KEY). Copie também para fora do servidor.

## Restauração
`scripts/restaurar.sh /var/lib/painel-pessoal/backups/banco-AAAAMMDD-HHMMSS.sqlite` (para o painel, guarda o banco atual como `.antes-da-restauracao-*`, remove `-wal/-shm`, troca, migra e sobe). Para restaurar o `.env`, copie o `env-*.bak` para `.env` (modo 600).

## Atualização
Traga o código novo, mantenha `composer.lock` e rode `scripts/atualizar.sh` (backup → build → migrações → subir). Nada atualiza sozinho. Para atualizar dependências de propósito: `scripts/gerar-lock.sh atualizar`.

**Fixar versões:** após o primeiro build, obtenha os digests (`docker image inspect dunglas/frankenphp:1-php8.4-bookworm --format '{{index .RepoDigests 0}}'`, idem `composer:2`) e coloque-os nos `ARG` do `Dockerfile`. A tag exata do FrankenPHP não pôde ser confirmada daqui.

## Logs
Contêiner: `json-file`, 10 MB × 3 arquivos. Laravel: um arquivo por dia em `storage/logs`, últimos 14 dias (`LOG_DAILY_DAYS`). Isso **não** limita cache, sessões, banco nem o disco inteiro.

## Etapa posterior: acesso onion (não feita)
Só depois de login funcionando e administrador criado. Inspecione o `torrc` real (`sudo grep -n HiddenService /etc/tor/torrc`), preserve o onion existente e **acrescente** um bloco separado, com diretório próprio, apontando `HiddenServicePort 80 127.0.0.1:8081`. Não sobrescreva o arquivo; recarregue o Tor e leia o novo `hostname`. Depois coloque esse endereço em `APP_URL`.

## Testes e limitações
| Item | Situação |
|---|---|
| Testes PHPUnit (login, rejeição, limitação, logout, sem cadastro, rotas protegidas, atalhos, escape, cabeçalhos, cookies, comando admin) | Escritos, **não executados** (sem PHP aqui) |
| Persistência após recriar contêiner | `scripts/testar-persistencia.sh`, **não executado** |
| Compose válido, app iniciando, porta só em loopback, arquivos internos 404, serviços existentes intactos | `scripts/verificar.sh`, **não executado** |
| `composer.lock` | **Ausente**: gere com `scripts/gerar-lock.sh` (sem rede aqui) |
| Visual desktop/celular | CSS original preservado; não conferido em navegador real |
| Regeneração da sessão no login | Feita no código (`session()->regenerate()`); sem teste dedicado |
| Limite por e-mail pode bloquear o próprio administrador por 15 min sob ataque (escolha consciente por causa do IP único do Tor) | Limitação conhecida |
