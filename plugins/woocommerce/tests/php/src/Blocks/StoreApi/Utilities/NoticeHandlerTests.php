<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\NoticeHandler;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * NoticeHandler unit tests.
 */
class NoticeHandlerTests extends TestCase {

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
