#!/bin/bash
#================================================================
# cleanup-releases.sh - Old Releases Cleanup
# Sistema de Ventas - Laravel Application
#================================================================
# Usage: ./cleanup-releases.sh [keep-count]
# Example: ./cleanup-releases.sh 3
#================================================================

set -e

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }

#================================================================
# CONFIGURATION
#================================================================
KEEP_COUNT="${1:-3}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DEPLOY_PATH="$(dirname "$(dirname "$SCRIPT_DIR")")"
RELEASES_DIR="${DEPLOY_PATH}/releases"
CURRENT_LINK="${DEPLOY_PATH}/current"

log_info "========================================"
log_info "CLEANUP RELEASES"
log_info "========================================"
log_info "Releases to keep: ${KEEP_COUNT}"
log_info "========================================"

# Get current release (to never delete)
CURRENT_RELEASE=$(readlink -f "$CURRENT_LINK" 2>/dev/null | xargs basename 2>/dev/null || echo "")

# List all releases sorted by date (oldest first)
RELEASES=$(ls -td "${RELEASES_DIR}"/*/ 2>/dev/null | xargs -I{} basename {} || echo "")
RELEASE_COUNT=$(echo "$RELEASES" | wc -l)

log_info "Total releases: ${RELEASE_COUNT}"
log_info "Current release: ${CURRENT_RELEASE}"

if [ "$RELEASE_COUNT" -le "$KEEP_COUNT" ]; then
    log_success "Nothing to clean up"
    exit 0
fi

# Calculate how many to delete
DELETE_COUNT=$((RELEASE_COUNT - KEEP_COUNT))
log_info "Releases to delete: ${DELETE_COUNT}"

# Get releases to delete (oldest ones, excluding current)
DELETED=0
for RELEASE in $(ls -td "${RELEASES_DIR}"/*/ | tail -n "$DELETE_COUNT" | xargs -I{} basename {}); do
    RELEASE_PATH="${RELEASES_DIR}/${RELEASE}"
    
    # Never delete current release
    if [ "$RELEASE" == "$CURRENT_RELEASE" ]; then
        log_warning "Skipping current release: ${RELEASE}"
        continue
    fi
    
    log_info "Deleting: ${RELEASE}"
    rm -rf "$RELEASE_PATH"
    DELETED=$((DELETED + 1))
done

log_info "========================================"
log_success "Cleanup complete. Deleted ${DELETED} releases."
log_info "========================================"

exit 0
