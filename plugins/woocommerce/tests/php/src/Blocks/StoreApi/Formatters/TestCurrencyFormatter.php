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
}
