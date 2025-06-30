<?php
/**
 * Default settings for Point of Sale.
 *
 * @package WooCommerce\Internal\Settings
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Internal\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * PointOfSaleDefaultSettings class.
 */
class PointOfSaleDefaultSettings {
	/**
	 * Get default store email.
	 *
	 * @return string
	 */
	public static function get_default_store_email() {
		return get_option( 'admin_email' );
	}

	/**
	 * Get default store name.
	 *
	 * @return string
	 */
	public static function get_default_store_name() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Get default store address.
	 *
	 * @return string
	 */
	public static function get_default_store_address() {
		if ( ! WC() || ! WC()->countries ) {
			return '';
		}

		return wp_specialchars_decode(
			WC()->countries->get_formatted_address(
				array(
					'address_1' => WC()->countries->get_base_address(),
					'address_2' => WC()->countries->get_base_address_2(),
					'city'      => WC()->countries->get_base_city(),
					'state'     => WC()->countries->get_base_state(),
					'postcode'  => WC()->countries->get_base_postcode(),
					'country'   => WC()->countries->get_base_country(),
				),
				"\n"
			)
		);
	}
}
