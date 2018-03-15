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
			array(
				'name'  => 'Billing Address 1',
				'value' => '123 South Street',
			),
			array(
				'name'  => 'Billing Address 2',
				'value' => 'Apt 1',
			),
			array(
				'name'  => 'Billing City',
				'value' => 'Philadelphia',
			),
			array(
				'name'  => 'Billing Postal/Zip Code',
				'value' => '19123',
			),
			array(
				'name'  => 'Billing State',
				'value' => 'PA',
			),
			array(
				'name'  => 'Billing Country',
				'value' => 'US',
			),
			array(
				'name'  => 'Billing Email',
				'value' => 'customer1@test.com',
			),
			array(
				'name'  => 'Shipping Address 1',
				'value' => '123 South Street',
			),
			array(
				'name'  => 'Shipping Address 2',
				'value' => 'Apt 1',
			),
			array(
				'name'  => 'Shipping City',
				'value' => 'Philadelphia',
			),
			array(
				'name'  => 'Shipping Postal/Zip Code',
				'value' => '19123',
			),
			array(
				'name'  => 'Shipping State',
				'value' => 'PA',
			),
			array(
				'name'  => 'Shipping Country',
				'value' => 'US',
			),
		), $response['data'] );

		// Next page should be orders.
		$response = WC_Privacy::data_exporter( 'test1@test.com', 1 );
		$this->assertTrue( 50 === count( $response['data'] ) );
		$this->assertArrayHasKey( 'name', $response['data'][0] );
		$this->assertArrayHasKey( 'value', $response['data'][0] );
		$this->assertContains( 'IP Address', $response['data'][0]['name'] );
		$this->assertContains( 'Billing Address', $response['data'][1]['name'] );
		$this->assertContains( 'Shipping Address', $response['data'][2]['name'] );
		$this->assertContains( 'Billing Phone', $response['data'][3]['name'] );
		$this->assertContains( 'Billing Email', $response['data'][4]['name'] );

		// Next page should be orders.
		$response = WC_Privacy::data_exporter( 'test1@test.com', 2 );
		$this->assertTrue( $response['done'] );
		$this->assertTrue( 5 === count( $response['data'] ) );
	}
}
