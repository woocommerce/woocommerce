<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Gateways\PayPal;

use Automattic\WooCommerce\Gateways\PayPal\CurrenciesSupported as PayPalCurrenciesSupported;

/**
 * Tests for the CurrenciesSupported helper class.
 */
class CurrenciesSupportedTest extends \WC_Unit_Test_Case {
	/**
	 * Tests for `is_currency_supported`.
	 *
	 * @return void
	 * @throws \Automattic\WooCommerce\Internal\DependencyManagement\ContainerException If the class cannot be retrieved from the container.
	 */
	public function test_is_currency_supported(): void {
		$currencies_supported = wc_get_container()->get( PayPalCurrenciesSupported::class )::instance();

		$this->assertTrue( $currencies_supported->is_currency_supported( 'USD' ) );
		$this->assertTrue( $currencies_supported->is_currency_supported( 'EUR' ) );
		$this->assertTrue( $currencies_supported->is_currency_supported( 'JPY' ) );

		$this->assertFalse( $currencies_supported->is_currency_supported( 'INR' ) );
		$this->assertFalse( $currencies_supported->is_currency_supported( 'ZAR' ) );
		$this->assertFalse( $currencies_supported->is_currency_supported( 'XXX' ) );
	}
}
