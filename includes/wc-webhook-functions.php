<?php
/**
 * WooCommerce Webhook functions
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
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
 * @since 3.2.0
 */
function wc_load_webhooks() {
	$data_store = WC_Data_Store::load( 'webhook' );
	$webhooks   = $data_store->get_webhooks_ids();

	foreach ( $webhooks as $webhook_id ) {
		$webhook = new WC_Webhook( $webhook_id );
		$webhook->enqueue();
	}
}
