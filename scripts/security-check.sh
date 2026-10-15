#!/usr/bin/env bash
# The checks from .github/workflows/security.yml and tests.yml, run on this machine.
# Used by .githooks/pre-push (before every `git push`) and `composer security` (by hand).
#
#   Secrets       gitleaks over the full history (.gitleaksignore lists revoked findings)
#   Dependencies  composer audit; npm audit for what ships ("dependencies") + critical in build tools
#   Code scan     Semgrep p/php, p/security-audit, p/secrets (.semgrepignore skips third-party code)
#   Tests         PHPUnit against elibrarydb_test — never the live database (see phpunit.xml)
#
# Every check runs even if an earlier one fails; the script exits non-zero if any failed.
# gitleaks and semgrep are found on PATH or in ~/.local/bin; a missing one is skipped with a warning.
# Options: --no-tests skips PHPUnit (e.g. when MySQL isn't running).

set -u
cd "$(git rev-parse --show-toplevel)" || exit 1

run_tests=1
for arg in "$@"; do
    [ "$arg" = "--no-tests" ] && run_tests=0
done

tool() {
    command -v "$1" 2>/dev/null && return
    [ -x "$HOME/.local/bin/$1" ] && echo "$HOME/.local/bin/$1"
}

failed=()
skipped=()
out=$(mktemp)
trap 'rm -f "$out"' EXIT

check() {   # check "<label>" <command...> — prints pass/FAIL; on failure, the command's output
    local label=$1 start=$SECONDS
    shift
    printf '  %-38s' "$label"
    if "$@" > "$out" 2>&1; then
        printf 'pass  (%ss)\n' $((SECONDS - start))
    else
        printf 'FAIL  (%ss)\n' $((SECONDS - start))
        tail -n 40 "$out" | sed 's/^/      /'
        failed+=("$label")
    fi
}

joined() {   # joined "a" "b, c" -> "a; b, c" (labels can contain commas)
    local list
    list=$(printf '%s; ' "$@")
    echo "${list%; }"
}

skip() {
    printf '  %-38s%s\n' "$1" "skipped: $2"
    skipped+=("$1")
}

echo "Security checks (same as the GitHub workflows):"
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "  note: you have uncommitted changes — these checks see your working tree, not just the commits."
fi

gitleaks=$(tool gitleaks)
if [ -n "$gitleaks" ]; then
    check "Secrets (gitleaks, full history)" "$gitleaks" git --redact --no-banner .
else
    skip "Secrets (gitleaks)" "gitleaks not found (see .githooks/pre-commit)"
fi

check "PHP packages (composer audit)" composer audit --locked --no-interaction
check "JS that ships (npm audit)" npm audit --omit=dev
check "Build tooling (npm audit, critical)" npm audit --audit-level=critical

semgrep=$(tool semgrep)
if [ -n "$semgrep" ]; then
    check "Code scan (Semgrep)" "$semgrep" scan --config p/php --config p/security-audit --config p/secrets \
        --metrics=off --error --quiet --disable-version-check .
else
    skip "Code scan (Semgrep)" "semgrep not found (pip install semgrep==1.178.0)"
fi

if [ "$run_tests" -eq 1 ]; then
    check "Tests (PHPUnit, elibrarydb_test)" vendor/bin/phpunit
else
    skip "Tests (PHPUnit)" "--no-tests"
fi

echo
if [ ${#failed[@]} -gt 0 ]; then
    echo "✗ ${#failed[@]} check(s) failed: $(joined "${failed[@]}")"
    exit 1
fi
if [ ${#skipped[@]} -gt 0 ]; then
    echo "✓ All checks that ran passed (skipped: $(joined "${skipped[@]}"))"
else
    echo "✓ All checks passed"
fi
