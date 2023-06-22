<?php

namespace Automattic\WooCommerce\Tests\Admin\API\Reports\Customers;

use WC_Data_Store;
use WC_Helper_Customer, WC_Helper_Order, WC_Helper_Queue;
use WC_REST_Unit_Test_Case;

/**
 * Tests for the WC Analytics Customers report datastore.
 */
class DataStoreTest extends WC_REST_Unit_Test_Case {
	/**
	 * @var WC_Data_Store|null
	 */
	private $sut = null;

	/**
	 * Set up before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->set_up_customer_data();

		$this->sut = WC_Data_Store::load( 'report-customers' );
	}

	/**
	 * Generate customer data for tests.
	 *
	 * @return void
	 */
	private function set_up_customer_data() {
		$customer = WC_Helper_Customer::create_customer( 'onlyatest', 'password', 'onlyatest@example.com' );
		$customer->set_first_name( 'Jay' );
		$customer->set_last_name( 'Ramathorn' );
		$customer->save();

		$customer = WC_Helper_Customer::create_customer( 'jaytest', 'password', 'justatest@example.com' );
		$customer->set_first_name( 'Jason' );
		$customer->set_last_name( 'Roto' );
		$customer->save();

		$customer = WC_Helper_Customer::create_customer( 'womack2001', 'password', 'mac@jaybird.local' );
		$customer->set_first_name( 'Steve' );
		$customer->set_last_name( 'Letme' );
		$customer->save();

		$customer = WC_Helper_Customer::create_customer( 'sotero', 'password', 'bananas@example.com' );
		$customer->set_first_name( 'Carl' );
		$customer->set_last_name( 'Foster' );
		$customer->save();

		$order = WC_Helper_Order::create_order( 0 ); // Order with guest customer (no account).
		$order->set_billing_email( 'rjayfarva@ramrod.local' );
		$order->set_billing_last_name( 'Arjay' );
		$order->save();

		WC_Helper_Queue::run_all_pending(); // Ensure order customer data is synced to lookup table.
	}

	/**
	 * @testdox The `get_data` method should return customer data, including from orders made by non-registered guests.
	 */
	public function test_get_data() {
		$query_args = array(
			'force_cache_refresh' => true,
			'orderby'             => 'name',
			'order_before'        => '',
			'order_after'         => '',
		);

		$data = $this->sut->get_data( $query_args );

		$this->assertEquals( 5, $data->total );
	}

	/**
	 * @testdox The `get_data` method should return different customer data depending on which field(s) are being searched.
	 */
	public function test_get_data_searchby() {
		$query_args = array(
			'force_cache_refresh' => true,
			'orderby'             => 'name',
			'order_before'        => '',
			'order_after'         => '',
			'search'              => 'Jay',
		);

		$query_args['searchby'] = 'name';

		$data = $this->sut->get_data( $query_args );
		$this->assertEquals( 2, $data->total );

		$query_args['searchby'] = 'username';

		$data = $this->sut->get_data( $query_args );
		$this->assertEquals( 1, $data->total );

		$query_args['searchby'] = 'email';

		$data = $this->sut->get_data( $query_args );
		$this->assertEquals( 2, $data->total );

		$query_args['searchby'] = 'all';

		$data = $this->sut->get_data( $query_args );
		$this->assertEquals( 4, $data->total );
	}
}
