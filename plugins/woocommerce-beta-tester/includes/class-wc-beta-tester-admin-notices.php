<?php
/**
 * Admin notices
 *
 * @package WC_Beta_Tester\Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin notices class.
 */
class WC_Beta_Tester_Admin_Notices {

	/**
	 * WooCommerce not installed notice.
	 */
	public function woocoommerce_not_installed() {
		include_once dirname( __FILE__ ) . '/views/html-admin-missing-woocommerce.php';
	}
}
