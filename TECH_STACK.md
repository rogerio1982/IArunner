# Stack técnica — AI Runner

## Front-end

- **HTML** puro — uma página por tela, direto na raiz do repositório
  (`*.html`), sem build tool.
- **CSS**: [Tailwind CSS](https://tailwindcss.com/) via CDN (sem etapa de
  build/compilação).
- **JavaScript**: vanilla JS (sem framework, sem bundler), um arquivo por
  página em `js/*.js`. Comunicação com o back-end via `fetch()` com
  `credentials: 'include'` (cookies de sessão).

## Back-end

- **PHP puro** (sem framework), ≥ 8.1 (produção roda **PHP 8.3.33**).
- API organizada em `api/*.php`, respondendo sempre JSON (nunca HTML).
- Autenticação via `$_SESSION` nativa do PHP + cookie.
- Senhas com `password_hash()` / `password_verify()`; comparação de senha
  de admin com `hash_equals()` (evita timing attack).

## Banco de dados

- **MySQL/MariaDB**.
- Schema principal em `database.sql` (tabelas `users`, `coaches`, `turmas`,
  `treinos_ia`, `user_treinos_ia`).
- Local: qualquer MySQL/MariaDB (ex. XAMPP).
- Produção: banco gerenciado da Hostinger (`u860063057_airunner`).

## Migrations de schema

- **[Phinx](https://phinx.org/)** (`robmorgan/phinx` ^0.16), instalado via
  Composer.
- Config em `phinx.php`, reaproveitando `config/database.php`/`.env` para
  credenciais.
- Migrations em `db/migrations/*.php`.
- Histórico anterior (migrations `.sql` manuais aplicadas via phpMyAdmin)
  preservado só como registro em `migrations/*.sql`.

## Gerenciador de dependências

- **Composer** (PHP) — única dependência de produção hoje é o Phinx.
  `vendor/` não é versionado; instalado direto no servidor (a Hostinger
  também roda `composer install` automaticamente a cada deploy Git).

## Pagamentos

- **Mercado Pago** — Checkout Pro, integrado via chamadas **cURL puras**
  (sem SDK oficial) em `includes/mercadopago.php`:
  - Criação de preferência de pagamento (`/checkout/preferences`).
  - Consulta de status de pagamento (`/v1/payments/{id}`), usada pelo
    webhook para confirmar a assinatura.

## Infraestrutura / Deploy

- **Hostinger** (hPanel) com **deploy automático via Git** — a raiz do
  repositório é publicada diretamente como document root (sem opção de
  apontar para subpasta como `public/`).
- **Apache/LiteSpeed** com `.htaccess`:
  - Define `DirectoryIndex` (prioriza `index.php` sobre qualquer
    `index.html`).
  - Bloqueia acesso HTTP direto a `.env`, `.sql`, `.md`, `.log`,
    `composer.lock`, `composer.json` (regra por extensão).
  - Bloqueia acesso às pastas `includes/`, `config/`, `logs/`, `db/`,
    `vendor/` (`Deny from all`), que ficam na árvore pública por causa do
    document root fixo.
- **Cron job** (hPanel, `*/5 * * * *`) aplicando migrations pendentes do
  Phinx automaticamente após cada deploy:
  ```
  /usr/bin/php .../vendor/bin/phinx migrate -c .../phinx.php
  ```
- **SSH** disponível para administração (porta 65002).

## Logs e observabilidade

- Erros fatais/exceções PHP não tratadas: capturados automaticamente
  (`includes/logger.php`, `registrar_handlers_de_erro()`) e gravados em
  `logs/app.log`.
- Erros JS do navegador (`window.onerror`, promises rejeitadas): capturados
  em `js/common.js`, enviados a `api/log_client_error.php`, caindo no mesmo
  log.
- Visualização dos logs sem acesso ao servidor: aba **"Logs"** no painel do
  treinador (`admin/coaching.html`, `action=view_logs`).

## Ambiente local

- Servidor embutido do PHP:
  ```
  php -S localhost:8000
  ```
- Variáveis de ambiente via `.env` (não versionado; `.env.example` como
  referência): credenciais de banco, credenciais do Mercado Pago, `APP_URL`,
  `ADMIN_PASSWORD`.
- Aviso: o servidor embutido do PHP ignora `.htaccess` — a proteção de
  pastas só vale em produção (Apache/LiteSpeed).

## Resumo em uma linha

PHP puro + MySQL + HTML/JS vanilla + Tailwind (CDN), sem framework nem
build tool, hospedado na Hostinger com deploy Git automático, migrations
via Phinx aplicadas por cron, e pagamentos via Mercado Pago (Checkout Pro)
integrados por cURL direto.
