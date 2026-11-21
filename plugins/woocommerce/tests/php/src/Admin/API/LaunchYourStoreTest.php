<?php

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * LaunchYourStoreTest API controller test.
 *
 * @class LaunchYourStoreTest.
 */
class LaunchYourStoreTest extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-admin/launch-your-store';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		// Register an administrator user and log in.
		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $this->user );
	}

	/**
	 * Get test order count.
	 *
	 * @return mixed
	 */
	protected function get_order_count() {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/woopayments/test-orders/count' );
		return $this->server->dispatch( $request )->get_data()['count'];
	}

	/**
	 * Check whether any WooPayments test orders exist.
	 *
	 * @return bool
	 */
	protected function has_test_orders() {
		$request = new WP_REST_Request( 'GET', self::ENDPOINT . '/woopayments/test-orders/has' );
		return $this->server->dispatch( $request )->get_data()['has_orders'];
	}

	/**
	 * Add a new test order
	 *
	 * @return void
	 */
	protected function add_test_order() {
		$order = OrderHelper::create_order( $this->user );
		$order->update_meta_data( '_wcpay_mode', 'test' );
		$order->save();
	}

	/**
	 * Add multiple WooPayments test orders.
	 *
	 * @param int $count Number of orders to create.
	 *
	 * @return void
	 */
	protected function add_test_orders( int $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$this->add_test_order();
		}
	}

	/**
	 * Test order count.
	 *
	 * @return void
	 */
	public function test_it_returns_test_order_count() {
		$count = $this->get_order_count();
		$this->assertEquals( 0, $count );

		$this->add_test_order();
		$this->add_test_order();

		$count = $this->get_order_count();
		$this->assertEquals( 2, $count );
	}

	/**
	 * Test order presence endpoint.
	 *
	 * @return void
	 */
	public function test_it_returns_whether_test_orders_exist() {
		$this->assertFalse( $this->has_test_orders() );

		$this->add_test_order();

		$this->assertTrue( $this->has_test_orders() );
	}

	/**
	 * Test delete endpoint returns 204.
	 *
	 * @return void
	 */
	public function test_delete_endpoint_returns_204() {
		$request = new WP_REST_Request( 'DELETE', self::ENDPOINT . '/woopayments/test-orders' );
		$status  = $this->server->dispatch( $request )->get_status();
		$this->assertEquals( 204, $status );
	}

	/**
	 * Test delete order endpoint.
	 *
	 * @return void
	 */
	public function test_delete_endpoint_deletes_test_order() {
		$this->add_test_order();
		$this->add_test_order();
		$request = new WP_REST_Request( 'DELETE', self::ENDPOINT . '/woopayments/test-orders' );
		$this->server->dispatch( $request );
		$this->assertEquals( 0, $this->get_order_count() );
		$this->assertFalse( $this->has_test_orders() );
	}

	/**
	 * Test delete endpoint deletes test orders across multiple batches.
	 *
	 * @return void
	 */
	public function test_delete_endpoint_deletes_test_orders_across_multiple_batches() {
		$this->add_test_orders( 101 );

		$request = new WP_REST_Request( 'DELETE', self::ENDPOINT . '/woopayments/test-orders' );
		$this->server->dispatch( $request );

		$this->assertEquals( 0, $this->get_order_count() );
		$this->assertFalse( $this->has_test_orders() );
	}
}
