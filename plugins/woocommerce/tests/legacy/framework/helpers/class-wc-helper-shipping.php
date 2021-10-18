<?php
/**
 * Helper class for shipping related unit tests.
 *
 * @package WooCommerce\Tests\Helper
 */

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
	 *
	 * @param float $cost Optional. Cost of flat rate method.
	 */
	public static function create_simple_flat_rate( $cost = 10 ) {
		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => $cost,
		);

		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
		update_option( 'woocommerce_flat_rate', array() );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();
	}

	/**
	 * Helper function to set customer address so that shipping can be calculated.
	 */
	public static function force_customer_us_address() {
		add_filter( 'woocommerce_customer_get_shipping_country', array( self::class, 'force_customer_us_country' ) );
		add_filter( 'woocommerce_customer_get_shipping_state', array( self::class, 'force_customer_us_state' ) );
		add_filter( 'woocommerce_customer_get_shipping_postcode', array( self::class, 'force_customer_us_postcode' ) );
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping state to be NY.
	 *
	 * @since 4.4.0
	 * @param string $state State code.
	 * @return string
	 */
	public static function force_customer_us_state( $state ) {
		return 'NY';
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping country to be US.
	 *
	 * @since 4.4.0
	 * @param string $country Country code.
	 * @return string
	 */
	public static function force_customer_us_country( $country ) {
		return 'US';
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping postal code to be 12345.
	 *
	 * @since 4.4.0
	 * @param string $postcode Postal code.
	 * @return string
	 */
	public static function force_customer_us_postcode( $postcode ) {
		return '12345';
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
		WC()->shipping()->unregister_shipping_methods();
	}
}
