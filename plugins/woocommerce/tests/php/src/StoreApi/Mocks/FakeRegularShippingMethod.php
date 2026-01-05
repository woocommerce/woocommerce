<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Mocks;

use WC_Shipping_Method;

/**
 * Fake shipping method that does NOT support local pickup for testing.
 */
class FakeRegularShippingMethod extends WC_Shipping_Method {

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id           = 'test_regular';
		$this->instance_id  = $instance_id;
		$this->method_title = 'Test Regular Shipping';
		$this->supports     = array( 'shipping-zones', 'instance-settings' );
	}

	/**
	 * Calculate shipping - not used in tests.
	 *
	 * @param array $package Package array.
	 */
	public function calculate_shipping( $package = array() ) {}
}
