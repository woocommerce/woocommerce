#!/usr/bin/env bash
#
# Tracks-parity differ (WooPayments → core merge verification harness).
#
# The runtime half of the telemetry-continuity contract (bc-manifest §0.3/§3.6): asserts the
# {event name, props contract} captured on one runtime matches the other. Pairs with
# `tracks-capture.php` (server-side drop-in) and the Playwright spy (client-side; see HARNESS.md).
#
# Normalization encodes the §0.3 boundary judgment — it freezes what consumers depend on and
# ignores what would wedge us:
#   - strips the WC Tracks name prefix (wcadmin_) so names compare cleanly;
#   - drops the auto-injected envelope (keys starting with '_', blog_id/_via/_ts/user_lang/…);
#   - keeps each remaining prop's KEY + TYPE and its value IF the value is a stable enum string;
#   - MASKS volatile values (numbers, ids like ch_/pi_/cus_/pm_…, timestamps) to a type token,
#     so two runs of the same flow (different order ids) still match on the contract.
#
# Sub-commands (drive a flow between reset and normalize):
#   WP="<runner>" tracks-parity.sh reset                 # truncate the capture on a store
#   WP="<runner>" tracks-parity.sh normalize > out.txt   # read+normalize the store's capture
#   tracks-parity.sh diff a.txt b.txt                    # diff two normalized captures; exit 1 on drift
#
# Typical A1 cross-store use (the orchestrator wires this):
#   WP=$REF tracks-parity.sh reset; <drive flow on ref>; WP=$REF tracks-parity.sh normalize > ref.txt
#   WP=$TGT tracks-parity.sh reset; <drive same flow on tgt>; WP=$TGT tracks-parity.sh normalize > tgt.txt
#   tracks-parity.sh diff ref.txt tgt.txt

set -uo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP="${WP:-wp}"
CMD="${1:-}"
CAPTURE_PHP='@readfile( ( defined("WP_CONTENT_DIR") ? WP_CONTENT_DIR : ABSPATH."wp-content" ) . "/uploads/wcpay-tracks-capture.jsonl" );'
RESET_PHP='$f=( defined("WP_CONTENT_DIR") ? WP_CONTENT_DIR : ABSPATH."wp-content" )."/uploads/wcpay-tracks-capture.jsonl"; @unlink($f); echo "reset";'

# Normalizer lives in its own file so the piped capture stays on stdin (a heredoc-script would
# steal stdin). Encodes the §0.3 boundary judgment.
normalize_stdin() {
	python3 "$SELF_DIR/tracks-normalize.py"
}

case "$CMD" in
	reset)
		# shellcheck disable=SC2086
		$WP eval "$RESET_PHP" 2>/dev/null | grep -q reset && echo "tracks capture reset" || echo "tracks capture reset (no prior file)"
		;;
	normalize)
		if ! command -v python3 >/dev/null 2>&1; then
			echo "python3 required for normalization" >&2; exit 2
		fi
		# shellcheck disable=SC2086
		$WP eval "$CAPTURE_PHP" 2>/dev/null | normalize_stdin
		;;
	diff)
		A="${2:-}"; B="${3:-}"
		if [ -z "$A" ] || [ -z "$B" ] || [ ! -f "$A" ] || [ ! -f "$B" ]; then
			echo "usage: tracks-parity.sh diff <normalized-a> <normalized-b>" >&2; exit 2
		fi
		d="$(diff "$A" "$B")"
		if [ -n "$d" ]; then
			echo "FAIL: Tracks contract drift (name/props) — telemetry continuity break (bc-manifest §0.3):"
			printf '%s\n' "$d" | sed 's/^/    /'
			echo "  '<' = $A only · '>' = $B only"
			exit 1
		fi
		echo "PASS: zero Tracks contract drift ($(wc -l < "$A" | tr -d ' ') events)."
		;;
	*)
		echo "usage: WP='<runner>' tracks-parity.sh <reset|normalize|diff a b>" >&2
		exit 2
		;;
esac
