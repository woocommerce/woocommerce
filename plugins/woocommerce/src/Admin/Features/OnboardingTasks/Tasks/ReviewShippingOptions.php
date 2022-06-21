<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;
use \Automattic\Jetpack\Connection\Manager as Jetpack_Connection_Manager;
use \Automattic\WooCommerce\Admin\PluginsHelper;
use WC_Data_Store;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks\Shipping;

/**
 * Review Shipping Options Task
 */
class ReviewShippingOptions extends Task {
	/**
	 * ID.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'shipping';
	}

	/**
	 * Title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Review Shipping Options', 'woocommerce' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return '';
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return '';
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		// TODO: Implement is_complete() method.
		return false;
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		// TODO: Just check automated shipping options have been set up when #33366 is completed.
		if ( Shipping::sell_only_digital_type() || Shipping::sell_unknown_product_type() ) {
			return false;
		}

		if ( ! self::has_shipping_zones() ) {
			return false;
		}

		$store_country = wc_format_country_state_string( get_option( 'woocommerce_default_country', '' ) )['country'];

		if ( ! $store_country ) {
			return false;
		}

		$is_jetpack_installed = PluginsHelper::is_plugin_installed( 'jetpack' );
		$is_jetpack_connected = class_exists( 'Jetpack_Connection_Manager' ) && ( new Jetpack_Connection_Manager() )->is_connected();

		$is_wcs_installed = $is_jetpack_connected && PluginsHelper::is_plugin_installed( 'woocommerce-services' );
		// Is it installed and WooCommerce Shipping & Tax Terms of Service accepted?
		$is_wcs_connected = $is_wcs_installed && class_exists( '\WC_Connect_Options' ) && \WC_Connect_Options::get_option( 'tos_accepted' );

		return 'US' === $store_country && (
			$is_jetpack_connected && $is_wcs_connected || $is_jetpack_installed && ! $is_wcs_installed ) ||
			! in_array( $store_country, array( 'US', 'CA', 'AU', 'UK' ), true );
	}

	/**
	 * Action URL.
	 *
	 * @return string
	 */
	public function get_action_url() {
		return self::has_shipping_zones()
			? admin_url( 'admin.php?page=wc-settings&tab=shipping' )
			: null;
	}

	/**
	 * Check if the store has any shipping zones.
	 *
	 * @return bool
	 */
	public static function has_shipping_zones() {
		return count( WC_Data_Store::load( 'shipping-zone' )->get_zones() ) > 0;
	}
}
