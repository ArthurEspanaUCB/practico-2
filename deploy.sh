#!/bin/bash

# Detener el script si hay errores
set -e

echo "🚀 Iniciando despliegue..."

# Navegar al directorio del proyecto (asegúrate de que esta ruta sea correcta en tu servidor)
# cd /path/to/project (Esto se gestionará desde el workflow o asumiendo ejecución en root del proyecto)

# Poner la aplicación en modo mantenimiento
echo "🔒 Poniendo aplicación en modo mantenimiento..."
php artisan down || true

# Actualizar código fuente
echo "📥 Descargando últimos cambios..."
git pull origin main

# Instalar dependencias de PHP
echo "📦 Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

# Instalar dependencias de Node y compilar assets
echo "🎨 Compilando assets de Frontend..."
npm ci
npm run build

# Ejecutar migraciones de base de datos
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# Limpiar y cachear configuración
echo "🧹 Optimizando cachés..."
php artisan optimize:clear
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache

# Restaurar permisos (ajusta 'www-data' según tu usuario de servidor web)
echo "🔑 Restaurando permisos..."
# chown -R www-data:www-data . # Descomentar si es necesario y tienes sudo
# chmod -R 775 storage bootstrap/cache

# Sacar de modo mantenimiento
echo "🔓 Levantando aplicación..."
php artisan up

echo "✅ ¡Despliegue completado con éxito!"
