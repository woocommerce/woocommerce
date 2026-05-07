<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Helper_Product;
use WC_Order;
use WC_Unit_Test_Case;
use WPAjaxDieContinueException;

/**
 * Tests for the Review Order submission handler.
 */
class SubmissionHandlerTest extends WC_Unit_Test_Case {

	/**
	 * Reset state between tests.
	 */
	public function tearDown(): void {
		$_POST = array();
		update_option( 'comment_moderation', '0' );
		remove_all_filters( 'woocommerce_review_order_submitted' );
		remove_all_filters( 'woocommerce_review_order_eligible_statuses' );
		remove_all_filters( 'wp_die_ajax_handler' );
		remove_all_filters( 'wp_send_json_handler' );
		remove_all_filters( 'wp_doing_ajax' );
		parent::tearDown();
	}

	/**
	 * Build a completed order with the given number of products.
	 *
	 * @param int $product_count How many products to attach.
	 * @return array{order:WC_Order, product_ids:int[], item_ids:int[]}
	 */
	private function make_order( int $product_count = 1 ): array {
		$order = OrderHelper::create_order();
		// Wipe the default item.
		foreach ( $order->get_items() as $item ) {
			$order->remove_item( $item->get_id() );
		}
		$order->set_billing_first_name( 'Jane' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_email( 'jane@example.test' );
		$order->set_status( OrderStatus::COMPLETED );

		$product_ids = array();
		for ( $i = 0; $i < $product_count; $i++ ) {
			$product       = WC_Helper_Product::create_simple_product();
			$product_ids[] = $product->get_id();
			$order->add_product( $product, 1 );
		}
		$order->save();

		$item_ids = array();
		foreach ( $order->get_items() as $item ) {
			$item_ids[] = $item->get_id();
		}

		return array(
			'order'       => $order,
			'product_ids' => $product_ids,
			'item_ids'    => $item_ids,
		);
	}

	/**
	 * Invoke the handler and capture the JSON it would have sent.
	 *
	 * @return array{success:bool, data:mixed, status:int}
	 */
	private function dispatch(): array {
		$response = array(
			'success' => false,
			'data'    => null,
			'status'  => 200,
		);

		$capture = static function ( $payload, $status ) use ( &$response ) {
			$response['success'] = ! empty( $payload['success'] );
			$response['data']    = $payload['data'] ?? null;
			$response['status']  = (int) ( $status ?? 200 );
		};

		add_filter( 'wp_die_ajax_handler', static fn() => static fn() => null );

		add_filter(
			'wp_send_json_handler',
			static function () use ( $capture ) {
				return $capture;
			}
		);

		add_filter(
			'wp_doing_ajax',
			static function () {
				return true;
			}
		);

		// `wp_send_json_*` always calls `wp_die`, but we can short-circuit
		// the JSON output by hooking the early `wp_die_ajax_handler`.
		// Easier: just call the handler and trust it sends headers; capture
		// via output buffering.
		ob_start();
		$handler = new SubmissionHandler();
		try {
			$handler->handle();
		} catch ( WPAjaxDieContinueException $e ) {
			// Expected: wp_send_json_* calls wp_die().
			unset( $e );
		}
		$body = (string) ob_get_clean();

		$decoded = json_decode( $body, true );
		if ( is_array( $decoded ) ) {
			$response['success'] = ! empty( $decoded['success'] );
			$response['data']    = $decoded['data'] ?? null;
		}
		return $response;
	}

	/**
	 * @testdox Handler rejects requests with a missing or bad nonce.
	 */
	public function test_rejects_bad_nonce(): void {
		$built = $this->make_order( 1 );
		/** @var WC_Order $order */
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => 'not-the-right-nonce',
		);

		$response = $this->dispatch();

		$this->assertFalse( $response['success'] );
	}

	/**
	 * @testdox Handler rejects mismatched order keys.
	 */
	public function test_rejects_bad_key(): void {
		$built = $this->make_order( 1 );
		/** @var WC_Order $order */
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => 'wc_order_NOPE',
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
		);

		$response = $this->dispatch();

		$this->assertFalse( $response['success'] );
	}

	/**
	 * @testdox A valid submission inserts a comment with rating + verified meta.
	 */
	public function test_inserts_review_with_meta(): void {
		$built = $this->make_order( 1 );
		/** @var WC_Order $order */
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 5,
					'text'          => 'Excellent product, highly recommended.',
				),
			),
		);

		$response = $this->dispatch();
		$this->assertTrue( $response['success'] );
		$this->assertIsArray( $response['data'] );
		$this->assertArrayHasKey( 'results', $response['data'] );
		$results = $response['data']['results'];
		$this->assertCount( 1, $results );
		$row = reset( $results );
		$this->assertSame( 'ok', $row['status'] );
		$this->assertArrayHasKey( 'comment_id', $row );

		$comment = get_comment( $row['comment_id'] );
		$this->assertNotNull( $comment );
		$this->assertSame( (int) $product_id, (int) $comment->comment_post_ID );
		$this->assertSame( 'review', $comment->comment_type );
		$this->assertSame( '5', get_comment_meta( $row['comment_id'], 'rating', true ) );
		$this->assertSame( '1', get_comment_meta( $row['comment_id'], 'verified', true ) );
	}

	/**
	 * @testdox Rows with no rating are skipped silently.
	 */
	public function test_skips_rows_without_rating(): void {
		$built = $this->make_order( 2 );
		/** @var WC_Order $order */
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $built['product_ids'][0],
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 4,
					'text'          => 'Great.',
				),
				array(
					'product_id'    => $built['product_ids'][1],
					'order_item_id' => $built['item_ids'][1],
					'rating'        => 0,
					'text'          => '',
				),
			),
		);

		$response = $this->dispatch();
		$this->assertTrue( $response['success'] );
		$results = $response['data']['results'];
		$this->assertCount( 1, $results, 'Skipped row should not appear in the results.' );
	}

	/**
	 * @testdox When comment_moderation is enabled, rows return pending_moderation.
	 */
	public function test_pending_moderation(): void {
		update_option( 'comment_moderation', '1' );

		$built      = $this->make_order( 1 );
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 4,
					'text'          => 'Pending text.',
				),
			),
		);

		$response = $this->dispatch();
		$results  = $response['data']['results'];
		$row      = reset( $results );
		$this->assertSame( 'pending_moderation', $row['status'] );

		$comment = get_comment( $row['comment_id'] );
		$this->assertSame( '0', $comment->comment_approved );
	}

	/**
	 * @testdox Rows referencing a product not on the order fail per-row, others succeed.
	 */
	public function test_per_row_isolation(): void {
		$built = $this->make_order( 1 );
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $built['product_ids'][0],
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 5,
				),
				array(
					'product_id'    => 999999,
					'order_item_id' => 999999,
					'rating'        => 5,
				),
			),
		);

		$response = $this->dispatch();
		$results  = $response['data']['results'];

		$this->assertCount( 2, $results );
		$ok_count    = 0;
		$error_count = 0;
		foreach ( $results as $row ) {
			if ( 'ok' === $row['status'] ) {
				++$ok_count;
			} elseif ( 'error' === $row['status'] ) {
				++$error_count;
			}
		}
		$this->assertSame( 1, $ok_count );
		$this->assertSame( 1, $error_count );
	}

	/**
	 * @testdox Out-of-range ratings surface as a per-row error (invalid_rating).
	 */
	public function test_invalid_rating_returns_error(): void {
		$built = $this->make_order( 1 );
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $built['product_ids'][0],
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 7,
				),
			),
		);

		$response = $this->dispatch();
		$row      = $response['data']['results'][0];

		$this->assertSame( 'error', $row['status'] );
		$this->assertSame( 'invalid_rating', $row['error'] );
	}

	/**
	 * @testdox Submitting a product_id that doesn't match the order item surfaces product_mismatch.
	 */
	public function test_product_mismatch_returns_error(): void {
		$built = $this->make_order( 1 );
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $built['product_ids'][0] + 99999,
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 4,
				),
			),
		);

		$response = $this->dispatch();
		$row      = $response['data']['results'][0];

		$this->assertSame( 'error', $row['status'] );
		$this->assertSame( 'product_mismatch', $row['error'] );
	}

	/**
	 * @testdox Order completed-at meta is set when every item has been reviewed.
	 */
	public function test_marks_order_complete_when_every_item_reviewed(): void {
		$built = $this->make_order( 2 );
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $built['product_ids'][0],
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 5,
				),
				array(
					'product_id'    => $built['product_ids'][1],
					'order_item_id' => $built['item_ids'][1],
					'rating'        => 4,
				),
			),
		);

		$response = $this->dispatch();
		$this->assertTrue( $response['success'] );

		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty( $fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ) );
	}

	/**
	 * @testdox Order completed-at meta is NOT set when some items are still unreviewed.
	 */
	public function test_does_not_mark_complete_when_one_item_unreviewed(): void {
		$built = $this->make_order( 2 );
		$order = $built['order'];

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $built['product_ids'][0],
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 5,
				),
				// Second product intentionally omitted.
			),
		);

		$this->dispatch();

		$fresh = wc_get_order( $order->get_id() );
		$this->assertEmpty( $fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ) );
	}

	/**
	 * @testdox A successful submission fires the woocommerce_review_order_submitted action with order + per-row results.
	 */
	public function test_fires_review_order_submitted_action(): void {
		$built      = $this->make_order( 1 );
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		$captured = array(
			'order'   => null,
			'results' => null,
			'calls'   => 0,
		);

		add_action(
			'woocommerce_review_order_submitted',
			static function ( $order_arg, $results_arg ) use ( &$captured ) {
				$captured['order']   = $order_arg;
				$captured['results'] = $results_arg;
				++$captured['calls'];
			},
			10,
			2
		);

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 4,
				),
			),
		);

		$this->dispatch();

		$this->assertSame( 1, $captured['calls'], 'Action should fire exactly once per submission.' );
		$this->assertInstanceOf( WC_Order::class, $captured['order'] );
		$this->assertSame( $order->get_id(), $captured['order']->get_id() );
		$this->assertIsArray( $captured['results'] );
		$this->assertCount( 1, $captured['results'] );
		$row = reset( $captured['results'] );
		$this->assertSame( 'ok', $row['status'] );
	}

	/**
	 * @testdox Submissions are rejected when the order's status is no longer eligible.
	 */
	public function test_rejects_when_order_status_ineligible(): void {
		$built = $this->make_order( 1 );
		$order = $built['order'];
		$order->set_status( OrderStatus::PROCESSING );
		$order->save();

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $built['product_ids'][0],
					'order_item_id' => $built['item_ids'][0],
					'rating'        => 5,
				),
			),
		);

		$response = $this->dispatch();

		$this->assertFalse( $response['success'] );
	}
}
