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

		if ( wc_current_theme_is_fse_theme() ) {
			include_once WC_ABSPATH . 'includes/blocks/class-wc-brands-block-templates.php';
			include_once WC_ABSPATH . 'includes/blocks/class-wc-brands-block-template-utils-duplicated.php';
		}

		if ( is_admin() ) {
			include_once WC_ABSPATH . 'includes/admin/class-wc-admin-brands.php';
		}
	}

	/**
	 * Ensures that the Brands feature is released initially only to 5% of users.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$assignment = get_option( 'woocommerce_remote_variant_assignment', false );

		if ( false === $assignment ) {
			return false;
		}
		return ( $assignment <= 6 ); // Considering 5% of the 0-120 range.
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
