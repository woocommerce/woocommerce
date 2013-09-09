<?php
/**
 * Update WC to 2.0.15
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.0.15
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb, $woocommerce;

// Update Canadian state codes from NF to NL state for Newfoundland
$new_state = 'NL';
$old_state = 'NF';
$country   = 'CA';

// Key is field to target with UPDATE query
// Value is field to find in subquery for getting correct country record
$field_match = array(
	'_billing_state'  => '_billing_country',
	'_shipping_state' => '_shipping_country',
	'billing_state'   => 'billing_country',
	'shipping_state'  => 'shipping_country',
);

foreach ( $field_match as $replace_key => $find_key ) {
	$wpdb->query(
		$wpdb->prepare(
			"
			UPDATE " . $wpdb->postmeta . " pm
			SET pm.meta_value = %s
			WHERE pm.meta_key = %s
			AND pm.meta_value = %s
			AND pm.meta_id IN (
				SELECT pmi.post_id
				FROM " . $wpdb->postmeta . " pmi
				WHERE pmi.meta_key = %s
				AND pmi.meta_value = %s
			) AS updatestate
			",
			$new_state, $replace_key, $old_state, $find_key, $country
		)
	);
}