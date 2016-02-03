<?php
namespace WooCommerce\Tests\Util;

/**
 * Class Log.
 * @package WooCommerce\Tests\Util
 * @since 2.3
 */
class Log extends \WC_Unit_Test_Case {
	public function read_content( $handle ) {
		return file_get_contents( wc_get_log_file_path( $handle ) );
	}

	/**
	 * Test add().
	 *
	 * @since 2.4
	 */
	public function test_add() {
		$log = new \WC_Logger();

		$log->add( 'unit-tests', 'this is a message' );

		$this->assertStringMatchesFormat( '%d-%d-%d @ %d:%d:%d - %s', $this->read_content( 'unit-tests' ) );
		$this->assertStringEndsWith( ' - this is a message' . PHP_EOL, $this->read_content( 'unit-tests' ) );
	}

	/**
	 * Test clear().
	 *
	 * @since 2.4
	 */
	public function test_clear() {
		$log = new \WC_Logger();

		$log->add( 'unit-tests', 'this is a message' );
		$log->clear( 'unit-tests' );

		$this->assertEquals( '', $this->read_content( 'unit-tests' ) );
	}
}
