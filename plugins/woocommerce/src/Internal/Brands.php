<?php
/**
 * Brands class file.
 */

declare( strict_types = 1);

namespace Automattic\WooCommerce\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * Class to initiate Brands functionality in core.
 */
class Brands {

	/**
	 * Class initialization
	 *
	 * @internal
	 */
	final public static function init() {

		if ( ! self::is_enabled() ) {
			return;
		}

		include_once WC_ABSPATH . 'includes/class-wc-brands.php';
		include_once WC_ABSPATH . 'includes/class-wc-brands-coupons.php';
		include_once WC_ABSPATH . 'includes/class-wc-brands-brand-settings-manager.php';
		include_once WC_ABSPATH . 'includes/wc-brands-functions.php';

		if ( is_admin() ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-brands.php';
		}
	}

	/**
	 * As of WooCommerce 9.6, Brands is enabled for all users.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return true;
	}

	/**
	 * If WooCommerce Brands gets activated forcibly, without WooCommerce active (e.g. via '--skip-plugins'),
	 * remove WooCommerce Brands initialization functions early on in the 'plugins_loaded' timeline.
	 */
	public static function prepare() {

		if ( ! self::is_enabled() ) {
			return;
		}

		if ( function_exists( 'wc_brands_init' ) ) {
			remove_action( 'plugins_loaded', 'wc_brands_init', 1 );
		}
	}
}
