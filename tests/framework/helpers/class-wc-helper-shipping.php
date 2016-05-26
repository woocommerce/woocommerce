<?php

/**
 * Class WC_Helper_Shipping.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Shipping {

	/**
	 * Create a simple flat rate at the cost of 10.
	 *
	 * @since 2.3
	 */
	public static function create_simple_flat_rate() {
		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat Rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => '10'
		);

		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
		update_option( 'woocommerce_flat_rate', array() );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping->unregister_shipping_methods();
	}

	/**
	 * Delete the simple flat rate.
	 *
	 * @since 2.3
	 */
	public static function delete_simple_flat_rate() {
		delete_option( 'woocommerce_flat_rate_settings' );
		delete_option( 'woocommerce_flat_rate' );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping->unregister_shipping_methods();
	}
}
