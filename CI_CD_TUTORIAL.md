# Tutorial Completo de CI/CD con GitHub Actions (Laravel + Vue)

Este documento detalla paso a paso cómo implementar un pipeline de Integración Continua (CI) y Despliegue Continuo (CD) profesional para una aplicación Laravel 12 con Vue 3, desplegando en un servidor de producción Debian.

---

## 1. Conceptos Clave

-   **CI (Continuous Integration)**: Ejecuta pruebas automáticas (PHPUnit, Pint, Build Check) cada vez que subes código para asegurar que nada se rompa.
-   **CD (Continuous Deployment)**: Automatiza el proceso de subir los cambios al servidor, construir assets y limpiar cachés sin intervención manual.
-   **Workflow**: Archivo `.yml` en `.github/workflows/` que define los pasos de automatización.

---

## 2. Requisitos Previos

### En tu Servidor (Debian)

1.  **Usuario y Permisos**:
    Debes tener acceso SSH al servidor. Se recomienda crear un par de claves SSH si no tienes uno.

    ```bash
    ssh-keygen -t ed25519 -C "github-actions"
    ```

    Agrega la clave pública (`id_ed25519.pub`) al archivo `~/.ssh/authorized_keys` del servidor.

2.  **Software Instalado**:

    -   Nginx/Apache
    -   PHP 8.2+ con extensiones necesarias
    -   Composer
    -   Git
    -   ACL (para permisos): `sudo apt install acl`

3.  **Directorio del Proyecto**:
    La carpeta `/var/www/sistema-venta` debe existir y tener permisos correctos.
    ```bash
    sudo mkdir -p /var/www/sistema-venta
    sudo chown -R $USER:www-data /var/www/sistema-venta
    ```

### En GitHub (Repositorio)

Debes configurar los **Secrets** para que GitHub Actions pueda entrar a tu servidor.
Ve a: `Settings` -> `Secrets and variables` -> `Actions` -> `New repository secret`.

Agrega las siguientes variables:

| Nombre Secret     | Valor                                                                                             |
| :---------------- | :------------------------------------------------------------------------------------------------ |
| `SSH_HOST`        | La IP pública de tu servidor (ej: `192.168.1.50`)                                                 |
| `SSH_USER`        | El usuario SSH (ej: `helmer` o `ubuntu`)                                                          |
| `SSH_PRIVATE_KEY` | El contenido de tu clave **PRIVADA** generada en el paso anterior.                                |
| `SSH_KNOWN_HOSTS` | (Opcional pero recomendado) La huella digital del servidor para evitar ataques Man-in-the-Middle. |

---

## 3. Estructura de Archivos Creados

### A. Workflow de Integración (CI)

Archivo: `.github/workflows/ci.yml`

**Qué hace:**

1.  **Backend Job**:
    -   Instala PHP 8.2.
    -   Instala dependencias (`composer install`).
    -   Crea una base de datos SQLite temporal en memoria.
    -   Ejecuta `php artisan key:generate`.
    -   Ejecuta pruebas unitarias (`phpunit`).
2.  **Frontend Job**:
    -   Instala Node.js 20.
    -   Instala dependencias (`npm ci`).
    -   Verifica que el proyecto compile (`npm run build`).
    -   Ejecuta pruebas de frontend (`npm run test`).

### B. Workflow de Despliegue (CD)

Archivo: `.github/workflows/cd.yml`

**Qué hace:**

1.  **Construcción (Build)**:
    -   GitHub compila los assets de Vue (JS/CSS) usando `npm run build`.
    -   _Ventaja_: No necesitas instalar Node/NPM en tu servidor de producción, ahorrando recursos y seguridad.
2.  **Transferencia**:
    -   Usa `rsync` para copiar **solo** los archivos necesarios al servidor.
    -   Excluye archivos basura como `.git`, `tests`, `node_modules`.
3.  **Ejecución Remota (SSH)**:
    -   Se conecta al servidor y ejecuta comandos críticos:
        -   `php artisan down`: Pone la app en mantenimiento (segundos).
        -   `composer install --no-dev`: Instala librerías de PHP optimizadas.
        -   `php artisan migrate --force`: Ejecuta cambios en la base de datos automáticamente.
        -   `php artisan config:cache`: Optimiza la configuración.
        -   `php artisan up`: Levanta el sitio nuevamente.

---

## 4. Comandos para Replicar

Para subir estos cambios a tu repositorio, ejecuta los siguientes comandos en tu terminal local:

```bash
# 1. Asegúrate de estar en la rama correcta (main o develop)
git checkout main

# 2. Agrega los nuevos archivos de workflow
git add .github/workflows/ci.yml .github/workflows/cd.yml CI_CD_TUTORIAL.md

# 3. Confirma los cambios con un mensaje descriptivo
git commit -m "feat: Implement CI/CD pipelines for Debian production"

# 4. Sube los cambios a GitHub
git push origin main
```

Una vez hecho esto, ve a la pestaña **Actions** en tu repositorio de GitHub. Verás que el workflow de CI se ejecuta inmediatamente. El de CD se ejecutará (o fallará si faltan los secrets) cuando hagas push a `main`.

---

## 5. Solución de Problemas Comunes

-   **Error: Host key verification failed**:
    -   Asegúrate de haber agregado `SSH_KNOWN_HOSTS` o usa `ssh-keyscan -H TU_IP >> ~/.ssh/known_hosts` en el paso del workflow (ya incluido en mi script).
-   **Error: Permission denied (publickey)**:
    -   Verifica que `SSH_PRIVATE_KEY` sea exacto y que la clave pública esté en `~/.ssh/authorized_keys` del servidor.
-   **Error: Storage permissions**:
    -   El script intenta ejecutar `sudo chmod`. Si tu usuario no tiene sudo sin contraseña, esto fallará.
    -   _Solución_: Configura tu usuario para ejecutar ciertos comandos sin password o ajusta los permisos manualmente una vez y asegúrate que el usuario web (`www-data`) sea dueño de la carpeta.

---

**Autor**: Generado por Asistente de IA bajo supervisión de Helmer.
