# AI Runner — visão geral do sistema

Aplicação web de treinos de corrida personalizados. Usuário se cadastra,
escolhe um nível, recebe um plano semanal de treinos e pode assinar um plano
pago via Mercado Pago.

## Arquitetura

- **Front-end**: HTML + CSS (Tailwind via CDN) + JavaScript puro, sem
  framework nem build tool. Páginas em `views/*.html`, scripts em `js/*.js`.
  Precisam ficar sob a raiz do projeto (não numa subpasta tipo `public/`)
  porque o deploy da Hostinger serve o repositório clonado diretamente, sem
  opção de apontar para uma subpasta como document root — por isso as URLs
  públicas das páginas são `/views/login.html`, `/views/dashboard.html` etc.
- **Back-end**: PHP puro (sem framework), funcionando como API JSON em `api/`.
  Front e back se comunicam via `fetch()`.
- **Banco de dados**: MySQL/MariaDB (schema em `database.sql`).
- **Sessão**: autenticação via `$_SESSION` do PHP + cookie, checada em cada
  chamada de API.
- **Pagamentos**: integração com Mercado Pago (Checkout Pro) via
  `includes/mercadopago.php`.

## Fluxo do usuário

1. **Landing page** (`views/index.html`) — apresenta o produto, com CTAs
   para criar conta ou entrar.
2. **Cadastro** (`views/cadastro.html` → `api/auth/cadastro.php`) — nome,
   e-mail, WhatsApp e senha. Cria o usuário com **7 dias de teste grátis**
   (`trial_ends_at`) e já inicia a sessão.
3. **Onboarding** (`views/onboarding.html` → `api/onboarding.php`) — usuário
   escolhe seu **nível** (iniciante/intermediário/avançado). O sistema
   atribui automaticamente a **primeira turma daquele nível em ordem
   alfabética** (tabela `turmas`) e monta um **plano semanal de 7 dias**
   (segunda a domingo da semana atual) a partir do catálogo de `treinos_ia`
   do nível escolhido — pega os treinos mais recentes, um por dia, repetindo
   o catálogo se houver menos de 7 cadastrados — e associa ao usuário em
   `user_treinos_ia` (com `date` e status `pending`).
4. **Dashboard** (`views/dashboard.html` → `api/dashboard.php`) — tela
   principal, mostra:
   - Saudação com o primeiro nome do usuário.
   - Aviso de dias restantes de teste grátis (se ainda estiver no trial).
   - Bloqueio de acesso (com CTA para assinar) se o trial acabou e não há
     assinatura ativa.
   - O treino do dia atual: nome e conteúdo (texto livre, formatação
     preservada) do `treinos_ia` do dia, com botão para enviar feedback.
   - A lista dos 7 treinos da semana com status (pendente/realizado/parcial/
     não realizado).
5. **Feedback do treino** (`api/treinos.php`) — ao concluir o treino do dia,
   o atleta envia status (`done`/`partial`/`not_done`), PSE (percepção de
   esforço, 1 a 10) e observações livres. Valida que o registro pertence ao
   usuário logado (evita que um usuário marque treino de outro).
6. **Assinatura** (`views/pagamento.html` → `api/pagamento.php`) — cria uma
   preferência de pagamento no Mercado Pago (Checkout Pro) e redireciona o
   usuário para a página de pagamento deles.
7. **Retorno do pagamento** (`views/pagamento_retorno.html` →
   `api/pagamento_retorno.php`) — página de retorno do Mercado Pago, mostra
   mensagem conforme o status (`approved`, `pending`, `failure`). O status
   real da assinatura só é confirmado pelo webhook, não por esse parâmetro
   de URL (que pode ser manipulado pelo usuário).
8. **Webhook do Mercado Pago** (`webhook.php`) — endpoint chamado pelo
   Mercado Pago quando um pagamento muda de status. Consulta o pagamento na
   API deles e, se `approved`, marca `subscription_status = 'active'` para o
   usuário (`external_reference` = id do usuário).
9. **Logout** (`api/auth/logout.php`) — destrói a sessão.

## Área do treinador (coaching)

- **Login unificado** (`views/login.html` → `api/auth/login.php`) — a mesma
  tela de login serve atletas e treinadores. O back-end tenta autenticar
  primeiro contra `users` (atleta) e depois contra `coaches` (treinador),
  respondendo `role: 'athlete'` ou `role: 'coach'`. O front redireciona para
  `/views/dashboard.html` ou `/views/admin/coaching.html` conforme o papel.
  Treinadores ficam na tabela `coaches` (nome, e-mail, senha com
  `password_hash`), sessão em `$_SESSION['coach_id']`
  (`includes/admin_auth.php` → `require_admin()`).
- **Atletas** (`action=overview`) — a aba "Atletas" do dashboard mostra uma
  **lista única com todos os atletas** (`allAthletes` na resposta: nome,
  e-mail, WhatsApp, nome da turma — "Sem turma" quando não vinculado —,
  status de acesso/assinatura), sem agrupar por turma; agrupar por turma
  continua existindo só na aba "Turmas" (`classes` na resposta, usado por
  `renderClassCards`). Traz também `recentAthletes`: os 10 atletas mais
  recentes por `created_at`, exibidos no topo da aba "Atletas" para o
  treinador perceber rapidamente quando chegam novos cadastros (essa seção
  some enquanto há uma busca ativa).
- **Detalhe do atleta** (`action=athlete&user_id=`) — dados do atleta e
  histórico dos últimos 30 treinos com status, PSE e observações enviadas
  pelo atleta. Permite mover o atleta para outra turma (`action=move_athlete`).
- **Personalizar treino do atleta** (`action=update_user_treino`) — no
  histórico do atleta, o treinador pode editar nome/conteúdo do treino de um
  dia específico. A edição fica em `user_treinos_ia.nome_personalizado` /
  `conteudo_personalizado` (colunas nullable) e vale **só para aquele
  atleta e aquele dia** — o treino_ia original do catálogo compartilhado
  não é alterado, então outros atletas que usam o mesmo treino continuam
  vendo a versão original. As queries em `api/dashboard.php` e
  `api/admin/coaching.php` usam `COALESCE(personalizado, original)` para
  decidir o que exibir. Enviar nome/conteúdo vazios remove a personalização
  e volta a mostrar o treino do catálogo.
- **Gestão de turmas** (`action=save_class`, `action=delete_class`) — CRUD de
  turmas. Excluir uma turma não apaga os atletas: `users.class_id` vira NULL
  (`ON DELETE SET NULL`).
- **Treino com IA** (`action=treinos_ia`, `action=treino_ia`,
  `action=save_treino_ia`, `action=delete_treino_ia`) — CRUD de treinos em
  formato de texto livre (tabela `treinos_ia`: nome, tipo/nível, conteúdo —
  **sem** dia da semana, é um catálogo por nível), pensado para receber
  treinos gerados por IA no futuro. O campo `conteudo` preserva a formatação
  exata (quebras de linha) digitada, sem parsing estruturado — front-end usa
  `<textarea>` na edição e `<pre>` na exibição. **É a fonte do plano de
  treino do atleta**: o dia da semana é uma propriedade da atribuição
  (`user_treinos_ia.date`), não do treino em si — ver
  `atribuir_plano_semanal_ao_usuario()`.
- **Busca** — as três listagens do dashboard de coaching (Atletas, Turmas,
  Treino com IA) têm um campo de busca client-side (filtragem em JS sobre
  os dados já carregados, sem chamada extra à API): atletas por
  nome/e-mail/WhatsApp, turmas por nome/descrição, treinos por nome. A
  busca de atletas ignora acentuação/caixa (`normalize()` em
  `js/admin_coaching.js`) e esconde a seção "Atletas recentes" enquanto
  há um termo digitado.

## Regras de negócio importantes

- **Acesso liberado** (`usuario_tem_acesso()` em `includes/functions.php`) se
  a assinatura está `active` **ou** se o usuário ainda está dentro do período
  de teste de 7 dias (`trial_ends_at > now()`).
- **Plano semanal** vem de `treinos_ia` (catálogo por nível), cadastrado
  pelo treinador na aba "Treino com IA" do dashboard de coaching — pensado
  para no futuro ser gerado dinamicamente por IA sem mudar o schema. Não há
  mais importação de treinos via CSV (tela e endpoint removidos).
- **Geração automática da semana** (`garantir_plano_semanal_atual()` em
  `includes/functions.php`) — não há cron job; a cada chamada de
  `api/dashboard.php`, o sistema verifica se a semana atual (segunda a
  domingo) do atleta já tem registros em `user_treinos_ia`. Se não tiver
  (nova semana começou, ou é o primeiro acesso), gera automaticamente a
  partir do nível da turma do atleta, chamando
  `atribuir_plano_semanal_ao_usuario()`. Atleta sem turma não gera nada.
  Isso garante que o atleta sempre tenha o plano da semana corrente pronto,
  mesmo sem ninguém acessar o sistema entre uma semana e outra.

## Estrutura de pastas

Tudo fica sob a raiz do repositório (a Hostinger publica a raiz do repo
clonado, sem opção de apontar para uma subpasta como document root), com o
front-end organizado em `views/`:

```
views/                → páginas HTML do front-end estático
  *.html              → uma página por tela (ex: /views/login.html)
js/*.js               → lógica de cada página (fetch para a API, manipulação de DOM)

api/                  → back-end, só responde JSON, nunca HTML
  _bootstrap.php      → helper comum (sessão, header JSON, json_response())
  auth/               → login, cadastro, logout, "quem sou eu"
  dashboard.php       → dados do dashboard (treino do dia + semana)
  classes.php         → lista as turmas disponíveis
  onboarding.php      → atribuição de turma + plano semanal por nível
  treinos.php         → registrar feedback do treino (status, PSE, observações)
  pagamento.php       → criar preferência de pagamento (Mercado Pago)
  pagamento_retorno.php → mensagem de retorno do pagamento
  admin/coaching.php  → dashboard do treinador (atletas, turmas, treinos IA)

includes/             → lógica compartilhada usada pela API (bloqueado via .htaccess)
  auth.php            → require_login(), current_user()
  functions.php       → helpers de negócio (acesso, trial, plano semanal, h())
  mercadopago.php     → integração com a API do Mercado Pago

config/database.php   → conexão PDO + leitura de variáveis de ambiente (.env) (bloqueado via .htaccess)

webhook.php            → recebe notificações de pagamento do Mercado Pago
database.sql           → schema do banco (users, turmas, coaches, treinos_ia,
                          user_treinos_ia)
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
  se não autenticado, redireciona para `/views/login.html`.
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
