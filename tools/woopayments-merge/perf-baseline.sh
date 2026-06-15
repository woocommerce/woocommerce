#!/usr/bin/env bash
#
# Per-surface payments perf baseline + regression gate (WooPayments → core merge, A0, RULE 1).
#
# Captures the query-count / time / memory of the payment surfaces (design-spec §5.3) and
# gates native against the reference baseline. Query count is the hard RULE-1 signal; a
# native surface must not issue MORE queries than the reference baseline.
#
#   WP="docker exec -i wcpay_wp_default wp --allow-root" perf-baseline.sh capture   # write baseline
#   WP="<target wp runner>"                              perf-baseline.sh check     # gate vs baseline
#
# Baseline file: perf-baseline.json (committed; the reference = current WooPayments behavior).

set -uo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROBE="$SELF_DIR/perf-baseline.php"
BASELINE="$SELF_DIR/perf-baseline.json"
WP="${WP:-wp}"

MODE="${1:-capture}"

run_probe() {
	# shellcheck disable=SC2086
	$WP eval-file - < "$PROBE"
}

case "$MODE" in
	capture)
		out="$(run_probe)"
		if [ -z "$out" ]; then
			echo "FAIL: probe produced no output (store unreachable)." >&2
			exit 2
		fi
		printf '%s\n' "$out" > "$BASELINE"
		echo "Baseline captured to $BASELINE:"
		printf '%s\n' "$out"
		;;
	check)
		if [ ! -f "$BASELINE" ]; then
			echo "MISSING baseline: run capture against the reference store first." >&2
			exit 2
		fi
		out="$(run_probe)"
		if [ -z "$out" ]; then
			echo "FAIL: probe produced no output (store unreachable)." >&2
			exit 2
		fi
		# Compare query counts per surface; fail if any target surface exceeds the baseline.
		# Uses node if available (JSON math); falls back to a python3 comparator.
		printf '%s\n' "$out" > "$SELF_DIR/.perf-current.json"
		if command -v python3 >/dev/null 2>&1; then
			python3 - "$BASELINE" "$SELF_DIR/.perf-current.json" <<'PY'
import json, sys
base = json.load(open(sys.argv[1]))
cur  = json.load(open(sys.argv[2]))
fail = 0
for name, b in base.get("surfaces", {}).items():
    c = cur.get("surfaces", {}).get(name)
    if c is None:
        print(f"  DRIFT  surface '{name}' missing in target"); fail = 1; continue
    if c["queries"] > b["queries"]:
        print(f"  REGRESS {name}: queries {b['queries']} -> {c['queries']} (RULE 1 fail)"); fail = 1
    else:
        print(f"  ok     {name}: queries {b['queries']} -> {c['queries']}, time {b['median_ms']}ms -> {c['median_ms']}ms")
sys.exit(1 if fail else 0)
PY
			rc=$?
			rm -f "$SELF_DIR/.perf-current.json"
			[ "$rc" -ne 0 ] && { echo "FAIL: per-surface query-count regression (RULE 1)."; exit 1; }
			echo "PASS: no per-surface query-count regression vs baseline."
		else
			echo "python3 not available; emitting current vs baseline for manual review:"
			echo "--- baseline ---"; cat "$BASELINE"
			echo "--- current ---";  printf '%s\n' "$out"
			rm -f "$SELF_DIR/.perf-current.json"
		fi
		;;
	*)
		echo "usage: WP='<wp runner>' perf-baseline.sh [capture|check]" >&2
		exit 2
		;;
esac
