<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Formatters;

use Automattic\WooCommerce\Blocks\StoreApi\Formatters\MoneyFormatter;

/**
 * TestMoneyFormatter tests.
 */
class TestMoneyFormatter extends \WP_UnitTestCase {

	private $mock_formatter;

	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->mock_formatter = new MoneyFormatter();
	}

	/**
	 * Test formatting.
	 */
	public function test_format() {
		$this->assertEquals( "1000", $this->mock_formatter->format( 10 ) );
		$this->assertEquals( "1000", $this->mock_formatter->format( "10" ) );
	}

	/**
	 * Test formatting with custom DP.
	 */
	public function test_format_dp() {
		$this->assertEquals( "100000", $this->mock_formatter->format( 10, [ 'decimals' => 4 ] ) );
		$this->assertEquals( "100000", $this->mock_formatter->format( "10", [ 'decimals' => 4 ] ) );
	}

	/**
	 * Test formatting with custom DP.
	 */
	public function test_format_rounding_mode() {
		$this->assertEquals( "156", $this->mock_formatter->format( 1.555, [ 'rounding_mode' => PHP_ROUND_HALF_UP ] ) );
		$this->assertEquals( "155", $this->mock_formatter->format( 1.555, [ 'rounding_mode' => PHP_ROUND_HALF_DOWN ] ) );
	}
}
