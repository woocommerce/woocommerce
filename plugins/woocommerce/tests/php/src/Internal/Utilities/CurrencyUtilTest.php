<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\CurrencyUtil;
use WC_Unit_Test_Case;

/** Tests for order currency precision. */
class CurrencyUtilTest extends WC_Unit_Test_Case {
	/**
	 * @testdox Currency metadata can increase precision without reducing the store setting.
	 * @param string $store Store currency.
	 * @param int    $decimals Store decimals.
	 * @param mixed  $currency Requested currency.
	 * @param int    $expected Expected precision.
	 * @testWith ["JPY", 0, "USD", 2]
	 *           ["JPY", 0, "EUR", 2]
	 *           ["JPY", 0, "KWD", 3]
	 *           ["JPY", 0, "JPY", 0]
	 *           ["USD", 0, "USD", 0]
	 *           ["USD", 0, "JPY", 0]
	 *           ["USD", 4, "JPY", 4]
	 *           ["JPY", 0, "UNKNOWN", 0]
	 *           ["JPY", 0, null, 0]
	 *           ["JPY", 0, [], 0]
	 */
	public function test_price_decimals( string $store, int $decimals, $currency, int $expected ): void {
		update_option( 'woocommerce_currency', $store );
		update_option( 'woocommerce_price_num_decimals', $decimals );
		$this->assertSame( $expected, CurrencyUtil::get_price_decimals( $currency ) );
	}

	/** @testdox Currency metadata loads independently of the current working directory. */
	public function test_metadata_loads_outside_plugin_directory(): void {
		update_option( 'woocommerce_currency', 'JPY' );
		update_option( 'woocommerce_price_num_decimals', 0 );
		$cache = new \ReflectionProperty( CurrencyUtil::class, 'currency_decimals' );
		$cache->setAccessible( true );
		$previous_cache     = $cache->getValue();
		$previous_directory = getcwd();
		if ( false === $previous_directory ) {
			throw new \RuntimeException( 'Cannot determine the directory to restore after the test.' );
		}
		try {
			$cache->setValue( null, null );
			chdir( ABSPATH );
			$this->assertSame( 2, CurrencyUtil::get_price_decimals( 'USD' ) );
		} finally {
			chdir( $previous_directory );
			$cache->setValue( null, $previous_cache );
		}
	}
}
