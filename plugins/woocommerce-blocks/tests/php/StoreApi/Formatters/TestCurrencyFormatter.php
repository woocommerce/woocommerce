<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Formatters;

use \WC_REST_Unit_Test_Case as TestCase;
use Automattic\WooCommerce\Blocks\StoreApi\Formatters\CurrencyFormatter;

/**
 * TestCurrencyFormatter tests.
 */
class TestCurrencyFormatter extends TestCase {

	private $mock_formatter;

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
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
