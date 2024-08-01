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

	/**
	 * @var MoneyFormatter
	 */
	private $formatter;

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->formatter = new MoneyFormatter();
	}

	/**
	 * Test formatting.
	 */
	public function test_format() {
		$this->assertEquals( '1000', $this->formatter->format( 10 ) );
		$this->assertEquals( '1000', $this->formatter->format( '10' ) );
	}

	/**
	 * Test formatting with custom DP.
	 */
	public function test_format_dp() {
		$this->assertEquals( '100000', $this->formatter->format( 10, [ 'decimals' => 4 ] ) );
		$this->assertEquals( '100000', $this->formatter->format( '10', [ 'decimals' => 4 ] ) );
	}

	/**
	 * Test formatting with custom DP.
	 */
	public function test_format_rounding_mode() {
		$this->assertEquals( '156', $this->formatter->format( 1.555 ) );
		$this->assertEquals( '156', $this->formatter->format( 1.555, [ 'rounding_mode' => PHP_ROUND_HALF_UP ] ) );
		$this->assertEquals( '155', $this->formatter->format( 1.555, [ 'rounding_mode' => PHP_ROUND_HALF_DOWN ] ) );
		$this->assertEquals( '156', $this->formatter->format( 1.555, [ 'rounding_mode' => PHP_ROUND_HALF_EVEN ] ) );
		$this->assertEquals( '155', $this->formatter->format( 1.555, [ 'rounding_mode' => PHP_ROUND_HALF_ODD ] ) );
		$this->assertEquals( '156', $this->formatter->format( 1.555, [ 'rounding_mode' => 123456 ] ) );
	}

	/**
	 * Test formatting for int overflow values.
	 */
	public function test_format_int_overflow() {
		$this->assertEquals( '922337203685477580800', $this->formatter->format( '9223372036854775808' ) );
		$this->assertEquals( '922337203685477580800', $this->formatter->format( floatval( '9223372036854775808' ) ) );
	}

	/**
	 * Data provider for invalid param types.
	 */
	public function invalidTypesProvider() {
		return [
			[ true ],
			[ null ],
			[ [ 'Not right' ] ],
			[ new \StdClass() ],
		];
	}

	/**
	 * Test formatting returns '' if a $value of type INT, STRING or FLOAT is not provided.
	 * @dataProvider invalidTypesProvider
	 *
	 * @param mixed $invalid_type The invalid type to test.
	 */
	public function test_format_unexpected_param_types( $invalid_type ) {
		$this->expected_doing_it_wrong = array_merge(
			$this->expected_doing_it_wrong,
			[ 'format' ]
		);

		// Assert that the format method returns an empty string for invalid types.
		$this->assertEquals( '', $this->formatter->format( $invalid_type ) );
	}
}
