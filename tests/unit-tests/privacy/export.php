<?php
/**
 * Privacy data exporter.
 *
 * @package WooCommerce\Tests\Util
 */
class WC_Test_Privacy_Export extends WC_Unit_Test_Case {

	/**
	 * Order tracking for cleanup.
	 *
	 * @var array
	 */
	protected $orders = array();

	/**
	 * Customer tracking for cleanup.
	 *
	 * @var array
	 */
	protected $customers = array();

	/**
	 * Clean up after test.
	 */
	public function tearDown() {
		foreach ( $this->orders as $object ) {
			$object->delete( true );
		}
		foreach ( $this->customers as $object ) {
			$object->delete( true );
		}
	}

	/**
	 * Test: Data exporter.
	 */
	public function test_data_exporter() {
		$customer1 = WC_Helper_Customer::create_customer( 'customer1', 'password', 'test1@test.com' );
		$customer1->set_billing_email( 'customer1@test.com' );
		$customer1->save();

		$customer2 = WC_Helper_Customer::create_customer( 'customer2', 'password', 'test2@test.com' );
		$customer2->set_billing_email( 'customer2@test.com' );
		$customer2->save();

		$this->customers[] = $customer1;
		$this->customers[] = $customer2;

		// Create a bunch of dummy orders for some users.
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 1 );
		$this->orders[] = WC_Helper_Order::create_order( 2 );
		$this->orders[] = WC_Helper_Order::create_order( 2 );

		// Do a test export and check response.
		$response = WC_Privacy::data_exporter( 'test1@test.com', 0 );

		$this->assertEquals( array(), $response );
	}
}
