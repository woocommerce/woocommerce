#!/usr/bin/env bash
#
# Financial reconciliation harness (WooPayments → core merge, A0, design-spec §6.3 R12).
#
# Reconciles WC-side money records against the provider's raw source: for each order, the
# WC-side refund records (amount/currency) must match what Stripe actually recorded. This is
# the money-safety oracle — RULE 0 on the money path. It NEVER trusts WC's own copy alone;
# it reads the raw provider record via the Stripe CLI (the only allowed raw-source reader;
# never the remote WPCOM sandbox).
#
#   WP="docker exec -i wcpay_wp_default wp --allow-root" financial-reconcile.sh <order_id>...
#
# PRECONDITIONS (this harness fails closed if they are not met — it will not emit a false PASS):
#   1. A valid Stripe CLI session on the host: `stripe login` (the local test key expires; refresh
#      when stale). NOTE: the legacy Transact container runs its own valid in-container key for the
#      listener; this reconciler reads raw source from the HOST Stripe CLI, which is separate.
#   2. The event listener running so provider events reach the store (the operator runs these):
#        - legacy Transact env (~/Work/a8c/transact-platform-server) — the reference store at :8082
#          uses this: `npm run stripe listen`   <-- the relevant one for the reference oracle
#        - wpcom-local env (only if a store is routed to http://wpcom.localhost:30001): `transact listen`
#      Drive operations (refunds/disputes/payouts) via the dev-tools Test Lab, e.g.:
#        wp wcpay-dev test-lab refunds ...   (then re-run this reconciler on the affected order)

set -uo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WP="${WP:-wp}"
IDS=("$@")

if [ "${#IDS[@]}" -eq 0 ]; then
	echo "usage: WP='<wp runner>' financial-reconcile.sh <order_id>..." >&2
	exit 2
fi

# --- Preflight: refuse to run (and refuse to imply success) without raw-source access. ---
echo "Preflight:"
probe="$(stripe charges list --limit 1 2>&1)"
if printf '%s' "$probe" | grep -qi "expired\|no api key\|stripe login\|Invalid API Key"; then
	echo "  BLOCKED: Stripe CLI has no valid session (raw-source read unavailable):"
	printf '%s\n' "$probe" | sed 's/^/    /' | head -3
	echo
	echo "  Reconciliation cannot verify money against source. Run 'stripe login' and retry."
	echo "  (Refusing to emit a PASS from WC-side data alone — that would defeat the oracle.)"
	exit 3
fi
echo "  ok: Stripe CLI session valid."

# WooPayments charges live on the store's CONNECTED account, not the platform account, so all
# raw-source reads must carry --stripe-account. Auto-detect it from the store.
ACCT="$($WP eval 'echo WC_Payments::get_account_service()->get_stripe_account_id();' 2>/dev/null | tr -d "[:space:]")"
if [ -z "$ACCT" ]; then
	echo "  BLOCKED: could not resolve the connected Stripe account from the store." >&2
	exit 3
fi
echo "  ok: connected account $ACCT."

# --- Reconcile each order: WC-side refunds vs Stripe-side refunds for its charge. ---
fail=0
for order_id in "${IDS[@]}"; do
	# WC side: charge id + per-refund amount/currency, as line-delimited "amount|currency".
	wc_json="$($WP eval-file - "$order_id" <<'PHP' 2>/dev/null
<?php
$order = wc_get_order( (int) $args[0] );
if ( ! $order ) { WP_CLI::line( wp_json_encode( array( 'error' => 'not_found' ) ) ); return; }
$charge_id = $order->get_meta( '_charge_id' );
if ( '' === $charge_id ) { $charge_id = $order->get_transaction_id(); }
$refunds = array();
foreach ( $order->get_refunds() as $r ) {
	$refunds[] = array( 'amount' => (string) $r->get_amount(), 'currency' => strtolower( (string) $r->get_currency() ) );
}
WP_CLI::line( wp_json_encode( array( 'charge_id' => (string) $charge_id, 'refunds' => $refunds ) ) );
PHP
)"

	charge_id="$(printf '%s' "$wc_json" | sed -n 's/.*"charge_id":"\([^"]*\)".*/\1/p')"
	if [ -z "$charge_id" ]; then
		echo "  order $order_id: no charge id on WC side — skipping (not a provider-charged order)."
		continue
	fi
	# Defensive: a charge id is always [A-Za-z0-9_]; reject anything else before it reaches the CLI
	# (blocks a stray meta value being interpreted as a flag/argument). Fail-closed: can't verify => fail.
	if ! printf '%s' "$charge_id" | grep -qE '^[A-Za-z0-9_]+$'; then
		echo "  order $order_id: charge id has unexpected characters — refusing to query the provider. RECONCILE FAIL."
		fail=1
		continue
	fi

	# Stripe side: amount_refunded (in minor units) + currency for that charge, from raw source
	# (read from the connected account where WooPayments charges live).
	stripe_charge="$(stripe charges retrieve "$charge_id" --stripe-account "$ACCT" 2>&1)"
	if printf '%s' "$stripe_charge" | grep -qi "No such charge\|error"; then
		echo "  order $order_id: charge $charge_id not found at provider — RECONCILE FAIL."
		fail=1
		continue
	fi
	s_refunded_minor="$(printf '%s' "$stripe_charge" | sed -n 's/.*"amount_refunded": *\([0-9]*\).*/\1/p' | head -1)"
	s_currency="$(printf '%s' "$stripe_charge" | sed -n 's/.*"currency": *"\([a-z]*\)".*/\1/p' | head -1)"

	# WC side total refunded (sum of refund amounts) in minor units + the WC currency, as "minor currency".
	wc_line="$($WP eval-file - "$order_id" <<'PHP' 2>/dev/null
<?php
$order = wc_get_order( (int) $args[0] );
$sum = 0.0;
foreach ( $order->get_refunds() as $r ) { $sum += (float) $r->get_amount(); }
$currency = strtolower( $order->get_currency() );
// Stripe zero-decimal currencies are already in their base unit (no *100). Three-decimal
// currencies (bhd/jod/kwd/omr/tnd) are charged specially by Stripe and not handled here.
$zero_decimal = array( 'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf' );
$minor = (int) round( $sum * ( in_array( $currency, $zero_decimal, true ) ? 1 : 100 ) );
WP_CLI::line( $minor . ' ' . $currency );
PHP
)"
	wc_refunded="${wc_line%% *}"
	wc_currency="${wc_line##* }"

	if [ "${wc_refunded:-0}" = "${s_refunded_minor:-0}" ] && [ "${wc_currency:-?}" = "${s_currency:-??}" ]; then
		echo "  order $order_id (charge $charge_id): refunded ${wc_refunded:-0} ${wc_currency:-?} — WC matches provider. ok"
	else
		echo "  order $order_id (charge $charge_id): MISMATCH — WC=${wc_refunded:-0} ${wc_currency:-?} provider=${s_refunded_minor:-0} ${s_currency:-??}. RECONCILE FAIL"
		fail=1
	fi
done

echo
if [ "$fail" -ne 0 ]; then
	echo "FAIL: financial reconciliation found WC↔provider divergence (RULE 0 money-path)."
	exit 1
fi
echo "PASS: WC-side money records match the provider's raw source for all reconciled orders."
exit 0
