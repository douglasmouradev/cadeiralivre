# Checklist VPS (aaPanel)

## 404 em `/agendar/{slug}` (só `demo-barbearia` funciona)

O Nginx está a responder antes do PHP. Corrija nesta ordem:

### 1. Pasta de execução

aaPanel → **Website** → site → **Site directory** → **Run directory** = `public`

### 2. Remover pastas físicas conflituosas

No terminal da VPS:

```bash
cd /www/wwwroot/cadeiralivre.tdesksolutions.com.br
rm -rf public/agendar public/cliente
```

Se existir `public/agendar/demo-barbearia/`, o Nginx serve só esse slug e devolve 404 aos restantes.

### 3. Rewrite para `index.php`

aaPanel → **Website** → **Config** → confirme:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

Ver exemplo completo em `deploy/aapanel-nginx.conf`.

Reinicie o Nginx no aaPanel.

### 4. Testar

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://cadeiralivre.tdesksolutions.com.br/agendar/teste-slug
```

Deve devolver **404 com HTML da app** (“Barbearia não encontrada”), **não** a página genérica “nginx”.

### 5. Criar a loja Adriele

```bash
cd /www/wwwroot/cadeiralivre.tdesksolutions.com.br
git pull origin main
php scripts/migrate.php
php scripts/create_adriele_store.php
```

URL: https://cadeiralivre.tdesksolutions.com.br/agendar/adriele-cardoso-nail-design
