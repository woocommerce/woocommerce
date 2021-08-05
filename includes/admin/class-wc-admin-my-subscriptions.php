<?php
/**
 * My Subscriptions Page
 *
 * @package  WooCommerce\Admin
 * @version  2.5.0
 */

use Automattic\Jetpack\Constants;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_My_Subscriptions Class.
 */
class WC_Admin_My_Subscriptions {
	/**
	 * Handles output of the My Subscriptions page in admin.
	 */
	public static function output() {
		do_action( 'woocommerce_helper_output' );
	}
}
