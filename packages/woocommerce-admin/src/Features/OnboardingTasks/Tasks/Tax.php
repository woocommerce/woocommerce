<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore as TaxDataStore;

/**
 * Tax Task
 */
class Tax {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		return array(
			'id'           => 'tax',
			'title'        => __( 'Set up tax', 'woocommerce' ),
			'content'      => self::can_use_automated_taxes()
				? __(
					'Good news! WooCommerce Services and Jetpack can automate your sales tax calculations for you.',
					'woocommerce'
				)
				: __(
					'Set your store location and configure tax rate settings.',
					'woocommerce'
				),
			'action_label' => self::can_use_automated_taxes()
				? __( 'Yes please', 'woocommerce' )
				: __( "Let's go", 'woocommerce' ),
			'is_complete'  => get_option( 'wc_connect_taxes_enabled' ) ||
				count( TaxDataStore::get_taxes( array() ) ) > 0 ||
				false !== get_option( 'woocommerce_no_sales_tax' ),
			'is_visible'   => true,
			'time'         => __( '1 minute', 'woocommerce' ),
		);
	}

	/**
	 * Check if the store has any enabled gateways.
	 *
	 * @return bool
	 */
	public static function can_use_automated_taxes() {
		if ( ! class_exists( 'WC_Taxjar' ) ) {
			return false;
		}

		return in_array( WC()->countries->get_base_country(), self::get_automated_tax_supported_countries(), true );
	}

	/**
	 * Get an array of countries that support automated tax.
	 *
	 * @return array
	 */
	public static function get_automated_tax_supported_countries() {
		// https://developers.taxjar.com/api/reference/#countries .
		$tax_supported_countries = array_merge(
			array( 'US', 'CA', 'AU' ),
			WC()->countries->get_european_union_countries()
		);

		return $tax_supported_countries;
	}
}
