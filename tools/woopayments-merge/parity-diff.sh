#!/usr/bin/env bash
#
# Bucket-E parity differ (WooPayments → core merge, A0 verification harness).
#
# Captures the Bucket-E payment surface (status/meta/notes/refunds) for the same order
# IDs from two stores and asserts ZERO diff on the PRESERVE surface — the executable
# form of RULE 0 (no merchant-facing regression). Everything the Bucket-E dump emits is
# a PRESERVE row, so "diff" == "regression". Exit 1 on any diff.
#
# Two modes:
#   Self-check (A0): prove the differ agrees with reality on the UNMODIFIED plugin —
#     dump the same store twice; must be zero diff. This is the A0 harness trust gate.
#       parity-diff.sh --self-check "docker exec -i wcpay_wp_default wp --allow-root" 346 344
#
#   Cross-store (A1 shadow mode onward): reference (current plugin) vs target (native).
#       parity-diff.sh \
#         --ref    "docker exec -i wcpay_wp_default wp --allow-root" \
#         --target "docker exec -i <core-cli> wp" \
#         346 344
#
# Env noise that is NOT payment behavior is filtered before diffing (e.g. local mail-send
# failure notes), so two stores with different mail config don't report false regressions.

set -uo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DUMP="$SELF_DIR/dump-bucket-e-surface.sh"

REF_WP=""
TARGET_WP=""
SELF_WP=""
IDS=()

while [ "$#" -gt 0 ]; do
	case "$1" in
		--ref) REF_WP="$2"; shift 2 ;;
		--target) TARGET_WP="$2"; shift 2 ;;
		--self-check) SELF_WP="$2"; shift 2 ;;
		--) shift; break ;;
		-*) echo "Unknown flag: $1" >&2; exit 2 ;;
		*) IDS+=("$1"); shift ;;
	esac
done
IDS+=("$@")

if [ "${#IDS[@]}" -eq 0 ]; then
	echo "usage: parity-diff.sh (--self-check WP | --ref WP --target WP) <order_id>..." >&2
	exit 2
fi

# Env-noise (local mail-transport failure notes) is excluded inside the dump itself, at the
# data level — never as a line-grep here, since each order is a single JSON line and a
# text filter would drop the whole record, not just the noisy note.
dump() {
	WP="$1" bash "$DUMP" "${IDS[@]}" 2>/dev/null
}

if [ -n "$SELF_WP" ]; then
	LEFT_LABEL="run-1"; RIGHT_LABEL="run-2"
	left="$(dump "$SELF_WP")"
	right="$(dump "$SELF_WP")"
else
	if [ -z "$REF_WP" ] || [ -z "$TARGET_WP" ]; then
		echo "Cross-store mode needs both --ref and --target." >&2
		exit 2
	fi
	LEFT_LABEL="reference"; RIGHT_LABEL="target-native"
	left="$(dump "$REF_WP")"
	right="$(dump "$TARGET_WP")"
fi

if [ -z "$left" ]; then
	echo "FAIL: $LEFT_LABEL produced no surface output (store unreachable or no orders)." >&2
	exit 2
fi

d="$(diff <(printf '%s\n' "$left") <(printf '%s\n' "$right"))"
if [ -n "$d" ]; then
	echo "FAIL: Bucket-E parity diff ($LEFT_LABEL vs $RIGHT_LABEL) — RULE 0 regression on PRESERVE surface:"
	printf '%s\n' "$d" | sed 's/^/    /'
	echo
	echo "  '<' = $LEFT_LABEL only · '>' = $RIGHT_LABEL only"
	exit 1
fi

echo "PASS: zero Bucket-E parity diff across ${#IDS[@]} order(s) ($LEFT_LABEL vs $RIGHT_LABEL)."
exit 0
