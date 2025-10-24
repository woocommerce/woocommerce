<?php
/**
 * Mock WC_Payments_Utils class for testing.
 *
 * This mock is used in tests when the real WC_Payments_Utils class is not available.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings\Mocks
 */

if ( ! class_exists( 'WC_Payments_Utils' ) ) {
	/**
	 * Mock WC_Payments_Utils class.
	 */
	class WC_Payments_Utils {
		/**
		 * Get the list of supported countries for WooPayments.
		 *
		 * @return array Array of country codes and names.
		 */
		public static function supported_countries() {
			return array(
				'us' => 'United States',
				'gb' => 'United Kingdom',
				'de' => 'Germany',
			);
		}
	}
}