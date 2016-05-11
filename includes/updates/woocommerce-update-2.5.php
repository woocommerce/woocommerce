<?php
/**
 * Update WC to 2.5.0
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin/Updates
 * @version  2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Fix currency settings for LAK currency.
$current_currency = get_option( 'woocommerce_currency' );

if ( 'KIP' === $current_currency ) {
	update_option( 'woocommerce_currency', 'LAK' );
}

// Update LAK currency code.
$wpdb->update(
	$wpdb->postmeta,
	array(
		'meta_value' => 'LAK'
	),
	array(
		'meta_key'   => '_order_currency',
		'meta_value' => 'KIP'
	)
);
