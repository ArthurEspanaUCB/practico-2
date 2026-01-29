# Guía de Configuración - CD Staging + Production (Debian)

Esta guía detalla **paso a paso** cómo configurar tu servidor Debian y GitHub para el pipeline de CD.

---

## 🖥️ PARTE 1: PREPARAR TU SERVIDOR DEBIAN

### Paso 1.1: Conectarte a tu servidor

```bash
# Desde tu PC con Windows (PowerShell o terminal)
ssh root@TU_IP_DEL_SERVIDOR
# Ejemplo: ssh root@192.168.1.100
```

> 💡 **¿Dónde encuentro la IP?** En el panel de tu proveedor (DigitalOcean, AWS, Linode, etc.) o pregunta a tu administrador.

### Paso 1.2: Instalar dependencias necesarias

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar PHP 8.2 y extensiones requeridas
sudo apt install -y software-properties-common
sudo apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-common \
    php8.2-pgsql php8.2-mbstring php8.2-xml php8.2-curl \
    php8.2-zip php8.2-bcmath php8.2-intl php8.2-readline

# Instalar Nginx
sudo apt install -y nginx

# Instalar PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Instalar herramientas adicionales
sudo apt install -y git curl rsync acl
```

### Paso 1.3: Crear usuario de deploy

```bash
# Crear usuario 'deploy' para los despliegues
sudo adduser deploy --disabled-password --gecos ""

# Agregar al grupo www-data (para permisos web)
sudo usermod -aG www-data deploy

# Verificar que se creó
id deploy
# Debe mostrar: uid=1001(deploy) gid=1001(deploy) groups=1001(deploy),33(www-data)
```

### Paso 1.4: Crear estructura de directorios

```bash
# Crear directorios del proyecto
sudo mkdir -p /var/www/comprasventas/releases
sudo mkdir -p /var/www/comprasventas/shared
sudo mkdir -p /var/www/comprasventas/shared/storage/logs
sudo mkdir -p /var/www/comprasventas/shared/storage/framework/cache
sudo mkdir -p /var/www/comprasventas/shared/storage/framework/sessions
sudo mkdir -p /var/www/comprasventas/shared/storage/framework/views

# Asignar permisos al usuario deploy
sudo chown -R deploy:www-data /var/www/comprasventas
sudo chmod -R 775 /var/www/comprasventas

# Verificar
ls -la /var/www/comprasventas/
```

### Paso 1.5: Configurar PostgreSQL

```bash
# Entrar a PostgreSQL como superusuario
sudo -u postgres psql

# Dentro de PostgreSQL, ejecutar:
CREATE USER comprasventas_user WITH PASSWORD 'TuPasswordSeguro123';
CREATE DATABASE comprasventas_db OWNER comprasventas_user;
GRANT ALL PRIVILEGES ON DATABASE comprasventas_db TO comprasventas_user;
\q

# Probar conexión
psql -h localhost -U comprasventas_user -d comprasventas_db
# Ingresa la contraseña cuando te la pida, luego escribe \q para salir
```

> 📝 **GUARDA ESTOS DATOS** - Los necesitarás para el .env:
>
> - `DB_DATABASE=sistema_ventadb`
> - `DB_USERNAME=comprasventas_user`
> - `DB_PASSWORD=Password123`

### Paso 1.6: Crear archivo .env

```bash
# Crear el archivo .env en shared
sudo nano /var/www/comprasventas/shared/.env
```

**Pega este contenido (modifica los valores):**

```env
APP_NAME=ComprasVentas
APP_ENV=staging
APP_KEY=base64:Ubl0o5AlcDWqmWWdLP+UDn3io46usSCMP/uYxKePrZA=
APP_DEBUG=false
APP_URL=http://192.168.0.10

RELEASE_VERSION=dev

LOG_CHANNEL=daily
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sistema_ventadb
DB_USERNAME=comprasventas_user
DB_PASSWORD=Password123

SESSION_DRIVER=file
SESSION_LIFETIME=120

CACHE_STORE=file
QUEUE_CONNECTION=sync
```

```bash
# Guardar: Ctrl+O, Enter, Ctrl+X

# Generar APP_KEY (temporal, ejecutar en tu PC local donde tengas Laravel)
# php artisan key:generate --show
# Copiar el resultado (base64:xxxxx) y pegarlo en APP_KEY del .env

# Asignar permisos correctos
sudo chown deploy:www-data /var/www/comprasventas/shared/.env
sudo chmod 640 /var/www/comprasventas/shared/.env
```

### Paso 1.7: Configurar Nginx

```bash
# Crear configuración del sitio
sudo nano /etc/nginx/sites-available/comprasventas
```

**Pega este contenido:**

```nginx
server {
    listen 80;
    server_name 192.168.0.10;
    root /var/www/comprasventas/current/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Guardar y activar el sitio
sudo ln -s /etc/nginx/sites-available/comprasventas /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # Eliminar sitio por defecto

# Verificar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl restart nginx
sudo systemctl enable nginx
```

---

## 🔑 PARTE 2: CONFIGURAR SSH KEYS (MUY IMPORTANTE)

### Paso 2.1: Generar SSH Key EN TU PC LOCAL (Windows)

Abre **PowerShell** en tu PC (NO en el servidor):

```powershell
# Crear directorio .ssh si no existe
mkdir -Force $HOME\.ssh

# Generar par de claves
ssh-keygen -t ed25519 -C "github-actions-deploy" -f $HOME\.ssh\deploy_key

# Cuando pregunte passphrase, déjala VACÍA (solo presiona Enter 2 veces)
```

### Paso 2.2: Ver y copiar las claves generadas

```powershell
# Ver la CLAVE PÚBLICA (esta va al servidor)
cat $HOME\.ssh\deploy_key.pub

# Copia TODO el contenido que aparece (empieza con ssh-ed25519...)
```

### Paso 2.3: Agregar clave pública AL SERVIDOR

```bash
# En tu SERVIDOR Debian, como usuario deploy:
sudo su - deploy

# Crear directorio .ssh
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Crear archivo authorized_keys y pegar la clave pública
nano ~/.ssh/authorized_keys
# Pega la clave pública que copiaste (ssh-ed25519 AAAA... github-actions-deploy)
# Guardar: Ctrl+O, Enter, Ctrl+X

chmod 600 ~/.ssh/authorized_keys
exit  # Volver a root
```

### Paso 2.4: Probar conexión SSH desde tu PC

```powershell
# En tu PC Windows, probar que funciona:
ssh -i $HOME\.ssh\deploy_key deploy@TU_IP_DEL_SERVIDOR

# Si conecta correctamente, escribe 'exit' para salir
```

### Paso 2.5: Obtener la CLAVE PRIVADA para GitHub

```powershell
# En tu PC Windows, ver la clave PRIVADA:
cat $HOME\.ssh\deploy_key

# Copia TODO el contenido, incluyendo:
# -----BEGIN OPENSSH PRIVATE KEY-----
# ... (todo el contenido) ...
# -----END OPENSSH PRIVATE KEY-----
```

> ⚠️ **IMPORTANTE**: Esta clave privada es SECRETA. Solo va en los secrets de GitHub, NUNCA la compartas ni la subas al repositorio.

---

## 🔐 PARTE 3: CONFIGURAR GITHUB SECRETS Y ENVIRONMENTS

### Paso 3.1: Ir a tu repositorio en GitHub

1. Ve a: `https://github.com/ArthurEspanaUCB/practico-2`
2. Click en **Settings** (pestaña arriba)
3. En el menú izquierdo, click en **Environments**

### Paso 3.2: Crear Environment "staging"

1. Click **New environment**
2. Nombre: `staging`
3. Click **Configure environment**
4. En **Environment secrets**, click **Add secret** para cada uno:

| Nombre del Secret | Valor que debes poner                                                    |
| ----------------- | ------------------------------------------------------------------------ |
| `STAGING_HOST`    | La IP de tu servidor (ej: `192.168.1.100`)                               |
| `STAGING_USER`    | `deploy`                                                                 |
| `STAGING_SSH_KEY` | El contenido COMPLETO de `deploy_key` (la clave privada con BEGIN y END) |
| `STAGING_APP_URL` | `http://TU_IP_DEL_SERVIDOR` (ej: `http://192.168.1.100`)                 |

### Paso 3.3: Crear Environment "production"

1. Volver a **Environments** → **New environment**
2. Nombre: `production`
3. Click **Configure environment**
4. ✅ Marcar **Required reviewers** → Agregar tu usuario de GitHub
5. En **Environment secrets**, agregar:

| Nombre del Secret | Valor                                                 |
| ----------------- | ----------------------------------------------------- |
| `PROD_HOST`       | IP del servidor de producción (puede ser la misma IP) |
| `PROD_USER`       | `deploy`                                              |
| `PROD_SSH_KEY`    | El contenido COMPLETO de la clave privada             |
| `PROD_APP_URL`    | `http://TU_IP_PRODUCCION`                             |

> 💡 **Nota**: Si solo tienes UN servidor, puedes usar la misma IP para staging y production. En un caso real, serían servidores diferentes.

---

## ✅ PARTE 4: VERIFICAR QUE TODO ESTÁ LISTO

### Lista de verificación en el SERVIDOR:

```bash
# Ejecutar estos comandos en tu servidor para verificar:

# 1. Verificar PHP
php -v
# Debe mostrar PHP 8.2.x

# 2. Verificar Nginx
sudo systemctl status nginx
# Debe estar "active (running)"

# 3. Verificar PostgreSQL
sudo systemctl status postgresql
# Debe estar "active (running)"

# 4. Verificar estructura de directorios
ls -la /var/www/comprasventas/
# Debe mostrar: releases/, shared/

# 5. Verificar .env existe
cat /var/www/comprasventas/shared/.env
# Debe mostrar el contenido del .env

# 6. Verificar permisos del usuario deploy
sudo -u deploy ls -la /var/www/comprasventas/
# NO debe dar error de permisos
```

### Lista de verificación en GITHUB:

- [ ] Environment `staging` creado con 4 secrets
- [ ] Environment `production` creado con 4 secrets + reviewer
- [ ] Archivo `.github/workflows/cd-staging-prod.yml` existe en el repo

---

## 🚀 PARTE 5: PROBAR EL PIPELINE

Una vez configurado todo:

```bash
# En tu PC local, hacer un commit y push
git add .
git commit -m "ci: test CD pipeline"
git push origin main
```

Ve a tu repositorio en GitHub → **Actions** y verás el pipeline ejecutándose.

---

## 4. Flujo del Pipeline

```
┌─────────────────────────────────────────────────────────────────┐
│                        PUSH A MAIN                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  BUILD JOB                                                       │
│  - Instalar dependencias PHP/NPM                                │
│  - Construir assets frontend                                    │
│  - Generar RELEASE_ID                                           │
│  - Subir artefacto único                                        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  DEPLOY STAGING (automático)                                     │
│  - Descargar artefacto                                          │
│  - rsync a servidor staging                                     │
│  - Ejecutar migraciones                                         │
│  - Switch symlink atómico                                       │
│  - Health check (/api/health)                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
            ┌─────────────────┴─────────────────┐
            │                                   │
            ▼                                   ▼
┌───────────────────────┐         ┌───────────────────────────────┐
│  ✅ SUCCESS           │         │  ❌ FAILURE                    │
│  (continúa pipeline)  │         │  → ROLLBACK STAGING           │
└───────────────────────┘         │  → Restaurar release anterior │
                                  └───────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      PUSH TAG v*                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  DEPLOY PRODUCTION (requiere aprobación)                         │
│  ⏸️ PAUSA - Esperando aprobación de Release Manager             │
│  - Una vez aprobado: mismo proceso que staging                  │
│  - Health check obligatorio                                     │
└─────────────────────────────────────────────────────────────────┘
```

---

## 5. Comandos Útiles

### Crear tag para deploy a producción

```bash
git tag -a v2.0.0 -m "release: CD environments + atomic deploy + health gates"
git push origin v2.0.0
```

### Verificar symlink actual en servidor

```bash
ssh deploy@staging.example.com "readlink -f /var/www/comprasventas/current"
```

### Listar releases disponibles

```bash
ssh deploy@staging.example.com "ls -la /var/www/comprasventas/releases/"
```

### Rollback manual

```bash
ssh deploy@staging.example.com "ln -sfn /var/www/comprasventas/releases/RELEASE_ID /var/www/comprasventas/current"
```

### Health check manual

```bash
curl -s https://staging.example.com/api/health | jq
```

---

## 6. Checklist Pre-Deploy

- [ ] Secrets configurados en GitHub (staging y production)
- [ ] Servidores con estructura de directorios creada
- [ ] Usuario de deploy con acceso SSH configurado
- [ ] Archivo `.env` en `/var/www/comprasventas/shared/` configurado
- [ ] Base de datos PostgreSQL accesible
- [ ] Nginx/Apache configurado apuntando a `/var/www/comprasventas/current/public`
- [ ] PHP 8.2+ instalado con extensiones requeridas
- [ ] Aprobadores configurados para environment `production`

---

## 7. Troubleshooting

### Error: "Permission denied" en SSH

```bash
# En el servidor, verificar permisos del directorio .ssh
chmod 700 ~/.ssh
chmod 600 ~/.ssh/authorized_keys
```

### Error: Health check falla post-deploy

```bash
# Verificar logs de la aplicación
tail -f /var/www/comprasventas/current/storage/logs/laravel.log

# Verificar que el symlink apunta correctamente
ls -la /var/www/comprasventas/current
```

### Error: Migraciones fallan

```bash
# Ejecutar manualmente para ver error detallado
cd /var/www/comprasventas/current
php artisan migrate --force --pretend
```
