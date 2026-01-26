#!/bin/bash
#================================================================
# backup-database.sh - PostgreSQL Database Backup
# Sistema de Ventas - Laravel Application
#================================================================
# Usage: ./backup-database.sh [backup-name]
# Example: ./backup-database.sh pre-20260114-164500
#================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

#================================================================
# CONFIGURATION
#================================================================
BACKUP_NAME="${1:-backup-$(date +%Y%m%d-%H%M%S)}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_PATH="$(dirname "$(dirname "$SCRIPT_DIR")")"
SHARED_DIR="${DEPLOY_PATH}/shared"
BACKUP_DIR="${SHARED_DIR}/database/backups"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"

# Database credentials (from environment or .env)
if [ -f "${SHARED_DIR}/.env" ]; then
    source <(grep -E '^(DB_HOST|DB_DATABASE|DB_USERNAME|DB_PASSWORD|DB_PORT)=' "${SHARED_DIR}/.env" | sed 's/^/export /')
fi

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_DATABASE:-sistema_ventas}"
DB_USER="${DB_USERNAME:-postgres}"
DB_PASS="${DB_PASSWORD:-}"

BACKUP_FILE="${BACKUP_DIR}/${BACKUP_NAME}.sql"

#================================================================
# VALIDATION
#================================================================
mkdir -p "$BACKUP_DIR"

if ! command -v pg_dump &> /dev/null; then
    log_error "pg_dump not found. Install PostgreSQL client tools."
    exit 1
fi

#================================================================
# CREATE BACKUP
#================================================================
log_info "========================================"
log_info "DATABASE BACKUP"
log_info "========================================"
log_info "Database: ${DB_NAME}"
log_info "Host: ${DB_HOST}:${DB_PORT}"
log_info "Backup: ${BACKUP_FILE}"
log_info "========================================"

export PGPASSWORD="$DB_PASS"

pg_dump \
    -h "$DB_HOST" \
    -p "$DB_PORT" \
    -U "$DB_USER" \
    -Fc \
    -f "$BACKUP_FILE" \
    "$DB_NAME" || {
    log_error "Backup failed!"
    exit 1
}

# Get file size
BACKUP_SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')
log_success "Backup created: ${BACKUP_FILE} (${BACKUP_SIZE})"

#================================================================
# CLEANUP OLD BACKUPS
#================================================================
log_info "Cleaning up backups older than ${RETENTION_DAYS} days..."

find "$BACKUP_DIR" -name "*.sql" -type f -mtime +${RETENTION_DAYS} -delete 2>/dev/null || true

REMAINING=$(ls -1 "$BACKUP_DIR"/*.sql 2>/dev/null | wc -l || echo "0")
log_info "Remaining backups: ${REMAINING}"

#================================================================
# DONE
#================================================================
log_info "========================================"
log_success "BACKUP COMPLETED SUCCESSFULLY"
log_info "========================================"

exit 0
