#!/usr/bin/env bash
#
# Harness orchestrator (WooPayments → core merge). ONE entry point that runs the full
# verification loop and reports a per-gate verdict + an aggregate exit code, so an autonomous
# implementor (or CI) can loop on it with no human in the loop.
#
# Two modes:
#   verify.sh --self-check "<WP>"               # A0: prove the harness agrees with reality on the
#                                               #     UNMODIFIED plugin (the trust gate). Default store: reference.
#   verify.sh --ref "<WP>" --target "<WP>"      # A1+: cross-store parity (reference plugin vs target native)
#
# Flags:
#   --with-tracks    also run the Tracks-parity gate (installs the capture drop-in + enables
#                    tracking on the store(s), then restores). Off by default since it mutates state.
#
# Exit: 0 only if every gate PASSED. Any FAIL or BLOCKED → non-zero (so a CI loop stops).
# PRECONDITIONS (see HARNESS.md): connected test account, event listener running, valid host
# `stripe login` for the financial gate. Local-only; never the remote WPCOM sandbox.

set -uo pipefail
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

MODE=""
REF_WP=""
TARGET_WP=""
WITH_TRACKS=0
while [ "$#" -gt 0 ]; do
	case "$1" in
		--self-check) MODE="self"; REF_WP="$2"; TARGET_WP="$2"; shift 2 ;;
		--ref) MODE="cross"; REF_WP="$2"; shift 2 ;;
		--target) TARGET_WP="$2"; shift 2 ;;
		--with-tracks) WITH_TRACKS=1; shift ;;
		*) echo "Unknown arg: $1" >&2; exit 2 ;;
	esac
done
if [ -z "$MODE" ] || [ -z "$REF_WP" ] || [ -z "$TARGET_WP" ]; then
	echo "usage: verify.sh (--self-check WP | --ref WP --target WP) [--with-tracks]" >&2
	exit 2
fi

PASS=(); FAILED=(); BLOCKED=()
record() { # $1 gate, $2 status(PASS|FAIL|BLOCKED)
	case "$2" in
		PASS) PASS+=("$1") ;;
		FAIL) FAILED+=("$1") ;;
		*)    BLOCKED+=("$1") ;;
	esac
	printf '  [%-7s] %s\n' "$2" "$1"
}

# Run a gate command; classify by exit code (0 PASS, 2/3 BLOCKED-preconditions, else FAIL).
gate() { # $1 label ; $2.. command
	local label="$1"; shift
	local out rc
	out="$("$@" 2>&1)"; rc=$?
	if [ "$rc" -eq 0 ]; then record "$label" PASS
	elif [ "$rc" -eq 2 ] || [ "$rc" -eq 3 ]; then record "$label" BLOCKED; printf '%s\n' "$out" | tail -2 | sed 's/^/      /'
	else record "$label" FAIL; printf '%s\n' "$out" | tail -4 | sed 's/^/      /'
	fi
}

echo "WooPayments-merge verification loop — mode: $MODE"
echo

# 1. BC + Tracks static drift gate (source-level; independent of stores).
gate "drift gate (BC + tracks)" bash "$SELF_DIR/bc-drift-gate.sh"

# 2. Drive a fixture flow on the reference store and collect order ids.
echo "  driving a charge fixture on the reference store..."
IDS="$(WP="$REF_WP" bash "$SELF_DIR/flow-drive.sh" charge --count=1 --type=success 2>/dev/null | sed -n 's/.*"order_id":\([0-9]*\).*/\1/p' | tr '\n' ' ')"
if [ -z "$IDS" ]; then
	record "flow-drive (charge)" FAIL
else
	record "flow-drive (charge) -> orders: $IDS" PASS
fi

# 3. Bucket-E parity on the fixture orders.
if [ -n "$IDS" ]; then
	if [ "$MODE" = "self" ]; then
		# shellcheck disable=SC2086
		gate "Bucket-E parity (self-check)" bash "$SELF_DIR/parity-diff.sh" --self-check "$REF_WP" $IDS
	else
		# shellcheck disable=SC2086
		gate "Bucket-E parity (cross-store)" bash "$SELF_DIR/parity-diff.sh" --ref "$REF_WP" --target "$TARGET_WP" $IDS
	fi
fi

# 4. Per-surface perf (RULE 1). Self-check compares the reference to its own baseline.
gate "perf (query-count RULE 1)" env WP="$TARGET_WP" bash "$SELF_DIR/perf-baseline.sh" check

# 5. Financial reconciliation on the fixture orders (money oracle; self-gates on the Stripe session).
if [ -n "$IDS" ]; then
	# shellcheck disable=SC2086
	gate "financial reconciliation" env WP="$TARGET_WP" bash "$SELF_DIR/financial-reconcile.sh" $IDS
fi

# 6. Tracks-parity (opt-in; mutates store state, so it sets up + tears down).
if [ "$WITH_TRACKS" -eq 1 ]; then
	echo "  (tracks gate: install drop-in + enable tracking on the store(s), drive, diff, restore — see HARNESS.md)"
	record "tracks parity (run via HARNESS.md recipe)" BLOCKED
fi

echo
echo "Summary: ${#PASS[@]} passed, ${#FAILED[@]} failed, ${#BLOCKED[@]} blocked."
if [ "${#FAILED[@]}" -ne 0 ]; then echo "RESULT: FAIL (regressions present)."; exit 1; fi
if [ "${#BLOCKED[@]}" -ne 0 ]; then echo "RESULT: INCOMPLETE (preconditions unmet — not a regression)."; exit 3; fi
echo "RESULT: PASS — the deterministic gates pass on the captured surfaces (Tier A/B)."
echo "  NOTE: this is NOT full merge verification. Browser checkout (incl. 3DS/SCA), client-side"
echo "  Tracks, broad perf, and bundle size are RUNBOOK/JUDGMENT, not gated here — see HARNESS.md."
exit 0
