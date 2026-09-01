#!/usr/bin/env bash

##
# Test + demonstration for tools/replace-next-version-tag.sh.
#
# Runs the script against a throwaway git repository (the script scans `git ls-files`,
# so fixtures must be tracked) and asserts every supported `$$next-version$$` context is
# handled, plus that an unsupported context is reported rather than silently left behind.
#
# Usage: bash tools/tests/replace-next-version-tag.test.sh
# Exits 0 if all assertions pass, non-zero otherwise.
##

set -euo pipefail

SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/replace-next-version-tag.sh"
VERSION="9.9.9"
FAILURES=0

# --- Tiny assertion helpers ----------------------------------------------------------

function pass { echo "  ✓ $1"; }
function fail { echo "  ✗ $1"; FAILURES=$(( FAILURES + 1 )); }

function assert_contains {
	# assert_contains <file> <literal string> <label>
	if grep -Fq "$2" "$1"; then pass "$3"; else fail "$3 (expected to find: $2)"; fi
}

function assert_no_token {
	# assert_no_token <file> <label>
	if grep -Fq '$$next-version$$' "$1"; then fail "$2 (token still present)"; else pass "$2"; fi
}

# --- Build a throwaway git repo with one fixture per supported context ----------------

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
cd "$TMP"
git init -q
git config user.email test@example.com
git config user.name test

mkdir -p src

# A fixture covering every recognized pattern. Tabs are required for the function-call
# patterns (WordPress coding standards), so this heredoc is intentionally tab-indented.
cat > src/fixture.php <<'PHP'
<?php
/**
 * @since $$next-version$$
 * @deprecated $$next-version$$
 * @deprecated since $$next-version$$
 */
function demo() {
	_deprecated_function( 'old_core_fn', '$$next-version$$', 'demo' );
	wc_deprecated_function( 'old_wc_fn', '$$next-version$$' );
	wc_deprecated_hook( 'woocommerce_old_hook', '$$next-version$$', 'woocommerce_new_hook' );
	wc_deprecated_argument( 'arg', '$$next-version$$', 'Use bar instead.' );
	_doing_it_wrong( __METHOD__, 'Nope.', '$$next-version$$' );
	wc_doing_it_wrong( __METHOD__, 'Nope.', '$$next-version$$' );
	do_action_deprecated( 'woocommerce_old_action', array(), '$$next-version$$', 'woocommerce_new_action' );
	apply_filters_deprecated( 'woocommerce_old_filter', array( $value ), '$$next-version$$' );
}
PHP

git add -A

echo
echo '== Demonstration: every $$next-version$$ context the script understands =='
echo "--- before ---"
cat src/fixture.php

# --- Check-only mode: should list tokens and exit non-zero ----------------------------

echo
echo "== Check-only mode (-c): reports tokens, makes no changes, exits non-zero =="
check_exit=0
CI='' bash "$SCRIPT" -c "$VERSION" . || check_exit=$?
if [[ "$check_exit" -ne 0 ]]; then pass "check mode exits non-zero while tokens remain"; else fail "check mode should exit non-zero"; fi
assert_contains src/fixture.php '$$next-version$$' "check mode leaves the file unmodified"

# --- Replace mode: resolves every token ----------------------------------------------

echo
echo "== Replace mode: stamps in $VERSION =="
bash "$SCRIPT" "$VERSION" .
echo "--- after ---"
cat src/fixture.php
echo

assert_contains src/fixture.php "@since $VERSION"                                              "@since"
assert_contains src/fixture.php "@deprecated $VERSION"                                         "@deprecated"
assert_contains src/fixture.php "@deprecated since $VERSION"                                   "@deprecated since"
assert_contains src/fixture.php "_deprecated_function( 'old_core_fn', '$VERSION', 'demo' )"    "_deprecated_function (core)"
assert_contains src/fixture.php "wc_deprecated_function( 'old_wc_fn', '$VERSION' )"            "wc_deprecated_function"
assert_contains src/fixture.php "wc_deprecated_hook( 'woocommerce_old_hook', '$VERSION'"       "wc_deprecated_hook"
assert_contains src/fixture.php "wc_deprecated_argument( 'arg', '$VERSION'"                    "wc_deprecated_argument"
assert_contains src/fixture.php "_doing_it_wrong( __METHOD__, 'Nope.', '$VERSION' )"           "_doing_it_wrong (core)"
assert_contains src/fixture.php "wc_doing_it_wrong( __METHOD__, 'Nope.', '$VERSION' )"         "wc_doing_it_wrong"
assert_contains src/fixture.php "do_action_deprecated( 'woocommerce_old_action', array(), '$VERSION'"   "do_action_deprecated"
assert_contains src/fixture.php "apply_filters_deprecated( 'woocommerce_old_filter', array( \$value ), '$VERSION' )" "apply_filters_deprecated"
assert_no_token src/fixture.php "no tokens remain after replacement"

# --- Safety net: a token in an unrecognized context is reported, not silently dropped --

echo
echo "== Safety net: a token in an unsupported context is reported =="
cat > src/unsupported.php <<'PHP'
<?php
// This bare string is not one of the recognized patterns.
$note = 'shipped in $$next-version$$';
PHP
git add -A

unsupported_exit=0
stderr_file="$(mktemp)"
CI='' bash "$SCRIPT" "$VERSION" . 2>"$stderr_file" || unsupported_exit=$?
if [[ "$unsupported_exit" -ne 0 ]]; then pass "exits non-zero when a token cannot be placed"; else fail "should exit non-zero for an unsupported token"; fi
if grep -Fq 'Unexpected `$$next-version$$` token' "$stderr_file"; then pass "reports the offending file/line"; else fail "should report the unexpected token"; fi
rm -f "$stderr_file"

# --- Result --------------------------------------------------------------------------

echo
if [[ "$FAILURES" -eq 0 ]]; then
	echo "All assertions passed."
	exit 0
else
	echo "$FAILURES assertion(s) failed."
	exit 1
fi
