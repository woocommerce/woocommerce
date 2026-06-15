#!/usr/bin/env bash
#
# BC-manifest drift gate (WooPayments → WooCommerce core merge, plan stage A0).
#
# Re-runs the backward-compatibility extraction commands (the "Regeneration
# Commands" blocks in ai-prompts/.../follow-up/bc-extraction/*.md) against the
# LIVE WooPayments source, normalizes the result into a stable per-category
# surface signature, and diffs it against a committed baseline. Any undispositioned
# add/remove of a BC surface line fails the gate.
#
# This is a DRIFT gate, not a re-extraction: the human-readable inventories in
# bc-extraction/*.md remain the disposition record; this script's baseline is the
# machine-diffable snapshot the gate compares against.
#
# Usage:
#   bc-drift-gate.sh            # check mode: exit 1 on any drift (CI + local gate)
#   bc-drift-gate.sh --update   # capture/refresh the baseline (disposition step)
#
# Source location (override in CI):
#   WCPAY_SRC=/path/to/woocommerce-payments bc-drift-gate.sh
#
# Normalization drops the absolute path prefix and line numbers (which churn on
# every edit) but keeps "relative_file: matched content", so moving a line within
# a file does not drift, while adding/removing/relocating a BC surface does.

set -uo pipefail

SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SELF_DIR/../.." && pwd)"
WCPAY_SRC="${WCPAY_SRC:-$REPO_ROOT/../woocommerce-payments}"
BASELINE_DIR="$SELF_DIR/bc-drift-baseline"

MODE="check"
if [ "${1:-}" = "--update" ]; then
	MODE="update"
elif [ -n "${1:-}" ]; then
	echo "Unknown argument: $1 (use --update or no argument)" >&2
	exit 2
fi

if [ ! -d "$WCPAY_SRC/includes" ]; then
	echo "ERROR: WooPayments source not found at: $WCPAY_SRC" >&2
	echo "       Set WCPAY_SRC to the woocommerce-payments checkout." >&2
	exit 2
fi

INC="$WCPAY_SRC/includes"
SRC="$WCPAY_SRC/src"
CLIENT="$WCPAY_SRC/client"
ROOTFILE="$WCPAY_SRC/woocommerce-payments.php"
SCAN="$INC $SRC"

# Strip the source path prefix and ":<line>:" so the signature is churn-stable, then sort-unique.
normalize() {
	sed -E "s#${WCPAY_SRC}/?##g" \
		| sed -E 's/^([^:]+):[0-9]+:[[:space:]]*/\1: /' \
		| sed -E 's/[[:space:]]+$//' \
		| grep -v -e '^[[:space:]]*$' \
		| LC_ALL=C sort -u
}

# --- Category probes: faithful to bc-extraction/*.md regeneration commands. ---
# `head` limits from the docs are intentionally dropped here (the gate needs the full surface).

probe_scheduler() {
	{
		grep -rHn "GROUP_ID\|group_id\|'woocommerce_payments'\|\"woocommerce_payments\"" $SCAN --include="*.php"
		grep -rHn "as_schedule_single_action\|as_schedule_recurring_action\|as_enqueue_async_action\|schedule_job\|as_unschedule_action\|as_unschedule_all_actions\|as_cancel_action" $SCAN --include="*.php"
		grep -rHn "wcpay_\|'woocommerce_payments_" $SCAN --include="*.php" | grep "schedule\|as_enqueue\|as_schedule\|add_action"
		grep -rHn "wp_schedule_event\|wp_schedule_single_event\|wp_unschedule_event\|wp_clear_scheduled_hook\|wp_next_scheduled\|wp_unschedule_hook" $SCAN --include="*.php"
		grep -rHn "add_action.*wcpay_\|add_action.*woocommerce_payments_" $SCAN --include="*.php"
		grep -rHn "const.*HOOK\|const.*ACTION\|const.*EVENT" $SCAN --include="*.php" | grep -i "wcpay\|woocommerce_pay"
		grep -rHn "wcpay_failed_event\|failed_webhook\|failed_event" $SCAN --include="*.php"
	} 2>/dev/null | grep -v vendor | grep -v '/tests/' | normalize
}

probe_php_api() {
	{
		grep -Hn "public static function" "$INC/class-wc-payments.php"
		grep -rHn "^function " "$INC" "$SRC" "$ROOTFILE" --include="*.php"
		grep -rHn 'GATEWAY_ID\|const TYPE\|protected \$type' "$INC" --include="*.php"
		grep -rHn "get_id()\|get_stripe_payment_method_type" "$INC/payment-methods/Configs/Definitions/" --include="*.php"
		grep -rHn "implements.*MultiCurrency" "$INC" --include="*.php"
		grep -rHn "WC_Payments::\|WC_Payment_Gateway_WCPay::\|WC_Payments_Account::\|WC_Payments_Customer_Service::\|WC_Payments_Order_Service::\|WC_Payments_Token_Service::" "$INC/subscriptions/" --include="*.php"
		grep -Hn "const.*META_KEY\|const.*OPTION\|const.*KEY" "$INC/class-wc-payments-order-service.php"
		grep -rHn "class.*Exception\|class.*_Exception" "$INC" --include="*.php"
	} 2>/dev/null | grep -v "vendor/\|/tests/\|/vendor\b" | normalize
}

probe_persisted_data() {
	{
		grep -rHn "update_meta_data\|add_meta_data\|get_meta\|delete_meta_data" "$INC" "$SRC" --include="*.php"
		grep -rHn "update_post_meta\|get_post_meta\|add_post_meta\|delete_post_meta" "$INC" "$SRC" --include="*.php"
		grep -rHn "update_user_meta\|get_user_meta\|add_user_meta\|delete_user_meta\|update_user_option\|get_user_option" "$INC" "$SRC" --include="*.php"
		grep -rHn "get_option\|update_option\|add_option\|delete_option" "$INC" "$SRC" --include="*.php" | grep -iE "wcpay|woopay|woocommerce_payments|woocommerce_woopayments|nox_profile|nox_lock|platform_checkout"
		grep -rHn "set_transient\|get_transient\|delete_transient" "$INC" "$SRC" --include="*.php"
		grep -rHn "WC()->session\|->session->set\|->session->get\|->session->has\|->session->__unset" "$INC" "$SRC" --include="*.php"
		grep -Hn "^	const " "$INC/class-database-cache.php"
		grep -rHn "^	const .*META_KEY\|^	const .*OPTION\|^	const .*SESSION_KEY\|^	const .*TRANSIENT\|^	const .*KEY\b" "$INC" "$SRC" --include="*.php" | grep -iE "wcpay|woopay|stripe|intent|charge|currency|invoice|subscription|product_id|product_price"
	} 2>/dev/null | grep -v "vendor\|/tests\|node_modules" | normalize
}

probe_endpoints() {
	{
		grep -rHn "register_rest_route(" "$INC/" "$SRC/" --include="*.php"
		grep -rHn "add_action.*wp_ajax" "$INC/" "$SRC/" --include="*.php"
		grep -rHn "add_action.*wc_ajax\|add_action.*woocommerce_api_" "$INC/" "$SRC/" --include="*.php"
		grep -rHn "woocommerce_store_api_register\|ExtendSchema\|ExtendRestApi\|register_endpoint_data\|register_update_callback\|register_payment_requirements" "$INC/" "$SRC/" --include="*.php"
		grep -rHn "AbstractPaymentMethodType\|woocommerce_blocks_payment_method_type_registration" "$INC/" "$SRC/" --include="*.php"
	} 2>/dev/null | grep -v "vendor/\|/tests/" | normalize
}

probe_hooks_filters() {
	{
		grep -rHn --include="*.php" -E "(do_action|do_action_deprecated|apply_filters|apply_filters_deprecated)\s*\(\s*['\"]?(wcpay_|wc_payments_|woocommerce_payments_|woopay_|wcpay_multi_currency_|__experimental_woocommerce_[a-z_]*payments[a-z_]*)" "$INC/" "$SRC/"
		grep -rHn --include="*.php" -E "protected\s+\\\$hook\s*=\s*['\"]" "$INC/core/server/request/"
		grep -rHn --include="*.php" "wcpay_.*_format" "$INC/class-wc-payments-localization-service.php"
		grep -rHn --include="*.php" -E "(do_action_deprecated|apply_filters_deprecated)" "$INC/" "$SRC/"
	} 2>/dev/null | grep -v "vendor/\|/tests/" | normalize
}

# Tracks / telemetry emitters (bc-extraction/tracks-events.md) — the non-negotiable telemetry
# contract (bc-manifest §0.3/§3.6). Static name-level drift only; prop-level drift is the runtime
# parity harness's job. Captures PHP recorders + JS recorders + the server-side event-name constants.
probe_tracks() {
	{
		grep -rHnE "Tracker::track[a-z_]*\(|record_tracks_event\(|wc_admin_record_tracks_event\(" "$INC" "$SRC" --include="*.php"
		grep -rHn "const " "$INC/constants/class-track-events.php"
		grep -rHnE "record(User)?Event\(|wcpayTracks|wcTracks\.recordEvent" "$CLIENT" --include="*.js" --include="*.jsx" --include="*.ts" --include="*.tsx"
	} 2>/dev/null | grep -vE "vendor|/tests/|__tests__|\.spec\." | normalize
}

CATEGORIES="scheduler php_api persisted_data endpoints hooks_filters tracks"

echo "BC-manifest drift gate"
echo "  source:   $WCPAY_SRC"
echo "  baseline: $BASELINE_DIR"
echo "  mode:     $MODE"
echo

fail=0
for cat in $CATEGORIES; do
	out="$("probe_$cat")"
	count="$(printf '%s\n' "$out" | grep -c . )"
	base="$BASELINE_DIR/$cat.txt"

	if [ "$MODE" = "update" ]; then
		mkdir -p "$BASELINE_DIR"
		printf '%s\n' "$out" > "$base"
		printf '  updated %-16s %5s lines\n' "$cat" "$count"
		continue
	fi

	if [ ! -f "$base" ]; then
		echo "  MISSING baseline: $cat (run --update to capture)"
		fail=1
		continue
	fi

	# "<" = present in live source but not baseline (NEW surface, needs disposition).
	# ">" = present in baseline but gone from live source (REMOVED surface).
	d="$(diff <(printf '%s\n' "$out") "$base")"
	if [ -n "$d" ]; then
		echo "  DRIFT  $cat ($count live lines):"
		printf '%s\n' "$d" | sed 's/^/      /'
		fail=1
	else
		printf '  ok     %-16s %5s lines\n' "$cat" "$count"
	fi
done

echo
if [ "$MODE" = "update" ]; then
	echo "Baseline captured. Commit $BASELINE_DIR and disposition any new rows in bc-extraction/*.md + bc-manifest.md."
	exit 0
fi

if [ "$fail" -ne 0 ]; then
	echo "FAIL: BC surface drift detected. Disposition each row (PRESERVE/FACADE/REDESIGN/EXTRACT/DROP)"
	echo "      in bc-manifest.md, then re-run with --update to accept the new baseline."
	exit 1
fi

echo "PASS: no undispositioned BC surface drift."
exit 0
