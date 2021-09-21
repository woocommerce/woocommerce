<?php
/**
 * OrderController Tests.
 */

namespace Automattic\WooCommerce\Blocks\Tests\StoreApi\Utilities;

use Automattic\WooCommerce\Blocks\StoreApi\Routes\RouteException;
use Yoast\PHPUnitPolyfills\Polyfills\ExpectException;
use Automattic\WooCommerce\Blocks\StoreApi\Utilities\OrderController;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class OrderControllerTests extends TestCase {
	use ExpectException;

	public function test_validate_selected_shipping_methods_throws() {
		$class = new OrderController();

		$this->expectException( RouteException::class );
		$class->validate_selected_shipping_methods( true, array( false ) );
	}

	public function test_validate_selected_shipping_methods() {
		$class = new OrderController();

		// By running this method we assert that it doesn't error because if it does this test will fail.
		$class->validate_selected_shipping_methods( true, array( 'free-shipping' ) );
		$class->validate_selected_shipping_methods( false, array( 'free-shipping' ) );
		$class->validate_selected_shipping_methods( true, null );
	}
}
