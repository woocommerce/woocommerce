#!/usr/bin/env bash
#
# Tracks-parity differ (WooPayments → core merge verification harness).
#
# The runtime half of the telemetry-continuity contract (bc-manifest §0.3/§3.6): asserts the
# {event name, props contract} captured for one runtime matches the other. Captures at the
# **wpcom-local Tracks sink** — the local endpoint every store's Tracks (client `browser_tkq` AND
# server `server_pixel`) is posted to via the `wpcom-local-helper` bridge. This is the actual
# pipeline destination, so it is complete (client + server) — strictly better than capturing at
# individual emitters. Requires: the helper active on the store, and the wpcom-local sink enabled.
#
# Normalization (tracks-normalize.py) encodes the §0.3 boundary judgment: freeze event name + prop
# keys/types + stable enum string values + WCPay's deliberate custom props; drop the auto-injected
# envelope and mask volatile values (ids, uuids, versions, timestamps). Synthetic sink sources
# (mock/helper_smoke) are excluded.
#
# The sink is shared by all stores in the wpcom-local checkout, so capture ONE store at a time:
# reset → drive that store's flow → normalize. Sub-commands:
#   tracks-parity.sh reset                  # clear the sink (wpcom-local tracks clear)
#   tracks-parity.sh normalize > out.txt    # read+normalize the sink's current captures
#   tracks-parity.sh diff a.txt b.txt       # diff two normalized captures; exit 1 on drift
#
# Cross-store use (the A4 gate — reference vs target):
#   tracks-parity.sh reset; <drive flow on :8082 reference>; tracks-parity.sh normalize > ref.txt
#   tracks-parity.sh reset; <drive same flow on :8889 target>; tracks-parity.sh normalize > tgt.txt
#   tracks-parity.sh diff ref.txt tgt.txt

set -uo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WLOCAL="${WLOCAL:-wpcom-local}"
CMD="${1:-}"

# Locate the sink NDJSON store (the supported way: ask the CLI).
sink_path() {
	$WLOCAL tracks path 2>/dev/null | grep -oE '/[^ ]*tracks-events\.ndjson' | head -1
}

case "$CMD" in
	reset)
		$WLOCAL tracks clear 2>&1 | grep -qiE "cleared|success" && echo "tracks sink cleared" || { echo "FAIL: could not clear the sink (is wpcom-local healthy?)" >&2; exit 2; }
		;;
	normalize)
		shift  # drop "normalize"; remaining args (e.g. --store <id>) pass to the normalizer
		if ! command -v python3 >/dev/null 2>&1; then
			echo "python3 required for normalization" >&2; exit 2
		fi
		ndjson="$(sink_path)"
		if [ -z "$ndjson" ] || [ ! -f "$ndjson" ]; then
			echo "FAIL: could not locate the sink store (wpcom-local tracks path). Is the sink enabled?" >&2
			exit 2
		fi
		python3 "$SELF_DIR/tracks-normalize.py" "$@" < "$ndjson"
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
		echo "usage: WLOCAL='<wpcom-local cmd>' tracks-parity.sh <reset|normalize|diff a b>" >&2
		exit 2
		;;
esac
