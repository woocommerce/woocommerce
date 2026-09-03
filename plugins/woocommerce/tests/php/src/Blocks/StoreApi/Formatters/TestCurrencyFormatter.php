<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Formatters;

use Automattic\WooCommerce\StoreApi\Formatters\CurrencyFormatter;

/**
 * TestCurrencyFormatter tests.
 */
class TestCurrencyFormatter extends \WP_UnitTestCase {

	private $mock_formatter;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->mock_formatter = new CurrencyFormatter();
	}

	/**
	 * Test formatting.
	 */
	public function test_format() {
		$value = $this->mock_formatter->format( [] );
		$this->assertArrayHasKey( 'currency_code', $value );
		$this->assertArrayHasKey( 'currency_symbol', $value );
		$this->assertArrayHasKey( 'currency_minor_unit', $value );
		$this->assertArrayHasKey( 'currency_decimal_separator', $value );
		$this->assertArrayHasKey( 'currency_thousand_separator', $value );
		$this->assertArrayHasKey( 'currency_prefix', $value );
		$this->assertArrayHasKey( 'currency_suffix', $value );
	}

	/**
	 * @testdox Formatted currency data decodes HTML entities in the price separators.
	 */
	public function test_format_decodes_separator_entities() {
		update_option( 'woocommerce_price_thousand_sep', '&nbsp;' );
		update_option( 'woocommerce_price_decimal_sep', '&#44;' );

		$value = $this->mock_formatter->format( [] );

		$this->assertSame( "\u{00A0}", $value['currency_thousand_separator'] );
		$this->assertSame( ',', $value['currency_decimal_separator'] );
	}
}
