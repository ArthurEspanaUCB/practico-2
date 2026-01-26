#!/bin/bash
#================================================================
# health-check.sh - Post-Deploy Health Validation
# Sistema de Ventas - Laravel Application
#================================================================
# Usage: ./health-check.sh [url] [timeout]
# Example: ./health-check.sh https://staging.example.com 30
#================================================================

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[✓]${NC} $1"; }
log_error() { echo -e "${RED}[✗]${NC} $1"; }

#================================================================
# CONFIGURATION
#================================================================
BASE_URL="${1:-http://localhost}"
TIMEOUT="${2:-30}"
HEALTH_ENDPOINT="${BASE_URL}/api/health"
CHECKS_PASSED=0
CHECKS_TOTAL=0

#================================================================
# HEALTH CHECK FUNCTION
#================================================================
check_health() {
    local name="$1"
    local check="$2"
    CHECKS_TOTAL=$((CHECKS_TOTAL + 1))
    
    if eval "$check"; then
        log_success "$name"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
        return 0
    else
        log_error "$name"
        return 1
    fi
}

#================================================================
# RUN CHECKS
#================================================================
log_info "Running health checks for: ${BASE_URL}"
log_info "Timeout: ${TIMEOUT} seconds"
echo ""

FAILED=0

# Check 1: HTTP Response
log_info "Checking HTTP endpoint..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time "$TIMEOUT" "$HEALTH_ENDPOINT" 2>/dev/null || echo "000")
if [ "$HTTP_STATUS" == "200" ]; then
    log_success "HTTP Status: ${HTTP_STATUS}"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    log_error "HTTP Status: ${HTTP_STATUS} (expected 200)"
    FAILED=1
fi
CHECKS_TOTAL=$((CHECKS_TOTAL + 1))

# Check 2: Health Response JSON
if [ "$HTTP_STATUS" == "200" ]; then
    log_info "Checking health response..."
    HEALTH_RESPONSE=$(curl -s --max-time "$TIMEOUT" "$HEALTH_ENDPOINT" 2>/dev/null)
    
    # Check status field
    STATUS=$(echo "$HEALTH_RESPONSE" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
    if [ "$STATUS" == "ok" ]; then
        log_success "Application Status: ${STATUS}"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        log_error "Application Status: ${STATUS} (expected 'ok')"
        FAILED=1
    fi
    CHECKS_TOTAL=$((CHECKS_TOTAL + 1))
    
    # Check database
    DB_OK=$(echo "$HEALTH_RESPONSE" | grep -o '"db":{[^}]*}' | grep -o '"ok":true' || echo "")
    if [ -n "$DB_OK" ]; then
        log_success "Database: Connected"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        log_error "Database: Not connected"
        FAILED=1
    fi
    CHECKS_TOTAL=$((CHECKS_TOTAL + 1))
fi

# Check 3: Response Time
if [ "$HTTP_STATUS" == "200" ]; then
    log_info "Checking response time..."
    RESPONSE_TIME=$(curl -s -o /dev/null -w "%{time_total}" --max-time "$TIMEOUT" "$HEALTH_ENDPOINT" 2>/dev/null)
    RESPONSE_MS=$(echo "$RESPONSE_TIME * 1000" | bc 2>/dev/null | cut -d'.' -f1 || echo "0")
    
    if [ "${RESPONSE_MS:-0}" -lt 5000 ]; then
        log_success "Response Time: ${RESPONSE_MS}ms"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        log_error "Response Time: ${RESPONSE_MS}ms (too slow, >5000ms)"
        FAILED=1
    fi
    CHECKS_TOTAL=$((CHECKS_TOTAL + 1))
fi

# Check 4: Storage writable (via artisan if available)
if [ -f "artisan" ]; then
    log_info "Checking storage permissions..."
    if php artisan storage:link --force 2>/dev/null; then
        log_success "Storage: Writable"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
    else
        log_error "Storage: Permission issues"
        FAILED=1
    fi
    CHECKS_TOTAL=$((CHECKS_TOTAL + 1))
fi

#================================================================
# SUMMARY
#================================================================
echo ""
log_info "========================================"
log_info "Health Check Summary"
log_info "========================================"
log_info "Checks Passed: ${CHECKS_PASSED}/${CHECKS_TOTAL}"

if [ "$FAILED" -eq 0 ]; then
    log_success "ALL HEALTH CHECKS PASSED"
    exit 0
else
    log_error "HEALTH CHECKS FAILED"
    exit 1
fi
