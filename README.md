<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP"/>
  <img src="https://img.shields.io/badge/MySQL-8-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL"/>
  <img src="https://img.shields.io/badge/SaaS-Multi--tenant-0ea5e9?style=flat-square" alt="SaaS"/>
  <img src="https://img.shields.io/badge/Docker-2496ED?style=flat-square&logo=docker&logoColor=white" alt="Docker"/>
</p>

<h1 align="center">CadeiraLivre</h1>

<p align="center">
  <strong>SaaS multi-tenant de agendamento para barbearias</strong> — agenda por slug, portal do cliente, planos, Stripe, superadmin e páginas LGPD.
</p>

<p align="center">
  <a href="https://portifolio-douglas-moura.vercel.app">Portfólio</a> ·
  <a href="https://github.com/douglasmouradev">GitHub</a> ·
  <a href="https://wa.me/5571997087082?text=Ol%C3%A1%20Douglas%2C%20tenho%20interesse%20no%20CadeiraLivre.">Solicitar implantação</a>
</p>

<p align="center">
  <img src="https://img.shields.io/github/actions/workflow/status/douglasmouradev/cadeiralivre/ci.yml?branch=main&label=CI&style=flat-square" alt="CI"/>
</p>

---

# CadeiraLivre

SaaS de agendamento para barbearias (**CadeiraLivre**) em **PHP 8.3**, **MySQL 8**, **MVC próprio** (sem framework), front **HTML/CSS/JS vanilla**.

## Destaques

- MVC próprio, **CSRF**, rate limit no login e **PDO**
- **Docker**, **GitHub Actions** e **PHPUnit**
- Superadmin da plataforma, **Stripe** e conformidade **LGPD**
- Front **HTML/CSS/JS** vanilla

## Requisitos

- PHP 8.3+ (extensões: `pdo_mysql`, `json`, `mbstring`, `fileinfo`, `openssl`)
- MySQL 8.0+
- Composer 2
- Apache com `mod_rewrite` **ou** Nginx com rewrite para `public/index.php`

## Instalação

1. **Clone / copie** o diretório `cadeira-livre` para o servidor.

2. **Instale dependências**

   ```bash
   cd cadeira-livre
   composer install --no-dev --optimize-autoloader
   ```

3. **Configure o ambiente**

   ```bash
   cp .env.example .env
   ```

   Edite `.env` com host, banco, usuário, senha MySQL e URL da aplicação (`APP_URL`, por exemplo `http://localhost/cadeira-livre/public`).

4. **Crie o banco vazio**

   ```sql
   CREATE DATABASE cadeira_livre_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Execute as migrations**

   ```bash
   php scripts/migrate.php
   ```

   O script aplica, em ordem, todos os arquivos em `database/migrations/*.sql`.

6. **(Opcional) Dados de demonstração**

   O script detecta se o demo já foi importado (tenant `demo-barbearia`) e **não repete** o import.

   Opção B — linha de comando (troque `root` e o nome do banco pelos valores do seu `.env`):

   ```bash
   mysql -h 127.0.0.1 -P 3306 -u root -p cadeira_livre_saas < database/seeds/001_demo.sql
   ```

   O `-p` sozinho pede a senha; **não** use a palavra `USUARIO` — isso era só exemplo no texto antigo.

7. **Permissões de upload**

   ```bash
   mkdir -p storage/uploads/logos storage/uploads/avatars
   chmod -R ug+rwX storage
   ```

8. **Document root**

   Aponte o virtual host para a pasta **`public/`** (não a raiz do projeto).

   Apache (exemplo): `DocumentRoot /var/www/cadeira-livre/public`

### Servidor embutido do PHP (só desenvolvimento)

```bash
php -S localhost:8000 -t public public/router.php
```

Defina `APP_URL=http://localhost:8000` no `.env`. O ficheiro `public/router.php` encaminha pedidos para `index.php` como o Apache faria com rewrite.

### Docker (Nginx + PHP-FPM + MySQL)

```bash
docker compose up -d
# após o MySQL aceitar ligações:
docker compose exec app sh -c "composer install && php scripts/migrate.php"
```

Aplicação: `http://localhost:8080` (ajuste `APP_URL` no `docker-compose.yml` se mudar a porta). No `.env` use `DB_HOST=db` e a mesma base/credenciais que o serviço `db` do Compose.

### Superadmin da plataforma

Utilizador com papel `superadmin` acessa `/saas` (dashboard, lojas, planos, billing e impersonação). Criação:

```bash
php scripts/create_superadmin.php admin@seu-dominio.com 'SenhaSegura8' 'Nome'
```

### Planos, limites e Stripe

- Tabela `plan_definitions` define limites (barbeiros, agendamentos/mês) e preços de referência.
- Novos tenants recebem plano `free` em trial (`subscription_status = trialing`).
- Após o trial, é necessário `subscription_status` `active` ou `trialing` (ex. via Stripe) para continuar a operar.
- Configure `STRIPE_SECRET_KEY` e `STRIPE_WEBHOOK_SECRET`; endpoint: `POST /webhooks/stripe`. Associe `stripe_price_id` em `plan_definitions` aos preços criados no Stripe e inclua `metadata[tenant_id]` na subscrição/checkout.

### Fila de e-mail

Com `MAIL_QUEUE=true`, os envios gravam-se em `outbound_emails`. Processe com cron:

```bash
php scripts/process_mail_queue.php
```

### Backup MySQL (exemplo)

```bash
chmod +x scripts/backup_mysql.sh
./scripts/backup_mysql.sh
```

Gera `storage/backups/mysql_*.sql.gz`.

### Testes e CI

```bash
composer install
vendor/bin/phpunit
```

O workflow GitHub Actions (`.github/workflows/ci.yml`) corre migrations e PHPUnit.

## Acesso demo (após seed)

- Painel: `APP_URL` → login **owner@demo.local** / **Senha1234**
- URL pública de agendamento: `/agendar/demo-barbearia`
- Barbeiros (mesma senha **Senha1234**): `barber1@demo.local`, `barber2@demo.local`, `barber3@demo.local` — após o login abrem direto a **Agenda** (`/agenda`); não têm acesso ao painel administrativo (`/painel`).
- Recepcionista: `recep@demo.local`
- **Cliente (portal só agendamento):** `carlos@cliente.com` / **Senha1234** — URLs: `/cliente/demo-barbearia/entrar` ou cadastro em `/cliente/demo-barbearia/cadastro` (mesma barbearia do slug). Depois do login, o fluxo de `/agendar/demo-barbearia` usa os dados da conta e não mistura com o login da equipe.

## E-mail

- Se `MAIL_SMTP_USER` estiver vazio no `.env`, o sistema usa `mail()` do PHP (útil em ambiente local).
- Com SMTP preenchido, o envio usa **PHPMailer**.

## Segurança

- PDO com prepared statements em todos os models.
- CSRF em formulários POST sensíveis (login incluído).
- Rate limit de login: 5 tentativas / 15 minutos (sessão).
- Headers de segurança em `public/.htaccess` e CSP básica em `public/index.php`.
- Uploads fora do document root em `storage/uploads/`; logos servidas via `/assets/tenant-logos/{slug}.png` (estático) ou `/loja-logo/{slug}`.

## Estrutura principal

- `app/` — Controllers, Models, Views, Middleware, Services, Helpers
- `config/` — `app.php`, `database.php`, `mail.php`
- `public/` — front controller, assets
- `database/migrations` — SQL numerado
- `database/seeds` — dados de exemplo
- `routes.php` — definição de rotas e middlewares

## Licença

Uso interno / proprietário conforme sua política.
