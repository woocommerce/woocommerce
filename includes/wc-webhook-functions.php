<?php
/**
 * WooCommerce Webhook functions
 *
 * @package  WooCommerce/Functions
 * @version  3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Process webhook delivery.
 *
 * @since 3.3.0
 * @param WC_Webhook $webhook Webhook instance.
 * @param array      $arg     Delivery arguments.
 */
function wc_webhook_process_delivery( $webhook, $arg ) {
	// Webhooks are processed in the background by default
	// so as to avoid delays or failures in delivery from affecting the
	// user who triggered it.
	if ( apply_filters( 'woocommerce_webhook_deliver_async', true, $webhook, $arg ) ) {
		// Deliver in background.
		wp_schedule_single_event( time(), 'woocommerce_deliver_webhook_async', array( $webhook->get_id(), $arg ) );
	} else {
		// Deliver immediately.
		$webhook->deliver( $arg );
	}
}
add_action( 'woocommerce_webhook_process_delivery', 'wc_webhook_process_delivery', 10, 2 );

/**
 * Wrapper function to execute the `woocommerce_deliver_webhook_async` cron.
 * hook, see WC_Webhook::process().
 *
 * @since 2.2.0
 * @param int   $webhook_id Webhook ID to deliver.
 * @param mixed $arg        Hook argument.
 */
function wc_deliver_webhook_async( $webhook_id, $arg ) {
	$webhook = new WC_Webhook( $webhook_id );
	$webhook->deliver( $arg );
}
add_action( 'woocommerce_deliver_webhook_async', 'wc_deliver_webhook_async', 10, 2 );

/**
 * Check if the given topic is a valid webhook topic, a topic is valid if:
 *
 * + starts with `action.woocommerce_` or `action.wc_`.
 * + it has a valid resource & event.
 *
 * @since  2.2.0
 * @param  string $topic Webhook topic.
 * @return bool
 */
function wc_is_webhook_valid_topic( $topic ) {

	// Custom topics are prefixed with woocommerce_ or wc_ are valid.
	if ( 0 === strpos( $topic, 'action.woocommerce_' ) || 0 === strpos( $topic, 'action.wc_' ) ) {
		return true;
	}

	$data = explode( '.', $topic );

	if ( ! isset( $data[0] ) || ! isset( $data[1] ) ) {
		return false;
	}

	$valid_resources = apply_filters( 'woocommerce_valid_webhook_resources', array( 'coupon', 'customer', 'order', 'product' ) );
	$valid_events    = apply_filters( 'woocommerce_valid_webhook_events', array( 'created', 'updated', 'deleted', 'restored' ) );

	if ( in_array( $data[0], $valid_resources, true ) && in_array( $data[1], $valid_events, true ) ) {
		return true;
	}

	return false;
}

/**
 * Get Webhook statuses.
 *
 * @since  2.3.0
 * @return array
 */
function wc_get_webhook_statuses() {
	return apply_filters( 'woocommerce_webhook_statuses', array(
		'active'   => __( 'Active', 'woocommerce' ),
		'paused'   => __( 'Paused', 'woocommerce' ),
		'disabled' => __( 'Disabled', 'woocommerce' ),
	) );
}

/**
 * Load webhooks.
 *
 * @since  3.3.0
 * @return bool
 */
function wc_load_webhooks() {
	$data_store = WC_Data_Store::load( 'webhook' );
	$webhooks   = $data_store->get_webhooks_ids();
	$loaded     = false;

	foreach ( $webhooks as $webhook_id ) {
		$webhook = new WC_Webhook( $webhook_id );
		$webhook->enqueue();
		$loaded = true;
	}

	return $loaded;
}

/**
 * Get webhook.
 *
 * @param  int|WC_Webhook $id Webhook ID or object.
 * @return WC_Webhook|null
 */
function wc_get_webhook( $id ) {
	$webhook = new WC_Webhook( $id );

	return 0 !== $webhook->get_id() ? $webhook : null;
}
