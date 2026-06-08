<?php
/**
 * Tests for the woocommerce_submit_order_reviews AJAX endpoint,
 * handled by Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler.
 *
 * @package WooCommerce\Tests\WC_AJAX.
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler;

/**
 * Tests for the submit_order_reviews AJAX handler.
 */
class WC_AJAX_Submit_Order_Reviews_Test extends \WP_Ajax_UnitTestCase {

	/**
	 * Set up: reset per-request eligibility cache and moderation option.
	 */
	public function set_up(): void {
		parent::set_up();
		ItemEligibility::reset_cache();
		update_option( 'comment_moderation', '0' );
	}

	/**
	 * Tear down: clean $_POST and reset state.
	 */
	public function tear_down(): void {
		unset( $_POST['_wcnonce'], $_POST['order_id'], $_POST['key'], $_POST['reviews'] );
		update_option( 'comment_moderation', '0' );
		ItemEligibility::reset_cache();
		parent::tear_down();
	}

	/**
	 * Create a completed order with N simple products and return ids.
	 *
	 * @param int $product_count Number of products to add.
	 * @return array{order: WC_Order, product_ids: int[], order_item_ids: int[]}
	 */
	private function create_order_with_products( int $product_count = 2 ): array {
		$product_ids    = array();
		$order_item_ids = array();
		$order          = wc_create_order( array( 'status' => OrderStatus::COMPLETED ) );

		$order->set_billing_first_name( 'John' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_email( 'john@example.com' );

		for ( $i = 0; $i < $product_count; $i++ ) {
			$product           = WC_Helper_Product::create_simple_product();
			$product_ids[]     = $product->get_id();
			$item              = $order->add_product( $product, 1 );
			$order_item_ids[]  = $item->get_id();
		}

		$order->save();

		return array(
			'order'          => $order,
			'product_ids'    => $product_ids,
			'order_item_ids' => $order_item_ids,
		);
	}

	/**
	 * Set up the $_POST data for a submit_order_reviews request.
	 *
	 * @param WC_Order $order   The order.
	 * @param array    $reviews Review rows.
	 */
	private function setup_post_data( WC_Order $order, array $reviews ): void {
		$_POST['_wcnonce'] = wp_create_nonce( SubmissionHandler::ACTION );
		$_POST['order_id'] = $order->get_id();
		$_POST['key']      = $order->get_order_key();
		$_POST['reviews']  = $reviews;
	}

	/**
	 * Trigger the AJAX handler and return the decoded response.
	 *
	 * @return array|null
	 */
	private function do_ajax(): ?array {
		$output_buffering_level = ob_get_level();

		try {
			$this->_handleAjax( SubmissionHandler::ACTION );
		} catch ( Exception $e ) {
			if ( ob_get_level() === $output_buffering_level + 1 ) {
				ob_get_clean();
			}
		}

		$result               = json_decode( $this->_last_response, true );
		$this->_last_response = false;

		return $result;
	}

	/**
	 * @testdox Should return 403 when nonce is invalid.
	 */
	public function test_returns_error_for_invalid_nonce(): void {
		$data  = $this->create_order_with_products( 1 );
		$order = $data['order'];

		$_POST['_wcnonce'] = 'invalid-nonce';
		$_POST['order_id'] = $order->get_id();
		$_POST['key']      = $order->get_order_key();
		$_POST['reviews']  = array(
			array(
				'order_item_id' => $data['order_item_ids'][0],
				'product_id'    => $data['product_ids'][0],
				'rating'        => 5,
			),
		);

		$response = $this->do_ajax();

		$this->assertFalse( $response['success'] ?? true );
		$this->assertSame( 403, $response['data']['code'] ?? 0 );
	}

	/**
	 * @testdox Should return 404 when the order key does not match.
	 */
	public function test_returns_error_for_invalid_order_key(): void {
		$data  = $this->create_order_with_products( 1 );
		$order = $data['order'];

		$_POST['_wcnonce'] = wp_create_nonce( SubmissionHandler::ACTION );
		$_POST['order_id'] = $order->get_id();
		$_POST['key']      = 'wc_order_INVALID';
		$_POST['reviews']  = array();

		$response = $this->do_ajax();

		$this->assertFalse( $response['success'] ?? true );
		$this->assertSame( 404, $response['data']['code'] ?? 0 );
	}

	/**
	 * @testdox Should return 404 when the order is not in an eligible status.
	 */
	public function test_returns_error_for_non_eligible_order_status(): void {
		$order = wc_create_order( array( 'status' => OrderStatus::PENDING ) );
		$order->set_billing_email( 'john@example.com' );
		$order->save();

		$this->setup_post_data( $order, array() );

		$response = $this->do_ajax();

		$this->assertFalse( $response['success'] ?? true );
		$this->assertSame( 404, $response['data']['code'] ?? 0 );
	}

	/**
	 * @testdox Should insert a review and return ok with a comment_id.
	 */
	public function test_inserts_review_successfully(): void {
		$data       = $this->create_order_with_products( 1 );
		$order      = $data['order'];
		$product_id = $data['product_ids'][0];

		$reviews = array(
			array(
				'order_item_id' => $data['order_item_ids'][0],
				'product_id'    => $product_id,
				'rating'        => 5,
				'text'          => 'Great product!',
			),
		);

		$this->setup_post_data( $order, $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['results'] );
		$this->assertSame( 'ok', $response['data']['results'][0]['status'] );
		$this->assertSame( $product_id, $response['data']['results'][0]['product_id'] );
		$this->assertArrayHasKey( 'comment_id', $response['data']['results'][0] );

		$comment = get_comment( $response['data']['results'][0]['comment_id'] );
		$this->assertSame( 'review', $comment->comment_type );
		$this->assertSame( 'Great product!', $comment->comment_content );
		$this->assertSame( 'John Doe', $comment->comment_author );
		$this->assertSame( 'john@example.com', $comment->comment_author_email );
		$this->assertSame( 5, (int) get_comment_meta( $comment->comment_ID, 'rating', true ) );
		$this->assertSame( 1, (int) get_comment_meta( $comment->comment_ID, 'verified', true ) );
		$this->assertSame( $order->get_id(), (int) get_comment_meta( $comment->comment_ID, ItemEligibility::ORDER_META_KEY, true ) );
	}

	/**
	 * @testdox Should silently skip rows with rating 0.
	 */
	public function test_skips_rows_without_rating(): void {
		$data       = $this->create_order_with_products( 1 );
		$product_id = $data['product_ids'][0];

		$reviews = array(
			array(
				'order_item_id' => $data['order_item_ids'][0],
				'product_id'    => $product_id,
				'rating'        => 0,
				'text'          => '',
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$this->assertSame( array(), $response['data']['results'] );
	}

	/**
	 * @testdox Should return error: invalid_rating when rating is outside 1-5.
	 */
	public function test_rejects_invalid_rating_range(): void {
		$data       = $this->create_order_with_products( 1 );
		$product_id = $data['product_ids'][0];

		$reviews = array(
			array(
				'order_item_id' => $data['order_item_ids'][0],
				'product_id'    => $product_id,
				'rating'        => 10,
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['results'] );
		$this->assertSame( 'error', $response['data']['results'][0]['status'] );
		$this->assertSame( 'invalid_rating', $response['data']['results'][0]['error'] );
	}

	/**
	 * @testdox Should return error: invalid_row when product is not in the order.
	 */
	public function test_rejects_product_not_in_order(): void {
		$data          = $this->create_order_with_products( 1 );
		$other_product = WC_Helper_Product::create_simple_product();

		$reviews = array(
			array(
				'order_item_id' => 0,
				'product_id'    => $other_product->get_id(),
				'rating'        => 4,
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$this->assertCount( 1, $response['data']['results'] );
		$this->assertSame( 'error', $response['data']['results'][0]['status'] );
		$this->assertSame( 'invalid_row', $response['data']['results'][0]['error'] );
	}

	/**
	 * @testdox Should process rows independently: one ok, one error, one skipped (excluded).
	 */
	public function test_processes_multiple_rows_independently(): void {
		$data          = $this->create_order_with_products( 2 );
		$product_ids   = $data['product_ids'];
		$item_ids      = $data['order_item_ids'];
		$other_product = WC_Helper_Product::create_simple_product();

		$reviews = array(
			array(
				'order_item_id' => $item_ids[0],
				'product_id'    => $product_ids[0],
				'rating'        => 5,
				'text'          => 'Excellent!',
			),
			array(
				'order_item_id' => 0,
				'product_id'    => $other_product->get_id(),
				'rating'        => 3,
			),
			array(
				'order_item_id' => $item_ids[1],
				'product_id'    => $product_ids[1],
				'rating'        => 0,
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$results = $response['data']['results'];
		// Skipped (rating=0) row is excluded, so only 2 results returned.
		$this->assertCount( 2, $results );
		$this->assertSame( 'ok', $results[0]['status'] );
		$this->assertSame( 'error', $results[1]['status'] );
		$this->assertSame( 'invalid_row', $results[1]['error'] );
	}

	/**
	 * @testdox Should return pending_moderation when comment moderation is enabled.
	 */
	public function test_respects_comment_moderation_setting(): void {
		update_option( 'comment_moderation', '1' );

		$data       = $this->create_order_with_products( 1 );
		$product_id = $data['product_ids'][0];

		$reviews = array(
			array(
				'order_item_id' => $data['order_item_ids'][0],
				'product_id'    => $product_id,
				'rating'        => 4,
				'text'          => 'Nice product',
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'pending_moderation', $response['data']['results'][0]['status'] );
		$comment = get_comment( $response['data']['results'][0]['comment_id'] );
		$this->assertSame( '0', $comment->comment_approved );
	}

	/**
	 * @testdox Should set _wc_review_request_completed_at when all items are reviewed.
	 */
	public function test_marks_review_request_completed(): void {
		$data        = $this->create_order_with_products( 2 );
		$product_ids = $data['product_ids'];
		$item_ids    = $data['order_item_ids'];

		$reviews = array(
			array(
				'order_item_id' => $item_ids[0],
				'product_id'    => $product_ids[0],
				'rating'        => 5,
			),
			array(
				'order_item_id' => $item_ids[1],
				'product_id'    => $product_ids[1],
				'rating'        => 4,
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$order       = wc_get_order( $data['order']->get_id() );
		$completed_at = $order->get_meta( SubmissionHandler::COMPLETED_META_KEY );
		$this->assertNotEmpty( $completed_at );
	}

	/**
	 * @testdox Should not set completion meta when only some items are reviewed.
	 */
	public function test_does_not_mark_completed_when_partial(): void {
		$data        = $this->create_order_with_products( 2 );
		$product_ids = $data['product_ids'];
		$item_ids    = $data['order_item_ids'];

		$reviews = array(
			array(
				'order_item_id' => $item_ids[0],
				'product_id'    => $product_ids[0],
				'rating'        => 5,
			),
			array(
				'order_item_id' => $item_ids[1],
				'product_id'    => $product_ids[1],
				'rating'        => 0,
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$response = $this->do_ajax();

		$this->assertTrue( $response['success'] );
		$order       = wc_get_order( $data['order']->get_id() );
		$completed_at = $order->get_meta( SubmissionHandler::COMPLETED_META_KEY );
		$this->assertEmpty( $completed_at );
	}

	/**
	 * @testdox Should fire woocommerce_review_order_submitted action with the order and results.
	 */
	public function test_fires_review_order_submitted_action(): void {
		$data       = $this->create_order_with_products( 1 );
		$product_id = $data['product_ids'][0];

		$reviews = array(
			array(
				'order_item_id' => $data['order_item_ids'][0],
				'product_id'    => $product_id,
				'rating'        => 5,
			),
		);

		$this->setup_post_data( $data['order'], $reviews );

		$captured_args = array();
		$callback      = function ( $fired_order, $fired_results ) use ( &$captured_args ) {
			$captured_args = array(
				'order'   => $fired_order,
				'results' => $fired_results,
			);
		};

		add_action( 'woocommerce_review_order_submitted', $callback, 10, 2 );

		$this->do_ajax();

		$this->assertNotEmpty( $captured_args );
		$this->assertInstanceOf( WC_Order::class, $captured_args['order'] );
		$this->assertIsArray( $captured_args['results'] );
		$this->assertCount( 1, $captured_args['results'] );

		remove_action( 'woocommerce_review_order_submitted', $callback );
	}
}
