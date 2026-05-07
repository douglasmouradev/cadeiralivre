# CadeiraLivre

SaaS de agendamento para barbearias (**CadeiraLivre**) em **PHP 8.3**, **MySQL 8**, **MVC próprio** (sem framework), front **HTML/CSS/JS vanilla**.

## Requisitos

- PHP 8.3+ (extensões: `pdo_mysql`, `json`, `mbstring`, `fileinfo`, `openssl`)
- MySQL 8.0+
- Composer 2
- Apache com `mod_rewrite` **ou** Nginx com rewrite para `public/index.php`

## Instalação

1. **Clone / copie** o diretório `barbershop-saas` para o servidor.

2. **Instale dependências**

   ```bash
   cd barbershop-saas
   composer install --no-dev --optimize-autoloader
   ```

3. **Configure o ambiente**

   ```bash
   cp .env.example .env
   ```

   Edite `.env` com host, banco, usuário, senha MySQL e URL da aplicação (`APP_URL`, por exemplo `http://localhost/barbershop-saas/public`).

4. **Crie o banco vazio**

   ```sql
   CREATE DATABASE barbershop_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
   mysql -h 127.0.0.1 -P 3306 -u root -p barbershop_saas < database/seeds/001_demo.sql
   ```

   O `-p` sozinho pede a senha; **não** use a palavra `USUARIO` — isso era só exemplo no texto antigo.

7. **Permissões de upload**

   ```bash
   mkdir -p storage/uploads/logos storage/uploads/avatars
   chmod -R ug+rwX storage
   ```

8. **Document root**

   Aponte o virtual host para a pasta **`public/`** (não a raiz do projeto).

   Apache (exemplo): `DocumentRoot /var/www/barbershop-saas/public`

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
- Uploads fora do document root em `storage/uploads/`; logos servidas via `/media/logo/{slug}`.

## Estrutura principal

- `app/` — Controllers, Models, Views, Middleware, Services, Helpers
- `config/` — `app.php`, `database.php`, `mail.php`
- `public/` — front controller, assets
- `database/migrations` — SQL numerado
- `database/seeds` — dados de exemplo
- `routes.php` — definição de rotas e middlewares

## Licença

Uso interno / proprietário conforme sua política.
