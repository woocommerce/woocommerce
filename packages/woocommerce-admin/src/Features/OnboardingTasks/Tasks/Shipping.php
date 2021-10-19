<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * Shipping Task
 */
class Shipping {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'           => 'shipping',
			'title'        => __( 'Set up shipping', 'woocommerce' ),
			'content'      => __(
				"Set your store location and where you'll ship to.",
				'woocommerce'
			),
			'action_url'   => self::has_shipping_zones()
				? admin_url( 'admin.php?page=wc-settings&tab=shipping' )
				: null,
			'action_label' => __( "Let's go", 'woocommerce' ),
			'is_complete'  => self::has_shipping_zones(),
			'can_view'     => self::has_physical_products(),
			'time'         => __( '1 minute', 'woocommerce' ),
		);
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
