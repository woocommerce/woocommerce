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
	 * Add lightweight test-mode orders, without line items, for volume tests.
	 *
	 * @param int $count How many orders to create.
	 * @return void
	 */
	protected function add_empty_test_orders( int $count ) {
		for ( $i = 0; $i < $count; $i++ ) {
			$order = wc_create_order();
			$order->update_meta_data( '_wcpay_mode', 'test' );
			$order->save();
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

		$count = $this->get_order_count();
		$this->assertEquals( 1, $count );
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
		$request = new WP_REST_Request( 'DELETE', self::ENDPOINT . '/woopayments/test-orders' );
		$this->server->dispatch( $request );
		$this->assertEquals( 0, $this->get_order_count() );
	}

	/**
	 * The count must cover every test order, not just the first page of results.
	 *
	 * @return void
	 */
	public function test_count_is_not_capped_by_posts_per_page() {
		update_option( 'posts_per_page', 5 );

		$this->add_empty_test_orders( 8 );
		wc_create_order();

		$this->assertSame( 8, $this->get_order_count(), 'Every test order should be counted, and orders without test-mode meta should not be.' );
	}

	/**
	 * Deletion must remove every test order, including past the first batch of 100.
	 *
	 * @return void
	 */
	public function test_delete_endpoint_deletes_all_test_orders_across_batches() {
		$this->add_empty_test_orders( 105 );
		$live_order = wc_create_order();

		$request = new WP_REST_Request( 'DELETE', self::ENDPOINT . '/woopayments/test-orders' );
		$status  = $this->server->dispatch( $request )->get_status();

		$this->assertEquals( 204, $status );
		$this->assertSame( 0, $this->get_order_count(), 'Every test order should be gone after the delete request.' );

		$live_order = wc_get_order( $live_order->get_id() );
		$this->assertNotFalse( $live_order, 'Orders without test-mode meta should survive the delete request.' );
		$this->assertNotSame( 'trash', $live_order->get_status(), 'Orders without test-mode meta should not be trashed.' );
	}
}
