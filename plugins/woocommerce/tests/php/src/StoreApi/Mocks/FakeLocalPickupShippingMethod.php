<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\StoreApi\Mocks;

use WC_Shipping_Method;

/**
 * Fake shipping method that supports local pickup for testing.
 */
class FakeLocalPickupShippingMethod extends WC_Shipping_Method {

	/**
	 * Constructor.
	 *
	 * @param int $instance_id Instance ID.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id           = 'test_local_pickup';
		$this->instance_id  = $instance_id;
		$this->method_title = 'Test Local Pickup';
		$this->supports     = array( 'shipping-zones', 'instance-settings', 'local-pickup' );
	}

	/**
	 * Calculate shipping - not used in tests.
	 *
	 * @param array $package Package array.
	 */
	public function calculate_shipping( $package = array() ) {}
}
