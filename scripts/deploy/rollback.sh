#!/bin/bash
#================================================================
# rollback.sh - Manual Rollback Script
# Sistema de Ventas - Laravel Application
#================================================================
# Usage: ./rollback.sh [release-directory]
# Example: ./rollback.sh releases/20260114-160000
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
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_PATH="$(dirname "$(dirname "$SCRIPT_DIR")")"
RELEASES_DIR="${DEPLOY_PATH}/releases"
SHARED_DIR="${DEPLOY_PATH}/shared"
CURRENT_LINK="${DEPLOY_PATH}/current"

TARGET_RELEASE="${1:-}"
PHP_FPM_SERVICE="php8.4-fpm"

#================================================================
# DETERMINE TARGET
#================================================================
if [ -z "$TARGET_RELEASE" ]; then
    # Try to get previous release from file
    if [ -f "${SHARED_DIR}/.previous_release" ]; then
        TARGET_RELEASE=$(cat "${SHARED_DIR}/.previous_release")
        log_info "Found previous release: ${TARGET_RELEASE}"
    else
        # Get second most recent release
        TARGET_RELEASE=$(ls -td "${RELEASES_DIR}"/*/ 2>/dev/null | sed -n '2p' | xargs basename 2>/dev/null || echo "")
        if [ -n "$TARGET_RELEASE" ]; then
            TARGET_RELEASE="${RELEASES_DIR}/${TARGET_RELEASE}"
        fi
    fi
fi

# Resolve relative path
if [[ ! "$TARGET_RELEASE" = /* ]]; then
    TARGET_RELEASE="${DEPLOY_PATH}/${TARGET_RELEASE}"
fi

#================================================================
# VALIDATION
#================================================================
if [ -z "$TARGET_RELEASE" ] || [ ! -d "$TARGET_RELEASE" ]; then
    log_error "No valid release to rollback to!"
    log_info ""
    log_info "Available releases:"
    ls -lt "${RELEASES_DIR}" 2>/dev/null | head -10 || echo "  (none)"
    log_info ""
    log_info "Usage: $0 <release-path>"
    log_info "Example: $0 releases/20260114-160000"
    exit 1
fi

CURRENT_RELEASE=$(readlink -f "$CURRENT_LINK" 2>/dev/null || echo "")

log_info "========================================"
log_info "ROLLBACK INITIATED"
log_info "========================================"
log_info "Current: ${CURRENT_RELEASE}"
log_info "Target:  ${TARGET_RELEASE}"
log_info "========================================"

# Confirmation
read -p "Are you sure you want to rollback? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_warning "Rollback cancelled"
    exit 0
fi

#================================================================
# PERFORM ROLLBACK
#================================================================
log_info "Performing atomic rollback..."

# Atomic symlink update
ln -sfn "$TARGET_RELEASE" "${CURRENT_LINK}.new"
mv -Tf "${CURRENT_LINK}.new" "$CURRENT_LINK"

log_success "Symlink updated"

#================================================================
# RELOAD SERVICES
#================================================================
log_info "Reloading services..."

if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
    sudo systemctl reload "$PHP_FPM_SERVICE"
    log_success "PHP-FPM reloaded"
fi

if systemctl is-active --quiet nginx; then
    sudo nginx -s reload
    log_success "Nginx reloaded"
fi

if command -v supervisorctl &> /dev/null; then
    sudo supervisorctl restart "sistema-ventas-worker:*" 2>/dev/null || true
fi

#================================================================
# DONE
#================================================================
log_info "========================================"
log_success "ROLLBACK COMPLETED"
log_info "========================================"
log_info "Active release: ${TARGET_RELEASE}"
log_info "========================================"

exit 0
