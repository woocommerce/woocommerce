<?php
/**
 * Update WC to 2.1.0
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Updates
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb, $woocommerce;

// Pages no longer used
wp_delete_post( get_option('woocommerce_pay_page_id'), true );
wp_delete_post( get_option('woocommerce_thanks_page_id'), true );