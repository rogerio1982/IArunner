# AI Runner — visão geral do sistema

Aplicação web de treinos de corrida personalizados. Usuário se cadastra,
escolhe um nível, recebe um plano semanal de treinos e pode assinar um plano
pago via Mercado Pago.

## Arquitetura

- **Front-end**: HTML + CSS (Tailwind via CDN) + JavaScript puro, sem
  framework nem build tool. Arquivos estáticos na raiz do projeto (`*.html`,
  `js/*.js`) — precisam ficar na raiz porque o deploy da Hostinger serve o
  repositório clonado diretamente, sem opção de apontar para uma subpasta.
- **Back-end**: PHP puro (sem framework), funcionando como API JSON em `api/`.
  Front e back se comunicam via `fetch()`.
- **Banco de dados**: MySQL/MariaDB (schema em `database.sql`).
- **Sessão**: autenticação via `$_SESSION` do PHP + cookie, checada em cada
  chamada de API.
- **Pagamentos**: integração com Mercado Pago (Checkout Pro) via
  `includes/mercadopago.php`.

## Fluxo do usuário

1. **Landing page** (`index.html`) — apresenta o produto, com CTAs
   para criar conta ou entrar.
2. **Cadastro** (`cadastro.html` → `api/auth/cadastro.php`) — nome,
   e-mail, WhatsApp e senha. Cria o usuário com **7 dias de teste grátis**
   (`trial_ends_at`) e já inicia a sessão.
3. **Onboarding** (`onboarding.html` → `api/onboarding.php`) — usuário
   escolhe o nível (iniciante / intermediário / avançado). O sistema monta um
   **plano semanal de 7 dias** (segunda a domingo da semana atual) a partir
   da tabela `workouts` para aquele nível, e associa ao usuário em
   `user_workouts` com status `pending`.
4. **Dashboard** (`dashboard.html` → `api/dashboard.php`) — tela
   principal, mostra:
   - Saudação com o primeiro nome do usuário.
   - Aviso de dias restantes de teste grátis (se ainda estiver no trial).
   - Bloqueio de acesso (com CTA para assinar) se o trial acabou e não há
     assinatura ativa.
   - O treino do dia atual (aquecimento, treino principal, desaquecimento,
     distância/tempo, foco) com botão para marcar como realizado.
   - A lista dos 7 treinos da semana com status (pendente/realizado).
5. **Marcar treino como feito** (`api/treinos.php`) — atualiza o status do
   treino do dia para `done`. Valida que o registro pertence ao usuário
   logado (evita que um usuário marque treino de outro).
6. **Assinatura** (`pagamento.html` → `api/pagamento.php`) — cria uma
   preferência de pagamento no Mercado Pago (Checkout Pro) e redireciona o
   usuário para a página de pagamento deles.
7. **Retorno do pagamento** (`pagamento_retorno.html` →
   `api/pagamento_retorno.php`) — página de retorno do Mercado Pago, mostra
   mensagem conforme o status (`approved`, `pending`, `failure`). O status
   real da assinatura só é confirmado pelo webhook, não por esse parâmetro
   de URL (que pode ser manipulado pelo usuário).
8. **Webhook do Mercado Pago** (`webhook.php`) — endpoint chamado pelo
   Mercado Pago quando um pagamento muda de status. Consulta o pagamento na
   API deles e, se `approved`, marca `subscription_status = 'active'` para o
   usuário (`external_reference` = id do usuário).
9. **Logout** (`api/auth/logout.php`) — destrói a sessão.

## Regras de negócio importantes

- **Acesso liberado** (`usuario_tem_acesso()` em `includes/functions.php`) se
  a assinatura está `active` **ou** se o usuário ainda está dentro do período
  de teste de 7 dias (`trial_ends_at > now()`).
- **Plano semanal** é fixo por nível (não é gerado por IA ainda — há um TODO
  no código para futuramente gerar dinamicamente via OpenAI).
- **Importação de treinos** (`admin/import.html` →
  `api/admin/import.php`) — área administrativa protegida por senha
  (`ADMIN_PASSWORD` no `.env`, verificada com `hash_equals`). Permite subir um
  CSV com o plano de treinos por nível/dia da semana, que é inserido/atualizado
  na tabela `workouts` (`INSERT ... ON DUPLICATE KEY UPDATE`).

## Estrutura de pastas

Tudo fica direto na raiz do repositório (a Hostinger publica a raiz do
repo clonado, sem opção de apontar para uma subpasta como document root):

```
*.html                → uma página por tela (front-end estático)
js/*.js               → lógica de cada página (fetch para a API, manipulação de DOM)
admin/import.html     → tela de importação de treinos (admin)

api/                  → back-end, só responde JSON, nunca HTML
  _bootstrap.php      → helper comum (sessão, header JSON, json_response())
  auth/               → login, cadastro, logout, "quem sou eu"
  dashboard.php       → dados do dashboard (treino do dia + semana)
  onboarding.php      → atribuição do plano semanal por nível
  treinos.php         → marcar treino como concluído
  pagamento.php       → criar preferência de pagamento (Mercado Pago)
  pagamento_retorno.php → mensagem de retorno do pagamento
  admin/import.php    → importação de treinos via CSV

includes/             → lógica compartilhada usada pela API (bloqueado via .htaccess)
  auth.php            → require_login(), current_user()
  functions.php       → helpers de negócio (acesso, trial, plano semanal, h())
  mercadopago.php     → integração com a API do Mercado Pago

config/database.php   → conexão PDO + leitura de variáveis de ambiente (.env) (bloqueado via .htaccess)

webhook.php            → recebe notificações de pagamento do Mercado Pago
database.sql           → schema do banco (tabelas users, workouts, user_workouts)
migration_*.sql        → migrações históricas do schema
.htaccess              → bloqueia acesso HTTP direto a .env/.sql/.md na raiz
includes/.htaccess, config/.htaccess → bloqueiam acesso direto a essas pastas
```

`includes/` e `config/` ficam na mesma árvore pública que o front-end e a
API (por causa do document root fixo da Hostinger), então são protegidas por
`.htaccess` (`Deny from all`) para não expor código-fonte/credenciais via
URL direta. `api/*.php` e `webhook.php` continuam acessíveis normalmente —
são endpoints, não implementação interna.

## Autenticação e segurança

- Sessão PHP (`$_SESSION['user_id']`) com cookie enviado pelo navegador
  (`fetch(..., { credentials: 'include' })` no front).
- Toda página do front que exige login chama `api/auth/me.php` ao carregar;
  se não autenticado, redireciona para `/login.html`.
- Senhas de usuário com `password_hash`/`password_verify`.
- Senha de admin comparada com `hash_equals` (evita timing attack).
- Proteção contra IDOR em `api/treinos.php`: o `UPDATE` sempre filtra por
  `user_id` da sessão, então um usuário não consegue marcar treino de outro
  mesmo manipulando o `user_workout_id`.

## Ambiente e configuração

- Variáveis de ambiente em `.env` (não versionado; `.env.example` como
  referência): credenciais do banco (`DB_HOST`, `DB_NAME`, `DB_USER`,
  `DB_PASS`), credenciais do Mercado Pago (`MP_ACCESS_TOKEN`,
  `MP_PUBLIC_KEY`), `APP_URL`, `ADMIN_PASSWORD`.
- Para rodar localmente: servidor PHP embutido a partir da raiz do projeto
  (nenhum roteador extra necessário, já que tudo está na mesma pasta):
  ```
  php -S localhost:8000
  ```
  Nota: o servidor embutido do PHP ignora `.htaccess`, então em dev local
  `includes/`/`config/` ficam acessíveis por URL — a proteção vale para
  produção (Apache/LiteSpeed da Hostinger).
- Banco de dados: aplicar `database.sql` num MySQL/MariaDB local (ex.: via
  XAMPP) antes de usar o sistema.
