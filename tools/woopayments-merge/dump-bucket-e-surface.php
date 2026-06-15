<?php
/**
 * Bucket-E payment-surface dump for parity diffing (WooPayments → core merge, A0 harness).
 *
 * Emits, for each given order, the persisted payment surface that RULE 0 requires to stay
 * byte-identical between the reference (current WooPayments) and target-native runtimes:
 * order status, payment-relevant meta, payment order notes, and refund records — all
 * stable-sorted and line-delimited JSON so reference-vs-target output can be `diff`ed.
 *
 * Run via the wrapper (stdin form works against docker / wp-env / local):
 *   WP="docker exec -i wcpay_wp_default wp" dump-bucket-e-surface.sh 12 34
 *
 * @package WooCommerce\Tools\WooPaymentsMerge
 */

// $args are the order IDs passed after the script (wp eval-file - <id>...).
if ( empty( $args ) ) {
	WP_CLI::error( 'Provide at least one order ID.' );
}

// Bucket-E payment surface: meta keys WooPayments persists on orders (design-spec §2.E / bc-manifest §3).
$key_pattern = '/(_payment_method|_payment_method_title|wcpay|woopay|woocommerce_payments|_stripe|_intent|_intention|_charge|_wcpay|exchange_rate|multi_currency|mandate|_new_order_being_scheduled|fraud)/i';

$records = array();

foreach ( $args as $order_id_arg ) {
	$order_id = (int) $order_id_arg;
	$order    = wc_get_order( $order_id );

	if ( ! $order ) {
		$records[] = array(
			'order_id' => $order_id,
			'error'    => 'not_found',
		);
		continue;
	}

	$meta = array();
	foreach ( $order->get_meta_data() as $meta_item ) {
		$data = $meta_item->get_data();
		$key  = (string) $data['key'];
		if ( preg_match( $key_pattern, $key ) ) {
			$value          = $data['value'];
			$meta[ $key ][] = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
		}
	}
	ksort( $meta );

	// Env-noise notes that are not payment behavior (local mail-transport failures differ by
	// env, not by runtime) are excluded here, at the data level, so cross-store parity does not
	// report false regressions.
	$noise_pattern = '/(failed to send|could not instantiate mail)/i';

	$notes = array();
	foreach ( wc_get_order_notes( array( 'order_id' => $order_id ) ) as $note ) {
		$content = trim( (string) $note->content );
		if ( preg_match( $noise_pattern, $content ) ) {
			continue;
		}
		$notes[] = array(
			'content'   => $content,
			'by_system' => (bool) ( 'system' === $note->added_by || 'WooCommerce' === $note->added_by ),
		);
	}
	usort(
		$notes,
		function ( $a, $b ) {
			return strcmp( $a['content'], $b['content'] );
		}
	);

	$refunds = array();
	foreach ( $order->get_refunds() as $refund ) {
		$refunds[] = array(
			'amount'   => (string) $refund->get_amount(),
			'currency' => (string) $refund->get_currency(),
			'reason'   => (string) $refund->get_reason(),
		);
	}
	usort(
		$refunds,
		function ( $a, $b ) {
			return strcmp( $a['amount'] . $a['reason'], $b['amount'] . $b['reason'] );
		}
	);

	$record = array(
		'currency'       => $order->get_currency(),
		'meta'           => $meta,
		'notes'          => $notes,
		'order_id'       => $order_id,
		'payment_method' => $order->get_payment_method(),
		'refunds'        => $refunds,
		'status'         => $order->get_status(),
		'total'          => (string) $order->get_total(),
		'transaction_id' => $order->get_transaction_id(),
	);
	ksort( $record );
	$records[] = $record;
}

foreach ( $records as $record ) {
	WP_CLI::line( wp_json_encode( $record ) );
}
