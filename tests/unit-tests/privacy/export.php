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
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer1->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer2->get_id() );
		$this->orders[] = WC_Helper_Order::create_order( $customer2->get_id() );

		// Test a non existing user.
		$response = WC_Privacy::data_exporter( 'doesnotexist@test.com', 0 );
		$this->assertEquals( array(), $response['data'] );

		// Do a test export and check response.
		$response = WC_Privacy::data_exporter( 'test1@test.com', 0 );
		$this->assertFalse( $response['done'] );
		$this->assertEquals( array(
			'Billing Address 1'        => '123 South Street',
			'Billing Address 2'        => 'Apt 1',
			'Billing City'             => 'Philadelphia',
			'Billing Postal/Zip Code'  => '19123',
			'Billing State'            => 'PA',
			'Billing Country'          => 'US',
			'Billing Email'            => 'customer1@test.com',
			'Shipping Address 1'       => '123 South Street',
			'Shipping Address 2'       => 'Apt 1',
			'Shipping City'            => 'Philadelphia',
			'Shipping Postal/Zip Code' => '19123',
			'Shipping State'           => 'PA',
			'Shipping Country'         => 'US',
		), $response['data'] );

		// Next page should be orders.
		$response = WC_Privacy::data_exporter( 'test1@test.com', 1 );
		$this->assertFalse( $response['done'] );
		$this->assertArrayHasKey( 'orders', $response['data'] );
		$this->assertTrue( 10 === count( $response['data']['orders'] ) );
		$this->assertArrayHasKey( 'Order ID', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Order Number', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'IP Address', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing First Name', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing Last Name', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing Company', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing Address 1', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing City', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing Postal/Zip Code', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing State', $response['data']['orders'][0] );
		$this->assertArrayHasKey( 'Billing Country', $response['data']['orders'][0] );

		// Next page should be orders.
		$response = WC_Privacy::data_exporter( 'test1@test.com', 2 );
		$this->assertTrue( $response['done'] );
		$this->assertArrayHasKey( 'orders', $response['data'] );
		$this->assertTrue( 1 === count( $response['data']['orders'] ), count( $response['data']['orders'] ) );
	}
}
