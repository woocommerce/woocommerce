<?php
/**
 * WooCommerce Order Step Logging Functions
 *
 * Tracks the steps of the checkout process for place order debugging.
 *
 * @package WooCommerce\Functions
 * @version 9.7.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Log an order-related message. This is not public API and should not be used by plugins or themes.
 *
 * @param string     $message Message to log.
 * @param array|null $context Optional. Additional information for log handlers.
 * @param bool       $final_step Optional. Whether this is the final step of the order logging, and should clear the log.
 * @param bool       $first_step Optional. Whether this is declared the first step in order to start a new log.
 *
 * @internal This function is intended for internal use only.
 * @since 9.9.0
 */
function wc_log_order_step( string $message, ?array $context = null, bool $final_step = false, bool $first_step = false ): void {

	try {
		if ( empty( $message ) ) {
			return; // Nothing to log.
		}

		static $logging_active;

		if ( $first_step ) {
			$logging_active = true;
		}

		if ( ! $logging_active ) {
			return; // Whenever the method is called without a started logging session, it will be ignored.
		}

		static $logger, $order_uid, $order_uid_short, $store_url;
		static $steps = array(); // Static array to store the messages and validate against unique messages before clearing the log.

		// Generate a static place order unique ID for logging purposes. When this is called multiple times in the same request,
		// the same UID will be used, enabling us to track recursion and race-condition issues on order processing methods
		// or other problems related to third-party plugins and filters.
		$order_uid       = $order_uid ? $order_uid : wp_generate_uuid4();
		$order_uid_short = $order_uid_short ? $order_uid_short : substr( $order_uid, 0, 8 );
		$store_url       = $store_url ? $store_url : get_site_url();

		$context['order_uid'] = $order_uid;
		$context['source']    = 'place-order-debug-' . $order_uid_short; // Source is segmented per order unique id.
		$context['store_url'] = $store_url;

		// Extract safe data from order object.
		if ( isset( $context['order_object'] ) && $context['order_object'] instanceof WC_Order ) {
			$context = array_merge( extract_order_safe_data( $context['order_object'] ), $context );
			unset( $context['order_object'] ); // This is super-important to avoid logging sensitive data.
		}

		if ( ! $logger ) {
			// Use a static logger instance to avoid unnecessary instantiations.
			$logger = new WC_Logger( null, WC_Log_Levels::DEBUG );
		}

		if ( ! is_null( error_get_last() ) ) {
			$context['last_error'] = error_get_last();
		}
		$context['backtrace']      = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 3 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$context['remote-logging'] = false; // forcing disable on remote logging.

		$steps[] = $message;
		// Logging the place order flow step. Log files are grouped per order to make is easier to navigate.
		$logger->log( WC_Log_Levels::DEBUG, $message, $context );

		// Clears the log if instructed and all steps are unique.
		if ( $final_step && count( array_unique( $steps ) ) === count( $steps ) ) {
			$logger->clear( $context['source'], true );
		}
	} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		// Since this runs in a critical path, we need to catch any exceptions and ignore them.
	}
}

/**
 * Extract safe data from order object for debug temporary logging.
 *
 * @param WC_Order $order Order object.
 *
 * @internal This function is intended for internal use only.
 * @since 9.9.0
 */
function extract_order_safe_data( WC_Order $order ) {
	$order_data = array(
		'order_id'       => $order->get_id(),
		'payment_method' => $order->get_payment_method_title(),
		'billing'        => array(
			'country' => $order->get_billing_country(),
			'state'   => $order->get_billing_state(),
		),
		'shipping'       => array(
			'country' => $order->get_shipping_country(),
			'state'   => $order->get_shipping_state(),
		),
		'used_coupons'   => $order->get_coupon_codes(),
		'totals'         => array(
			'subtotal' => $order->get_subtotal(),
			'shipping' => $order->get_shipping_total(),
			'tax'      => $order->get_total_tax(),
			'discount' => $order->get_discount_total(),
			'total'    => $order->get_total(),
		),

	);

	// Add tax breakdown.
	foreach ( $order->get_tax_totals() as $tax ) {
		$order_data['totals']['tax_breakdown'][ $tax->label ] = $tax->amount;
	}

	// Add cart items and quantities.
	foreach ( $order->get_items() as $item_id => $item ) {
		$order_data['cart_items'][] = array(
			'id'           => $item_id,
			'product_id'   => $item->get_product_id(),
			'variation_id' => $item->get_variation_id(),
			'quantity'     => $item->get_quantity(),
		);
	}

	// Add Shipping methods used.
	foreach ( $order->get_shipping_methods() as $method ) {
		$order_data['shipping_methods'][] = array(
			'id'    => $method->get_method_id(),
			'label' => $method->get_method_title(),
		);
	}

	return $order_data;
}
