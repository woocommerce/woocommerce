<?php
/**
 * Test the API controller class that handles the /activity-panel/counts REST response.
 *
 * @package WooCommerce\Admin\Tests\Admin\API
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Admin\API;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Enums\ProductStatus;
use WC_Helper_Order;
use WC_Helper_Product;
use WC_REST_Unit_Test_Case;
use WP_Error;
use WP_REST_Request;

/**
 * ActivityPanelCounts API controller test.
 *
 * @class ActivityPanelCountsTest.
 */
class ActivityPanelCountsTest extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoint.
	 *
	 * @var string
	 */
	const ENDPOINT = '/wc-analytics/activity-panel/counts';

	/**
	 * Set up.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Create an unapproved (hold) product review.
	 *
	 * @param int $product_id Product ID.
	 * @return int Comment ID.
	 */
	private function create_unapproved_review( $product_id ) {
		return wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => 'shopper',
				'comment_author_email' => 'shopper@example.com',
				'comment_content'      => 'Awaiting moderation.',
				'comment_approved'     => 0,
				'comment_type'         => 'review',
			)
		);
	}

	/**
	 * Test that the response has the expected shape and counts for a store manager.
	 */
	public function test_returns_counts_for_manager() {
		wp_set_current_user( $this->user );

		WC_Helper_Order::create_order( 1, null, array( 'status' => OrderStatus::PROCESSING ) );
		WC_Helper_Order::create_order( 1, null, array( 'status' => OrderStatus::ON_HOLD ) );
		WC_Helper_Order::create_order( 1, null, array( 'status' => OrderStatus::COMPLETED ) );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_low_stock_amount( 2 );
		$product->set_stock_quantity( 1 );
		$product->save();

		$this->create_unapproved_review( $product->get_id() );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, $data['orders_to_fulfill_count'] );
		$this->assertEquals( 1, $data['reviews_to_moderate_count'] );
		$this->assertEquals( 1, $data['products_low_in_stock_count'] );
	}

	/**
	 * Test that a user without manage_woocommerce is denied.
	 */
	public function test_permission_denied_for_user_without_manage_woocommerce() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test that a custom order_statuses param is respected instead of the default.
	 */
	public function test_custom_order_statuses_param_is_respected() {
		wp_set_current_user( $this->user );

		WC_Helper_Order::create_order( 1, null, array( 'status' => OrderStatus::PROCESSING ) );
		WC_Helper_Order::create_order( 1, null, array( 'status' => OrderStatus::ON_HOLD ) );

		$request = new WP_REST_Request( 'GET', self::ENDPOINT );
		$request->set_param( 'order_statuses', array( OrderStatus::PROCESSING ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, $data['orders_to_fulfill_count'] );
	}

	/**
	 * Test that the low stock count matches the existing dedicated endpoint, i.e. this
	 * controller isn't diverging from the counting logic it delegates to.
	 */
	public function test_low_stock_count_matches_dedicated_endpoint() {
		wp_set_current_user( $this->user );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_low_stock_amount( 5 );
		$product->set_stock_quantity( 3 );
		$product->save();

		$counts_request  = new WP_REST_Request( 'GET', self::ENDPOINT );
		$counts_response = $this->server->dispatch( $counts_request );
		$counts_data     = $counts_response->get_data();

		$dedicated_request = new WP_REST_Request( 'GET', '/wc-analytics/products/count-low-in-stock' );
		$dedicated_request->set_param( 'status', ProductStatus::PUBLISH );
		$dedicated_response = $this->server->dispatch( $dedicated_request );
		$dedicated_data     = $dedicated_response->get_data();

		$this->assertEquals(
			$dedicated_data['total'],
			$counts_data['products_low_in_stock_count']
		);
	}

	/**
	 * Test that a failed sub-request yields null for that count, not 0, so a merchant can't
	 * mistake "sub-request failed" for a genuine zero count.
	 */
	public function test_failed_sub_request_returns_null_not_zero() {
		wp_set_current_user( $this->user );

		WC_Helper_Order::create_order( 1, null, array( 'status' => OrderStatus::PROCESSING ) );

		$failing_route = '/wc-analytics/products/count-low-in-stock';
		$force_error   = function ( $result, $server, $request ) use ( $failing_route ) {
			if ( $request->get_route() === $failing_route ) {
				return new WP_Error( 'test_forced_failure', 'Forced failure for test.', array( 'status' => 500 ) );
			}
			return $result;
		};

		add_filter( 'rest_pre_dispatch', $force_error, 10, 3 );

		try {
			$request  = new WP_REST_Request( 'GET', self::ENDPOINT );
			$response = $this->server->dispatch( $request );
			$data     = $response->get_data();
		} finally {
			remove_filter( 'rest_pre_dispatch', $force_error, 10 );
		}

		$this->assertEquals( 200, $response->get_status() );
		$this->assertNull( $data['products_low_in_stock_count'] );
		$this->assertEquals( 1, $data['orders_to_fulfill_count'] );
	}
}
