<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;

/**
 * NoticeHandler unit tests.
 */
class NoticeHandlerTests extends \WC_Unit_Test_Case {

	/**
	 * Clear the WooCommerce notice queue.
	 *
	 * Notices live on the WC session singleton, which neither the per-test database
	 * transaction nor the hook restore touches, so they have to be cleared explicitly or
	 * they leak into every later test in the process.
	 */
	public function tearDown(): void {
		wc_clear_notices();

		parent::tearDown();
	}

	/**
	 * Test convert_notices_to_exceptions.
	 */
	public function test_convert_notices_to_exceptions() {
		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( 'This is an error message with Some HTML in it.' );
		wc_add_notice( '<strong>This is an error message with <a href="#">Some HTML in it</a>.', 'error' );
		$errors = NoticeHandler::convert_notices_to_exceptions( 'test_error' );
	}

	/**
	 * Test convert_notices_to_wp_errors.
	 */
	public function test_convert_notices_to_wp_errors() {
		wc_add_notice( '<strong>This is an error message with <a href="#">Some HTML in it</a>.', 'error' );
		$errors = NoticeHandler::convert_notices_to_wp_errors( 'test_error' );
		$this->assertTrue( is_wp_error( $errors ) );
		$this->assertEquals( 1, count( $errors->get_error_codes() ) );
		$this->assertEquals( 'This is an error message with Some HTML in it.', $errors->get_error_message() );
		$this->assertEquals( 'test_error', $errors->get_error_code() );
	}
}
