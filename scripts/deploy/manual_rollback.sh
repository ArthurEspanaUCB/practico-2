#!/bin/bash
# =============================================================================
# Manual Rollback Script
# Uso: ./scripts/deploy/manual_rollback.sh [staging|production] [release_id]
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

ENVIRONMENT=${1:-staging}
RELEASE_ID=${2:-}
BASE_PATH="/var/www/comprasventas"

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}  ROLLBACK MANUAL - $ENVIRONMENT${NC}"
echo -e "${YELLOW}========================================${NC}"

# Verificar que estamos en el servidor correcto
if [ ! -d "$BASE_PATH/releases" ]; then
    echo -e "${RED}Error: No se encontró el directorio de releases en $BASE_PATH${NC}"
    exit 1
fi

# Listar releases disponibles
echo -e "\n${GREEN}Releases disponibles:${NC}"
ls -1dt $BASE_PATH/releases/*/ 2>/dev/null | head -10 | while read dir; do
    release_name=$(basename "$dir")
    current_link=$(readlink -f "$BASE_PATH/current" 2>/dev/null || echo "")
    if [ "$BASE_PATH/releases/$release_name" = "$current_link" ]; then
        echo -e "  ${GREEN}→ $release_name (ACTUAL)${NC}"
    else
        echo "    $release_name"
    fi
done

# Si no se especificó release, usar el anterior
if [ -z "$RELEASE_ID" ]; then
    RELEASE_ID=$(ls -1dt $BASE_PATH/releases/*/ 2>/dev/null | sed -n '2p' | xargs -r basename)
    if [ -z "$RELEASE_ID" ]; then
        echo -e "${RED}Error: No hay release anterior disponible para rollback${NC}"
        exit 1
    fi
    echo -e "\n${YELLOW}No se especificó release, usando el anterior: $RELEASE_ID${NC}"
fi

# Verificar que el release existe
if [ ! -d "$BASE_PATH/releases/$RELEASE_ID" ]; then
    echo -e "${RED}Error: El release $RELEASE_ID no existe${NC}"
    exit 1
fi

# Confirmar rollback
echo -e "\n${YELLOW}¿Realizar rollback a $RELEASE_ID? (y/N)${NC}"
read -r confirm
if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "Rollback cancelado"
    exit 0
fi

# Ejecutar rollback
echo -e "\n${GREEN}Ejecutando rollback...${NC}"

# Cambiar symlink atómicamente
ln -sfn "$BASE_PATH/releases/$RELEASE_ID" "$BASE_PATH/current"

# Limpiar cachés
cd "$BASE_PATH/current"
php artisan optimize:clear 2>/dev/null || true
php artisan config:cache
php artisan route:cache

# Verificar
CURRENT=$(readlink -f "$BASE_PATH/current")
echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}  ROLLBACK COMPLETADO${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "Current symlink: $CURRENT"

# Health check
echo -e "\n${YELLOW}Verificando health...${NC}"
if command -v curl &> /dev/null; then
    if [ "$ENVIRONMENT" = "production" ]; then
        HEALTH_URL="${PROD_APP_URL:-http://localhost}/api/health"
    else
        HEALTH_URL="${STAGING_APP_URL:-http://localhost}/api/health"
    fi
    
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$HEALTH_URL" 2>/dev/null || echo "000")
    if [ "$HTTP_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Health check PASSED (HTTP $HTTP_STATUS)${NC}"
    else
        echo -e "${RED}⚠️ Health check returned HTTP $HTTP_STATUS${NC}"
    fi
fi

echo -e "\n${GREEN}Rollback a $RELEASE_ID completado exitosamente${NC}"
