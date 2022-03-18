<?php
/**
 * Privacy data exporter.
 *
 * @package WooCommerce\Tests\Util
 */

/**
 * Tests for WC_Privacy_Exporters class.
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
	 * Load up the importer classes since they aren't loaded by default.
	 */
	public function setUp() {
		parent::setUp();

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
	}

	/**
	 * Test: Customer data exporter.
	 */
	public function test_customer_data_exporter() {
		// Test a non existing user.
		$response = WC_Privacy_Exporters::customer_data_exporter( 'doesnotexist@test.com' );
		$this->assertEquals( array(), $response['data'] );

		// Do a test export and check response.
		$response = WC_Privacy_Exporters::customer_data_exporter( 'test1@test.com' );
		$this->assertTrue( $response['done'] );
		$this->assertEquals(
			array(
				array(
					'group_id'          => 'woocommerce_customer',
					'group_label'       => 'Customer Data',
					'group_description' => 'User&#8217;s WooCommerce customer data.',
					'item_id'           => 'user',
					'data'              => array(
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
							'value' => 'San Francisco',
						),
						array(
							'name'  => 'Billing Postal/Zip Code',
							'value' => '94110',
						),
						array(
							'name'  => 'Billing State',
							'value' => 'CA',
						),
						array(
							'name'  => 'Billing Country / Region',
							'value' => 'US',
						),
						array(
							'name'  => 'Email Address',
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
							'value' => 'San Francisco',
						),
						array(
							'name'  => 'Shipping Postal/Zip Code',
							'value' => '94110',
						),
						array(
							'name'  => 'Shipping State',
							'value' => 'CA',
						),
						array(
							'name'  => 'Shipping Country / Region',
							'value' => 'US',
						),
					),
				),
			),
			$response['data']
		);
	}

	/**
	 * Test: Order data exporter.
	 */
	public function test_order_data_exporter() {
		$response = WC_Privacy_Exporters::order_data_exporter( 'test1@test.com', 1 );

		$this->assertEquals( 'woocommerce_orders', $response['data'][0]['group_id'] );
		$this->assertEquals( 'Orders', $response['data'][0]['group_label'] );
		$this->assertContains( 'order-', $response['data'][0]['item_id'] );
		$this->assertArrayHasKey( 'data', $response['data'][0] );
		$this->assertTrue( 8 === count( $response['data'][0]['data'] ), count( $response['data'][0]['data'] ) );

		// Next page should be orders.
		$response = WC_Privacy_Exporters::order_data_exporter( 'test1@test.com', 2 );
		$this->assertTrue( $response['done'] );
		$this->assertTrue( 8 === count( $response['data'][0]['data'] ), count( $response['data'][0]['data'] ) );
	}
}
