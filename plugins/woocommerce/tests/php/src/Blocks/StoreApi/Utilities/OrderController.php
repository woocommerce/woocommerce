<?php
/**
 * OrderController Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Utilities;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * OrderControllerTests class.
 */
class OrderControllerTests extends TestCase {
	/**
	 * test_validate_selected_shipping_methods_throws
	 */
	public function test_validate_selected_shipping_methods_throws() {
		$class = new OrderController();

		$this->expectException( RouteException::class );
		$class->validate_selected_shipping_methods( true, array( false ) );
	}

	/**
	 * test_validate_selected_shipping_methods.
	 */
	public function test_validate_selected_shipping_methods() {
		$class = new OrderController();

		// By running this method we assert that it doesn't error because if it does this test will fail.
		$class->validate_selected_shipping_methods( true, array( 'free-shipping' ) );
		$class->validate_selected_shipping_methods( false, array( 'free-shipping' ) );
		$class->validate_selected_shipping_methods( true, null );
		// The above methods throw Exception on error, but this is classed as a risky test because there are no
		// assertions. Assert true to work around this warning.
		$this->assertTrue( true );
	}
}
