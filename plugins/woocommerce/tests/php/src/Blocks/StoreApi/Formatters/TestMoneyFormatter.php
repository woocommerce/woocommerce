<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Formatters;

use Automattic\WooCommerce\StoreApi\Formatters\MoneyFormatter;

/**
 * TestMoneyFormatter tests.
 */
class TestMoneyFormatter extends \WP_UnitTestCase {

	private $mock_formatter;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
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

	/**
	 * Test formatting for int overflow values.
	 */
	public function test_format_int_overflow() {
		$this->assertEquals( '922337203685477580800', $this->mock_formatter->format( '9223372036854775808' ) );
		$this->assertEquals( '922337203685477580800', $this->mock_formatter->format( floatval('9223372036854775808') ) );
	}

	/**
	 * Test formatting expects exception on invalid array values.
	 */
	public function test_format_array_exception() {
		$this->expectException(\TypeError::class);
		$this->mock_formatter->format( array( 'This is not right') );
	}
}
