#!/usr/bin/env bash
#
# Flow driver (WooPayments → core merge verification harness).
#
# The reusable layer that EXERCISES a payments surface deterministically and emits the
# affected order id(s) as structured output, so the gates (parity-diff / financial-reconcile /
# tracks-parity) have known fixtures to verify. It drives real operations through the dev-tools
# Test Lab (which calls the actual gateway: WC_Payment_Gateway_WCPay::process_payment / refund),
# so the orders + Stripe objects are linked exactly as a real flow would link them.
#
#   WP="docker exec -i wcpay_wp_default wp --allow-root" flow-drive.sh charge  --count=1 --type=success
#   WP="..."                                             flow-drive.sh refund  --type=partial
#   WP="..."                                             flow-drive.sh dispute
#   WP="..."                                             flow-drive.sh payout
#
# Output: one JSON object per line, e.g. {"op":"charge","order_id":348,"charge_id":"ch_..."}.
# Capture the order ids and feed them to the gates:
#   ids=$(flow-drive.sh charge | sed -n 's/.*"order_id":\([0-9]*\).*/\1/p')
#   parity-diff.sh --self-check "$WP" $ids
#
# PRECONDITIONS: a connected test account + the event listener running (operator-run) so the
# provider events propagate. See HARNESS.md. Local-only; never the remote WPCOM sandbox.

set -uo pipefail

WP="${WP:-wp}"
OP="${1:-}"
shift || true

if [ -z "$OP" ]; then
	echo "usage: WP='<wp runner>' flow-drive.sh <charge|refund|dispute|payout> [--count=N] [--type=...]" >&2
	exit 2
fi

# Map the singular op to the Test Lab's plural subcommand.
case "$OP" in
	charge)  SUB="charges" ;;
	refund)  SUB="refunds" ;;
	dispute) SUB="disputes" ;;
	payout)  SUB="payouts" ;;
	*) echo "Unknown op: $OP (use charge|refund|dispute|payout)" >&2; exit 2 ;;
esac

# Drive the operation through the Test Lab and capture its JSON.
# shellcheck disable=SC2086
raw="$($WP wcpay-dev test-lab "$SUB" --format=json "$@" 2>&1)"
rc=$?

# Strip WP notices/log lines so only the JSON payload remains.
json="$(printf '%s\n' "$raw" | grep -vE 'textdomain|Debugging in WordPress|_load_textdomain|^Creating ')"

if [ "$rc" -ne 0 ] || [ -z "$json" ]; then
	echo "FLOW-DRIVE FAIL ($OP): the Test Lab command did not complete." >&2
	printf '%s\n' "$raw" | tail -5 >&2
	exit 1
fi

# Emit one structured line per affected order. order_id is the join key the gates consume;
# charge_id is included when present (charge flow). Multiple order ids → multiple lines.
printf '%s\n' "$json" \
	| grep -oE '"order_id":[[:space:]]*[0-9]+' \
	| grep -oE '[0-9]+' \
	| while read -r oid; do
		charge="$(printf '%s\n' "$json" | grep -oE '"charge_id":[[:space:]]*"[^"]*"' | head -1 | sed -E 's/.*"([^"]*)"$/\1/')"
		if [ -n "$charge" ]; then
			printf '{"op":"%s","order_id":%s,"charge_id":"%s"}\n' "$OP" "$oid" "$charge"
		else
			printf '{"op":"%s","order_id":%s}\n' "$OP" "$oid"
		fi
	done

exit 0
