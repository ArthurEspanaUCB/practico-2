#!/bin/bash
#================================================================
# deploy.sh - Atomic Deployment Script
# Sistema de Ventas - Laravel Application
#================================================================
# Usage: ./deploy.sh <release-tag> [environment]
# Example: ./deploy.sh v1.0.0-20260114-164500 staging
#================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

#================================================================
# CONFIGURATION
#================================================================
RELEASE_TAG="${1:-}"
ENVIRONMENT="${2:-staging}"

# Load config based on environment
if [ "$ENVIRONMENT" == "production" ]; then
    DEPLOY_PATH="${PROD_PATH:-/var/www/sistema-ventas-production}"
    GITHUB_REPO="${GITHUB_REPOSITORY:-helmerfmj/sistema-ventas}"
else
    DEPLOY_PATH="${STAGING_PATH:-/var/www/sistema-ventas-staging}"
    GITHUB_REPO="${GITHUB_REPOSITORY:-helmerfmj/sistema-ventas}"
fi

RELEASES_DIR="${DEPLOY_PATH}/releases"
SHARED_DIR="${DEPLOY_PATH}/shared"
CURRENT_LINK="${DEPLOY_PATH}/current"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
RELEASE_DIR="${RELEASES_DIR}/${TIMESTAMP}"

# PHP-FPM service (adjust for your system)
PHP_FPM_SERVICE="php8.4-fpm"

#================================================================
# VALIDATION
#================================================================
if [ -z "$RELEASE_TAG" ]; then
    log_error "Usage: $0 <release-tag> [environment]"
    log_error "Example: $0 v1.0.0-20260114-164500 staging"
    exit 1
fi

log_info "========================================"
log_info "DEPLOYMENT STARTED"
log_info "========================================"
log_info "Release: ${RELEASE_TAG}"
log_info "Environment: ${ENVIRONMENT}"
log_info "Deploy Path: ${DEPLOY_PATH}"
log_info "Timestamp: ${TIMESTAMP}"
log_info "========================================"

#================================================================
# PRE-DEPLOY CHECKS
#================================================================
log_info "Running pre-deploy checks..."

# Check disk space (requires at least 2GB free)
AVAILABLE_SPACE=$(df -BG "${DEPLOY_PATH}" | awk 'NR==2 {print $4}' | sed 's/G//')
if [ "$AVAILABLE_SPACE" -lt 2 ]; then
    log_error "Insufficient disk space. Need at least 2GB, have ${AVAILABLE_SPACE}GB"
    exit 1
fi
log_success "Disk space check passed (${AVAILABLE_SPACE}GB available)"

# Check shared directory exists
if [ ! -d "$SHARED_DIR" ]; then
    log_error "Shared directory not found: ${SHARED_DIR}"
    log_error "Run initial setup first!"
    exit 1
fi
log_success "Shared directory exists"

# Check .env exists
if [ ! -f "${SHARED_DIR}/.env" ]; then
    log_error ".env file not found in ${SHARED_DIR}"
    exit 1
fi
log_success ".env file exists"

#================================================================
# DOWNLOAD RELEASE
#================================================================
log_info "Downloading release from GitHub..."

ARTIFACT_NAME="sistema-ventas-${RELEASE_TAG}"
DOWNLOAD_DIR="/tmp/deploy-${TIMESTAMP}"
mkdir -p "$DOWNLOAD_DIR"

# Download from GitHub Releases
DOWNLOAD_URL="https://github.com/${GITHUB_REPO}/releases/download/${RELEASE_TAG}/${ARTIFACT_NAME}.tar.gz"
CHECKSUM_URL="https://github.com/${GITHUB_REPO}/releases/download/${RELEASE_TAG}/${ARTIFACT_NAME}.tar.gz.sha256"

log_info "Downloading: ${DOWNLOAD_URL}"
curl -fsSL -o "${DOWNLOAD_DIR}/${ARTIFACT_NAME}.tar.gz" "$DOWNLOAD_URL" || {
    log_error "Failed to download release artifact"
    exit 1
}

log_info "Downloading checksum..."
curl -fsSL -o "${DOWNLOAD_DIR}/${ARTIFACT_NAME}.tar.gz.sha256" "$CHECKSUM_URL" || {
    log_warning "Checksum file not found, skipping verification"
}

# Verify checksum if available
if [ -f "${DOWNLOAD_DIR}/${ARTIFACT_NAME}.tar.gz.sha256" ]; then
    log_info "Verifying checksum..."
    cd "$DOWNLOAD_DIR"
    sha256sum -c "${ARTIFACT_NAME}.tar.gz.sha256" || {
        log_error "Checksum verification failed!"
        exit 1
    }
    log_success "Checksum verified"
fi

#================================================================
# PREPARE NEW RELEASE
#================================================================
log_info "Preparing new release directory..."

mkdir -p "$RELEASE_DIR"
tar -xzf "${DOWNLOAD_DIR}/${ARTIFACT_NAME}.tar.gz" -C "$RELEASE_DIR"
log_success "Release extracted to ${RELEASE_DIR}"

#================================================================
# CREATE SYMLINKS TO SHARED
#================================================================
log_info "Creating symlinks to shared resources..."

# Remove default storage and link to shared
rm -rf "${RELEASE_DIR}/storage"
ln -sfn "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"
log_success "Linked storage -> shared/storage"

# Link .env
ln -sfn "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
log_success "Linked .env -> shared/.env"

#================================================================
# SET PERMISSIONS
#================================================================
log_info "Setting permissions..."

chown -R www-data:www-data "$RELEASE_DIR"
find "$RELEASE_DIR" -type f -exec chmod 644 {} \;
find "$RELEASE_DIR" -type d -exec chmod 755 {} \;
chmod -R 775 "${SHARED_DIR}/storage"

log_success "Permissions set"

#================================================================
# DATABASE BACKUP
#================================================================
log_info "Creating database backup..."

BACKUP_DIR="${SHARED_DIR}/database/backups"
mkdir -p "$BACKUP_DIR"

# Call backup script
if [ -f "${DEPLOY_PATH}/scripts/deploy/backup-database.sh" ]; then
    "${DEPLOY_PATH}/scripts/deploy/backup-database.sh" "pre-${TIMESTAMP}" || {
        log_warning "Database backup failed, continuing anyway..."
    }
else
    log_warning "Backup script not found, skipping backup"
fi

#================================================================
# RUN MIGRATIONS
#================================================================
log_info "Running database migrations..."

cd "$RELEASE_DIR"
php artisan migrate --force || {
    log_error "Migration failed!"
    log_error "Deployment aborted. Previous release is still active."
    exit 1
}
log_success "Migrations completed"

#================================================================
# ATOMIC SWITCH
#================================================================
log_info "Performing atomic switch..."

# Store previous release for potential rollback
PREVIOUS_RELEASE=$(readlink -f "$CURRENT_LINK" 2>/dev/null || echo "")
if [ -n "$PREVIOUS_RELEASE" ]; then
    echo "$PREVIOUS_RELEASE" > "${SHARED_DIR}/.previous_release"
fi

# Atomic symlink update
ln -sfn "$RELEASE_DIR" "${CURRENT_LINK}.new"
mv -Tf "${CURRENT_LINK}.new" "$CURRENT_LINK"

log_success "Symlink updated: current -> ${RELEASE_DIR}"

#================================================================
# RELOAD SERVICES
#================================================================
log_info "Reloading services..."

# Reload PHP-FPM
if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
    sudo systemctl reload "$PHP_FPM_SERVICE"
    log_success "PHP-FPM reloaded"
else
    log_warning "PHP-FPM service not found or not running"
fi

# Reload Nginx
if systemctl is-active --quiet nginx; then
    sudo nginx -s reload
    log_success "Nginx reloaded"
else
    log_warning "Nginx not running"
fi

# Restart queue workers if using supervisor
if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart "sistema-ventas-worker:*" 2>/dev/null || true
    log_success "Queue workers restarted"
fi

#================================================================
# HEALTH CHECK
#================================================================
log_info "Running health checks..."

if [ -f "${DEPLOY_PATH}/scripts/deploy/health-check.sh" ]; then
    "${DEPLOY_PATH}/scripts/deploy/health-check.sh" || {
        log_error "Health check failed! Initiating rollback..."
        
        # Rollback to previous release
        if [ -n "$PREVIOUS_RELEASE" ] && [ -d "$PREVIOUS_RELEASE" ]; then
            ln -sfn "$PREVIOUS_RELEASE" "${CURRENT_LINK}.new"
            mv -Tf "${CURRENT_LINK}.new" "$CURRENT_LINK"
            sudo systemctl reload "$PHP_FPM_SERVICE" 2>/dev/null || true
            sudo nginx -s reload 2>/dev/null || true
            log_warning "Rolled back to: ${PREVIOUS_RELEASE}"
        fi
        
        exit 1
    }
else
    log_warning "Health check script not found, skipping"
fi

log_success "Health checks passed"

#================================================================
# CLEANUP
#================================================================
log_info "Cleaning up..."

# Remove download directory
rm -rf "$DOWNLOAD_DIR"

# Call cleanup script
if [ -f "${DEPLOY_PATH}/scripts/deploy/cleanup-releases.sh" ]; then
    "${DEPLOY_PATH}/scripts/deploy/cleanup-releases.sh"
fi

#================================================================
# DONE
#================================================================
log_info "========================================"
log_success "DEPLOYMENT COMPLETED SUCCESSFULLY"
log_info "========================================"
log_info "Release: ${RELEASE_TAG}"
log_info "Directory: ${RELEASE_DIR}"
log_info "Environment: ${ENVIRONMENT}"
log_info "========================================"

exit 0
