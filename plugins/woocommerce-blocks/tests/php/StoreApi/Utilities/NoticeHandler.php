<?php
/**
 * NoticeHandler Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\CartController;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\NoticeHandler;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class NoticeHandlerTests extends TestCase {
	public function test_convert_notices_to_exceptions() {
		$this->expectException( RouteException::class );
		$this->expectExceptionMessage( 'This is an error message with Some HTML in it.' );
		wc_add_notice( '<strong>This is an error message with <a href="#">Some HTML in it</a>.', 'error' );
		$errors = NoticeHandler::convert_notices_to_exceptions( 'test_error' );
	}
}
