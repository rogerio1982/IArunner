# Migrations via Phinx

A partir desta introdução, mudanças de schema devem ser feitas como
migrations do [Phinx](https://book.cakephp.org/phinx/0/en/index.html), não
mais como arquivos `.sql` avulsos colados manualmente no phpMyAdmin (ver
histórico em `../migrations/`, mantido só como registro).

## Setup local

```
composer install
vendor/bin/phinx migrate
```

## Criar uma nova migration

```
vendor/bin/phinx create NomeDaMudanca
```

Edita o arquivo gerado em `db/migrations/`, implementa `up()`/`down()`, testa
local (`vendor/bin/phinx migrate` / `vendor/bin/phinx rollback`), e só então
commita e dá push — o cron da Hostinger aplica em produção automaticamente.

## Produção (Hostinger)

Cron configurado no hPanel para rodar a cada deploy/periodicamente:

```
/usr/bin/php /home/u860063057/domains/lemonchiffon-bee-812537.hostingersite.com/public_html/vendor/bin/phinx migrate -c /home/u860063057/domains/lemonchiffon-bee-812537.hostingersite.com/public_html/phinx.php
```

`vendor/` não é versionado (`.gitignore`); é instalado direto no servidor via
`composer install` (feito manualmente uma vez, ou reinstalado após alterar
`composer.json`).

## Baseline

`20260921000000_baseline_schema_atual.php` representa o schema que já
existia em produção antes da introdução do Phinx. Ela foi marcada como já
aplicada em produção (breakpoint), sem rodar de verdade contra o banco
existente — só executa o `up()` real num banco novo/vazio (ex.: setup local
do zero). **Nunca editar essa migration**; toda mudança de schema daqui pra
frente entra como uma migration nova.
