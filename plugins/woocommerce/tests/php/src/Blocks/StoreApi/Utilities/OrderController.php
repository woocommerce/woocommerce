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
		$class->validate_selected_shipping_methods( true, null );
	}

	/**
	 * test_validate_selected_shipping_methods.
	 */
	public function test_validate_selected_shipping_methods() {
		// Add a flat rate to the default zone.
		$flat_rate    = WC()->shipping()->get_shipping_methods()['flat_rate'];
		$default_zone = \WC_Shipping_Zones::get_zone( 0 );
		$default_zone->add_shipping_method( $flat_rate->id );
		$default_zone->save();

		$class = new OrderController();

		$registered_methods = \WC_Shipping_Zones::get_zone( 0 )->get_shipping_methods();
		$valid_method       = array_shift( $registered_methods );

		// By running this method we assert that it doesn't error because if it does this test will fail.
		$class->validate_selected_shipping_methods( true, array( $valid_method->id . ':' . $valid_method->instance_id ) );
		$class->validate_selected_shipping_methods( false, array( 'free-shipping' ) );
		// The above methods throw Exception on error, but this is classed as a risky test because there are no
		// assertions. Assert true to work around this warning.
		$this->assertTrue( true );
	}
}
