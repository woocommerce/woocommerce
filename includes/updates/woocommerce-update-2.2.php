<?php
/**
 * Update WC to 2.2.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;

// Update options
$woocommerce_ship_to_destination = 'shipping';

if ( get_option( 'woocommerce_ship_to_billing_address_only' ) === 'yes' ) {
	$woocommerce_ship_to_destination = 'billing_only';
} elseif ( get_option( 'woocommerce_ship_to_billing' ) === 'yes' ) {
	$woocommerce_ship_to_destination = 'billing';
}

add_option( 'woocommerce_ship_to_destination', $woocommerce_ship_to_destination, '', 'no' );