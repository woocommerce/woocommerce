<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Onboarding;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Task;

/**
 * Shipping Task
 */
class Shipping extends Task {
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
		return __( 'Set up shipping', 'woocommerce-admin' );
	}

	/**
	 * Content.
	 *
	 * @return string
	 */
	public function get_content() {
		return __(
			"Set your store location and where you'll ship to.",
			'woocommerce-admin'
		);
	}

	/**
	 * Time.
	 *
	 * @return string
	 */
	public function get_time() {
		return __( '1 minute', 'woocommerce-admin' );
	}

	/**
	 * Task completion.
	 *
	 * @return bool
	 */
	public function is_complete() {
		return self::has_shipping_zones();
	}

	/**
	 * Task visibility.
	 *
	 * @return bool
	 */
	public function can_view() {
		return self::has_physical_products();
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
		return count( \WC_Shipping_Zones::get_zones() ) > 0;
	}

	/**
	 * Check if the store has physical products.
	 *
	 * @return bool
	 */
	public static function has_physical_products() {
		$profiler_data = get_option( Onboarding::PROFILE_DATA_OPTION, array() );
		$product_types = isset( $profiler_data['product_types'] ) ? $profiler_data['product_types'] : array();

		return in_array( 'physical', $product_types, true ) ||
			count(
				wc_get_products(
					array(
						'virtual' => false,
						'limit'   => 1,
					)
				)
			) > 0;
	}
}
