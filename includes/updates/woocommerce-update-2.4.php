<?php
/**
 * Update WC to 2.4.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Maintain the old coupon logic for upgrades
update_option( 'woocommerce_calc_discounts_sequentially', 'yes' );