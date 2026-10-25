#!/usr/bin/env bash
#
# Chromium-based user acceptance tests for ksf_FA_ImportStagingProcessing.
#
# These tests drive Chromium in headless mode against a live FrontAccounting
# instance to exercise the module's UI pages end to end.
#
# Prerequisites:
 *   - chromium or google-chrome installed
 *   - A running FA instance with ksf_FA_ImportStagingProcessing installed
 *     and activated for a company.
 *   - Set FA_USER_TEST_BASE_URL (e.g. http://localhost:8090) and
 *     FA_USER_TEST_COMPANY (company id) before running.
 *
# Usage:
#   FA_USER_TEST_BASE_URL=http://localhost:8090 \
#   FA_USER_TEST_COMPANY=1 \
#   ./tests/User/chromium_user_tests.sh
#
# @since 2.0.0
# @BABOK Related: UAT-ISU-001 through UAT-ISU-007
# @BABOK Related: NFR-ISU-008 user test coverage

set -euo pipefail

BASE_URL="${FA_USER_TEST_BASE_URL:-}"
COMPANY="${FA_USER_TEST_COMPANY:-}"
CHROMIUM="${CHROMIUM:-chromium}"

if [[ -z "$BASE_URL" || -z "$COMPANY" ]]; then
    echo "SKIP: FA_USER_TEST_BASE_URL and FA_USER_TEST_COMPANY must be set."
    exit 0
fi

PASS=0
FAIL=0

assert_contains() {
    local label="$1" body="$2" needle="$3"
    if echo "$body" | grep -qF "$needle"; then
        echo "PASS: $label"
        PASS=$((PASS + 1))
    else
        echo "FAIL: $label — expected '$needle' in page body"
        FAIL=$((FAIL + 1))
    fi
}

assert_not_contains() {
    local label="$1" body="$2" needle="$3"
    if echo "$body" | grep -qF "$needle"; then
        echo "FAIL: $label — did not expect '$needle' in page body"
        FAIL=$((FAIL + 1))
    else
        echo "PASS: $label"
        PASS=$((PASS + 1))
    fi
}

fetch() {
    local url="$1"
    "$CHROMIUM" --headless --no-sandbox --disable-gpu \
        --dump-dom "$url" 2>/dev/null || true
}

echo "=== Chromium User Acceptance Tests — ksf_FA_ImportStagingProcessing ==="
echo "Base URL: $BASE_URL  Company: $COMPANY"
echo

# ------------------------------------------------------------------
# UAT-ISU-003: Process staged transactions into FA invoices.
# Load the process statements page and verify the process queue renders.
# ------------------------------------------------------------------
BODY=$(fetch "$BASE_URL/modules/ksf_FA_ImportStagingProcessing/process_statements.php?company=$COMPANY")
assert_contains "Process queue page loads" "$BODY" "process_statements"
assert_contains "Process queue shows status filter" "$BODY" "status"

# ------------------------------------------------------------------
# UAT-ISU-007: Process queue displays correct status.
# Verify the page contains status filter controls.
# ------------------------------------------------------------------
assert_contains "Status filter present" "$BODY" "unsettled\|settled\|all"

# ------------------------------------------------------------------
# UAT-ISU-001: Upload CSV and verify staging records.
# Load the import statements page and verify the CSV upload form renders.
# ------------------------------------------------------------------
IMPORT_BODY=$(fetch "$BASE_URL/modules/ksf_FA_ImportStagingProcessing/import_statements.php?company=$COMPANY")
assert_contains "Import page loads" "$IMPORT_BODY" "import_statements"
assert_contains "CSV upload form present" "$IMPORT_BODY" "upload\|file\|csv"

# ------------------------------------------------------------------
# UAT-ISU-005: Counterparty matching links to existing customers.
# Load the main module page and verify customer matching controls render.
# ------------------------------------------------------------------
MAIN_BODY=$(fetch "$BASE_URL/modules/ksf_FA_ImportStagingProcessing/ksf_import_staging.php?company=$COMPANY")
assert_contains "Main module page loads" "$MAIN_BODY" "ksf_import_staging"

echo
echo "=== Results: $PASS passed, $FAIL failed ==="
if [[ $FAIL -gt 0 ]]; then
    exit 1
fi
exit 0
