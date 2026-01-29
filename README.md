# Sistema de Ventas e Inventario

Sistema web integral para la gestión de ventas, compras, inventario, clientes y proveedores. Desarrollado con **Laravel 11** (API Backend) y **Vue 3** (Frontend), utilizando **PostgreSQL** como base de datos.

## 🚀 Características Principales

-   **Gestión de Productos**: Control de inventario, categorías, alertas de stock bajo y manejo de imágenes.
-   **Ventas**: Punto de venta (POS) con cálculo automático de totales, impuestos y descuentos. Soporte para ventas al contado y crédito.
-   **Compras**: Registro de compras a proveedores para reabastecimiento de stock.
-   **Clientes y Proveedores**: Gestión completa de terceros con historial de crédito.
-   **Seguridad (RBAC)**: Sistema de Roles y Permisos para controlar el acceso a los módulos.
-   **Reportes**: Generación de reportes en PDF para auditoría y control financiero.
-   **API RESTful**: Arquitectura moderna API-First con separación clara entre Backend y Frontend.

## 🛠️ Tecnologías

-   **Backend**: PHP 8.2+, Laravel 11.
-   **Frontend**: Vue.js 3, Bootstrap 5, Axios, SweetAlert2.
-   **Base de Datos**: PostgreSQL 16+.
-   **Herramientas**: Vite, Composer, NPM.

## ✅ Testing

Para ejecutar las pruebas automatizadas del proyecto:

### Pruebas de Backend (PHPUnit)

```bash
php artisan test
```

### Pruebas de Frontend (si aplica)

```bash
npm run test
```

## 🔄 Integración Continua (CI)

Este proyecto está preparado para CI. Se recomienda configurar un pipeline (ej. GitHub Actions) que ejecute:

1.  Linting de código (PHP_CodeSniffer / ESLint).
2.  Análisis estático (PHPStan).
3.  Pruebas unitarias y de integración (PHPUnit).

Asegúrese de configurar las variables de entorno necesarias en su proveedor de CI para la conexión a base de datos de pruebas.

## 📋 Requisitos Previos

-   PHP >= 8.2
-   Composer
-   Node.js & NPM
-   Servidor PostgreSQL

## 🔧 Instalación

1. **Clonar el repositorio**

    ```bash
    git clone https://github.com/tu-usuario/sistema-venta.git
    cd sistema-venta
    ```

2. **Instalar dependencias de PHP**

    ```bash
    composer install
    ```

3. **Instalar dependencias de JavaScript**

    ```bash
    npm install
    ```

4. **Configurar entorno**

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

    _Configura tus credenciales de base de datos en el archivo `.env` (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD)._

5. **Migración y Seeders**
   Este comando creará las tablas y poblará la base de datos con usuarios y datos iniciales.

    ```bash
    php artisan migrate --seed
    ```

6. **Crear enlace simbólico para imágenes**

    ```bash
    php artisan storage:link
    ```

7. **Compilación de Assets**
    ```bash
    npm run build
    ```
    _Para desarrollo:_ `npm run dev`

## 🏁 Ejecución

Para iniciar el servidor local de desarrollo:

```bash
php artisan serve
```

El sistema estará disponible en `http://localhost:8000`.

## 📄 Licencia

Este proyecto está bajo la licencia [MIT](LICENSE)
