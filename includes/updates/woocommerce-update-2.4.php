<?php
/**
 * Update WC to 2.4.0
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Updates
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

/**
 * Coupon discount calculations.
 * Maintain the old coupon logic for upgrades.
 */
update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );

/**
 * Flat Rate Shipping.
 * Update legacy options to new math based options.
 */
$shipping_methods = array(
	'woocommerce_flat_rates'                        => new WC_Shipping_Flat_Rate(),
	'woocommerce_international_delivery_flat_rates' => new WC_Shipping_International_Delivery()
);
foreach ( $shipping_methods as $flat_rate_option_key => $shipping_method ) {
	// Stop this running more than once if routine is repeated
	if ( version_compare( $shipping_method->get_option( 'version', 0 ), '2.4.0', '<' ) ) {
		$has_classes                      = sizeof( WC()->shipping->get_shipping_classes() ) > 0;
		$cost_key                         = $has_classes ? 'no_class_cost' : 'cost';
		$min_fee                          = $shipping_method->get_option( 'minimum_fee' );
		$math_cost_strings                = array( 'cost' => array(), 'no_class_cost' => array() );
		$math_cost_strings[ $cost_key ][] = $shipping_method->get_option( 'cost' );

		if ( $fee = $shipping_method->get_option( 'fee' ) ) {
			$math_cost_strings[ $cost_key ][] = strstr( $fee, '%' ) ? '[fee percent="' . str_replace( '%', '', $fee ) . '" min="' . esc_attr( $min_fee ) . '"]' : $fee;
		}

		foreach ( WC()->shipping->get_shipping_classes() as $shipping_class ) {
			$rate_key                       = 'class_cost_' . $shipping_class->slug;
			$math_cost_strings[ $rate_key ] = $math_cost_strings[ 'no_class_cost' ];
		}

		if ( $flat_rates = array_filter( (array) get_option( $flat_rate_option_key, array() ) ) ) {
			foreach ( $flat_rates as $shipping_class => $rate ) {
				$rate_key = 'class_cost_' . $shipping_class;
				if ( $rate['cost'] || $rate['fee'] ) {
					$math_cost_strings[ $rate_key ][] = $rate['cost'];
					$math_cost_strings[ $rate_key ][] = strstr( $rate['fee'], '%' ) ? '[fee percent="' . str_replace( '%', '', $rate['fee'] ) . '" min="' . esc_attr( $min_fee ) . '"]' : $rate['fee'];
				}
			}
		}

		if ( 'item' === $shipping_method->type ) {
			foreach ( $math_cost_strings as $key => $math_cost_string ) {
				$math_cost_strings[ $key ] = array_filter( array_map( 'trim', $math_cost_strings[ $key ] ) );
				if ( ! empty( $math_cost_strings[ $key ] ) ) {
					$last_key                                = max( 0, sizeof( $math_cost_strings[ $key ] ) - 1 );
					$math_cost_strings[ $key ][0]            = '( ' . $math_cost_strings[ $key ][0];
					$math_cost_strings[ $key ][ $last_key ] .= ' ) * [qty]';
				}
			}
		}

		$math_cost_strings[ 'cost' ][] = $shipping_method->get_option( 'cost_per_order' );

		// Save settings
		foreach ( $math_cost_strings as $option_id => $math_cost_string ) {
			$shipping_method->settings[ $option_id ] = implode( ' + ', array_filter( $math_cost_string ) );
		}

		$shipping_method->settings['version'] = '2.4.0';
		$shipping_method->settings['type']    = 'item' === $shipping_method->settings['type'] ? 'class' : $shipping_method->settings['type'];

		update_option( $shipping_method->plugin_id . $shipping_method->id . '_settings', $shipping_method->settings );
	}
}

/**
 * Update the old user API keys to the new Apps keys.
 */
$api_users = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'woocommerce_api_consumer_key'" );
$apps_keys = array();

// Get user data
foreach ( $api_users as $_user ) {
	$user = get_userdata( $_user->user_id );
	$apps_keys[] = array(
		'user_id'         => $user->ID,
		'permissions'     => $user->woocommerce_api_key_permissions,
		'consumer_key'    => wc_api_hash( $user->woocommerce_api_consumer_key ),
		'consumer_secret' => $user->woocommerce_api_consumer_secret,
		'truncated_key'   => substr( $user->woocommerce_api_consumer_secret, -7 )
	);
}

if ( ! empty( $apps_keys ) ) {
	// Create new apps
	foreach ( $apps_keys as $app ) {
		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			$app,
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);
	}

	// Delete old user keys from usermeta
	foreach ( $api_users as $_user ) {
		$user_id = intval( $_user->user_id );
		delete_user_meta( $user_id, 'woocommerce_api_consumer_key' );
		delete_user_meta( $user_id, 'woocommerce_api_consumer_secret' );
		delete_user_meta( $user_id, 'woocommerce_api_key_permissions' );
	}
}

/**
 * Webhooks.
 * Make sure order.update webhooks get the woocommerce_order_edit_status hook.
 */
$order_update_webhooks = get_posts( array(
	'posts_per_page' => -1,
	'post_type'      => 'shop_webhook',
	'meta_key'       => '_topic',
	'meta_value'     => 'order.updated'
) );
foreach ( $order_update_webhooks as $order_update_webhook ) {
	$webhook = new WC_Webhook( $order_update_webhook->ID );
	$webhook->set_topic( 'order.updated' );
}

/**
 * Refunds for full refunded orders.
 * Update fully refunded orders to ensure they have a refund line item so reports add up.
 */
$refunded_orders = get_posts( array(
	'posts_per_page' => -1,
	'post_type'      => 'shop_order',
	'post_status'    => array( 'wc-refunded' )
) );

// Ensure emails are disabled during this update routine
remove_all_actions( 'woocommerce_order_status_refunded_notification' );
remove_all_actions( 'woocommerce_order_partially_refunded_notification' );
remove_action( 'woocommerce_order_status_refunded', array( 'WC_Emails', 'send_transactional_email' ) );
remove_action( 'woocommerce_order_partially_refunded', array( 'WC_Emails', 'send_transactional_email' ) );

foreach ( $refunded_orders as $refunded_order ) {
	$order_total    = get_post_meta( $refunded_order->ID, '_order_total', true );
	$refunded_total = $wpdb->get_var( $wpdb->prepare( "
		SELECT SUM( postmeta.meta_value )
		FROM $wpdb->postmeta AS postmeta
		INNER JOIN $wpdb->posts AS posts ON ( posts.post_type = 'shop_order_refund' AND posts.post_parent = %d )
		WHERE postmeta.meta_key = '_refund_amount'
		AND postmeta.post_id = posts.ID
	", $refunded_order->ID ) );

	if ( $order_total > $refunded_total ) {
		$refund = wc_create_refund( array(
			'amount'     => $order_total - $refunded_total,
			'reason'     => __( 'Order Fully Refunded', 'woocommerce' ),
			'order_id'   => $refunded_order->ID,
			'line_items' => array(),
			'date'       => $refunded_order->post_modified
		) );
	}
}

wc_delete_shop_order_transients();
