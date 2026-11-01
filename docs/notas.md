# Notas pessoais

Aba Notas: criar, editar, excluir com confirmação e listar texto simples em grade.
Título obrigatório (até 120 caracteres), conteúdo obrigatório (até 20.000).
A grade mostra uma prévia; abrir/editar mostra a nota completa.

Ordenação: criação ou última edição, crescente/decrescente, com botão de inversão.
Padrão: última edição, recentes primeiro. Empates usam o ID na mesma direção.
Paginação de 24 notas, preservando a ordenação.
Salvar sem alterações não modifica a última edição. Não há salvamento automático.
Cada nota pertence ao usuário autenticado. Conteúdo escapado; formulários usam CSRF.
A confirmação GET não apaga nada: a remoção depende de DELETE autenticado.

## Atualizar no Debian
Na pasta real do projeto (confira com pwd e git status):
1. Traga o código com git pull --ff-only. Não descarte mudanças locais.
2. Faça backup consistente do SQLite e guarde o .env em local protegido.
3. Execute scripts/testar.sh. Não prossiga se falhar.
4. Execute docker compose build painel.
5. Execute docker compose stop painel.
6. Execute docker compose run --rm painel migrar.
7. Se a migração terminar sem erro: docker compose up -d painel.
8. Abra o painel e confira a aba Notas.

Se build ou testes falharem, o contêiner existente permanece disponível.
Se migração falhar após a parada, mantenha o painel parado para diagnóstico.
A migração cria apenas a tabela notas; não altera usuários ou projetos.
Não use migrate:fresh ou rollback: isso pode apagar dados.

## Validação
NotasTest cobre CRUD, autenticação, isolamento por usuário, validação, escape HTML,
quatro ordenações, datas e paginação.
PHPUnit e build Docker NÃO executados no ambiente de implementação, sem PHP/Composer/Docker.
Validar no Debian antes de considerar a versão pronta.
