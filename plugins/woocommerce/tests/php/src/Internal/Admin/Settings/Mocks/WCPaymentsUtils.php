<?php
/**
 * Mock WC_Payments_Utils class for testing.
 *
 * This mock is used in tests when the real WC_Payments_Utils class is not available.
 *
 * @package WooCommerce\Tests\Internal\Admin\Settings\Mocks
 */

declare( strict_types=1 );

if ( ! class_exists( 'WC_Payments_Utils' ) ) {
	/**
	 * Mock WC_Payments_Utils class.
	 *
	 * phpcs:disable Squiz.Classes.ClassFileName.NoMatch
	 * phpcs:disable Suin.Classes.PSR4.IncorrectClassName
	 * phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
	 */
	class WC_Payments_Utils {
		/**
		 * Get the list of supported countries for WooPayments.
		 *
		 * @return array Array of country codes and names.
		 */
		public static function supported_countries() {
			// This is just a subset of countries that WooPayments supports.
			// But it should cover our testing needs.
			return array(
				'us' => 'United States',
				'gb' => 'United Kingdom',
				'de' => 'Germany',
			);
		}
	}
}
