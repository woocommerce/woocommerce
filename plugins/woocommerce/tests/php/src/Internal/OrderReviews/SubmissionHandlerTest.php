<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\Internal\OrderReviews\SubmissionHandler;
use WC_Helper_Product;
use WC_Order;
use WC_Unit_Test_Case;
use WPAjaxDieContinueException;

/**
 * Tests for the Review Order submission handler.
 */
class SubmissionHandlerTest extends WC_Unit_Test_Case {

	/**
	 * Feature flag gates the OrderReviews stack.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_customer_review_request_enabled', 'yes' );
	}

	/**
	 * Reset state between tests.
	 */
	public function tearDown(): void {
		$_POST = array();
		update_option( 'comment_moderation', '0' );
		update_option( 'comment_max_links', 2 );
		update_option( 'moderation_keys', '' );
		remove_all_filters( 'woocommerce_review_order_submitted' );
		remove_all_filters( 'woocommerce_review_order_eligible_statuses' );
		remove_all_filters( 'woocommerce_review_order_eligible_items' );
		remove_all_filters( 'wp_die_ajax_handler' );
		remove_all_filters( 'wp_send_json_handler' );
		remove_all_filters( 'wp_doing_ajax' );
		delete_option( 'woocommerce_feature_customer_review_request_enabled' );
		parent::tearDown();
	}

	/**
	 * Build an empty completed order for review submissions.
	 */
	private function make_empty_order(): WC_Order {
		$order = new WC_Order();
		$order->set_customer_id( 1 );
		$order->set_billing_first_name( 'Jane' );
		$order->set_billing_last_name( 'Doe' );
		$order->set_billing_email( 'jane@example.test' );
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		return $order;
	}

	/**
	 * Build a completed order with the given number of products.
	 *
	 * @param int $product_count How many products to attach.
	 * @return array{order:WC_Order, product_ids:int[], item_ids:int[]}
	 */
	private function make_order( int $product_count = 1 ): array {
		$order = $this->make_empty_order();

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
		// Simple products store variation_id `0`. Summary meta is omitted entirely (empty string would be misleading).
		$this->assertSame( '0', get_comment_meta( $row['comment_id'], ItemEligibility::VARIATION_META_KEY, true ) );
		$this->assertSame( '', get_comment_meta( $row['comment_id'], ItemEligibility::VARIATION_SUMMARY_META_KEY, true ) );
	}

	/**
	 * @testdox Two variations of one parent product each produce their own review with variation meta.
	 */
	public function test_writes_per_variation_meta_for_variable_product_rows(): void {
		$variable      = WC_Helper_Product::create_variation_product();
		$variation_ids = $variable->get_children();
		$variation_a   = wc_get_product( $variation_ids[0] );
		$variation_b   = wc_get_product( $variation_ids[1] );

		$order = $this->make_empty_order();
		$order->set_billing_email( 'shopper@example.test' );
		$order->add_product( $variation_a, 1 );
		$order->add_product( $variation_b, 1 );
		$order->save();

		$items  = array_values( $order->get_items() );
		$item_a = $items[0];
		$item_b = $items[1];

		// The page template posts each row's product_id as the *variation* id
		// (it reads `$product->get_id()` on the WC_Product_Variation), so the
		// test mirrors that to exercise the same path SubmissionHandler runs
		// in production.
		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $variation_a->get_id(),
					'order_item_id' => $item_a->get_id(),
					'rating'        => 5,
					'text'          => 'Loved variation A.',
				),
				array(
					'product_id'    => $variation_b->get_id(),
					'order_item_id' => $item_b->get_id(),
					'rating'        => 3,
					'text'          => 'Variation B was just OK.',
				),
			),
		);

		$response = $this->dispatch();
		$this->assertTrue( $response['success'] );
		$results = $response['data']['results'];
		$this->assertCount( 2, $results );

		$rows         = array_values( $results );
		$comment_a_id = (int) $rows[0]['comment_id'];
		$comment_b_id = (int) $rows[1]['comment_id'];
		$this->assertNotSame( $comment_a_id, $comment_b_id, 'Each variation row should produce its own comment.' );

		// Result rows canonicalise product_id to the parent and surface the variation_id separately.
		$this->assertSame( (int) $variable->get_id(), (int) $rows[0]['product_id'] );
		$this->assertSame( (int) $variable->get_id(), (int) $rows[1]['product_id'] );
		$this->assertSame( (int) $variation_a->get_id(), (int) $rows[0]['variation_id'] );
		$this->assertSame( (int) $variation_b->get_id(), (int) $rows[1]['variation_id'] );

		$this->assertSame( (string) $variation_a->get_id(), get_comment_meta( $comment_a_id, ItemEligibility::VARIATION_META_KEY, true ) );
		$this->assertSame( (string) $variation_b->get_id(), get_comment_meta( $comment_b_id, ItemEligibility::VARIATION_META_KEY, true ) );

		// Exact snapshot text — derived the same way the production helper builds it.
		$expected_a = ItemEligibility::format_variation_summary( $item_a );
		$expected_b = ItemEligibility::format_variation_summary( $item_b );
		$this->assertNotSame( '', $expected_a, 'Test setup precondition: variation A produces a non-empty summary.' );
		$this->assertNotSame( '', $expected_b, 'Test setup precondition: variation B produces a non-empty summary.' );
		$this->assertSame(
			$expected_a,
			get_comment_meta( $comment_a_id, ItemEligibility::VARIATION_SUMMARY_META_KEY, true )
		);
		$this->assertSame(
			$expected_b,
			get_comment_meta( $comment_b_id, ItemEligibility::VARIATION_SUMMARY_META_KEY, true )
		);
		$this->assertNotSame( $expected_a, $expected_b, 'Each variation should snapshot its own attribute summary.' );

		// Every row reviewed → order completion meta stamped.
		$fresh = wc_get_order( $order->get_id() );
		$this->assertNotEmpty(
			$fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ),
			'When every reviewable slot has a current-order review, the order should be marked complete.'
		);
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
	 * @testdox A review with more links than comment_max_links is held for moderation, like an ordinary comment.
	 */
	public function test_link_count_over_limit_is_held(): void {
		update_option( 'comment_max_links', 2 );

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
					'rating'        => 5,
					'text'          => 'Great deal, see <a href="https://spam.test/a">here</a> and <a href="https://spam.test/b">here</a>.',
				),
			),
		);

		$response = $this->dispatch();
		$row      = reset( $response['data']['results'] );
		$this->assertSame( 'pending_moderation', $row['status'] );

		$comment = get_comment( $row['comment_id'] );
		$this->assertSame( '0', $comment->comment_approved );
	}

	/**
	 * @testdox A review matching a moderation keyword is held for moderation, like an ordinary comment.
	 */
	public function test_moderation_keyword_match_is_held(): void {
		update_option( 'moderation_keys', "casino\nviagra" );

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
					'rating'        => 5,
					'text'          => 'Won big at the casino thanks to this product!',
				),
			),
		);

		$response = $this->dispatch();
		$row      = reset( $response['data']['results'] );
		$this->assertSame( 'pending_moderation', $row['status'] );

		$comment = get_comment( $row['comment_id'] );
		$this->assertSame( '0', $comment->comment_approved );
	}

	/**
	 * @testdox A second submission after a moderator's spam/trash verdict cannot auto-approve.
	 */
	public function test_resubmit_after_spam_verdict_cannot_autoapprove(): void {
		$built      = $this->make_order( 1 );
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		$_POST      = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 5,
					'text'          => 'Original review text.',
				),
			),
		);
		$first      = $this->dispatch();
		$comment_id = (int) reset( $first['data']['results'] )['comment_id'];

		// A moderator marks the review as spam.
		wp_spam_comment( $comment_id );
		$this->assertSame( 'spam', wp_get_comment_status( $comment_id ) );

		// The customer resubmits the same row.
		$_POST  = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 5,
					'text'          => 'Trying again with clean text.',
				),
			),
		);
		$second = $this->dispatch();
		$row    = reset( $second['data']['results'] );

		$this->assertSame( 'error', $row['status'] );
		$this->assertSame( 'spam', wp_get_comment_status( $comment_id ), 'Moderator decision must stick; a second submission must not auto-approve.' );
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
	 * @testdox Completion stamping ignores reviews tagged to a different order, even on the same parent product.
	 *
	 * Per-order scope guards against an older review from a previous order
	 * inflating the current order's review count. Two variation rows on the
	 * current order require two current-order reviews; an older review of
	 * the same parent must not count toward that quota.
	 */
	public function test_does_not_mark_complete_when_prior_order_review_exists_for_same_parent(): void {
		$variable      = WC_Helper_Product::create_variation_product();
		$variation_ids = $variable->get_children();
		$variation_a   = wc_get_product( $variation_ids[0] );
		$variation_b   = wc_get_product( $variation_ids[1] );
		$other_order   = $this->make_empty_order();

		// Pre-existing approved review from an older order, same parent, variation B.
		$prior_comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $variable->get_id(),
				'comment_author'       => 'Jane',
				'comment_author_email' => 'jane@example.test',
				'comment_content'      => 'Reviewed previously.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		add_comment_meta( $prior_comment_id, 'rating', 4, true );
		add_comment_meta( $prior_comment_id, ItemEligibility::ORDER_META_KEY, (int) $other_order->get_id(), true );
		add_comment_meta( $prior_comment_id, ItemEligibility::VARIATION_META_KEY, (int) $variation_b->get_id(), true );

		$order = $this->make_empty_order();
		$order->add_product( $variation_a, 1 );
		$order->add_product( $variation_b, 1 );
		$order->save();

		$items  = array_values( $order->get_items() );
		$item_a = $items[0];

		// Customer reviews variation A on the CURRENT order. Variation B stays unreviewed.
		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $variable->get_id(),
					'order_item_id' => $item_a->get_id(),
					'rating'        => 5,
				),
			),
		);

		$this->dispatch();

		$fresh = wc_get_order( $order->get_id() );
		$this->assertEmpty(
			$fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ),
			'Older-order reviews on the same parent product must not inflate the current order\'s per-slot count; variation B is still unreviewed.'
		);
	}

	/**
	 * @testdox Duplicate comments for the same variation row do not satisfy a sibling row's quota.
	 *
	 * Guards the per-slot completion count against a double-submit (concurrent
	 * AJAX or client retry) writing two comments for the same variation: the
	 * sibling variation row should still be considered unreviewed.
	 */
	public function test_duplicate_comments_for_same_slot_do_not_complete_siblings(): void {
		$variable      = WC_Helper_Product::create_variation_product();
		$variation_ids = $variable->get_children();
		$variation_a   = wc_get_product( $variation_ids[0] );
		$variation_b   = wc_get_product( $variation_ids[1] );

		$order = $this->make_empty_order();
		$order->add_product( $variation_a, 1 );
		$order->add_product( $variation_b, 1 );
		$order->save();

		$order_id = (int) $order->get_id();

		// Simulate a double-submit by writing two approved comments tagged with variation A.
		foreach ( array( 'First click.', 'Retry click.' ) as $body ) {
			$cid = (int) wp_insert_comment(
				array(
					'comment_post_ID'      => $variable->get_id(),
					'comment_author'       => 'Jane',
					'comment_author_email' => 'jane@example.test',
					'comment_content'      => $body,
					'comment_type'         => 'review',
					'comment_approved'     => 1,
				)
			);
			add_comment_meta( $cid, 'rating', 5, true );
			add_comment_meta( $cid, ItemEligibility::ORDER_META_KEY, $order_id, true );
			add_comment_meta( $cid, ItemEligibility::VARIATION_META_KEY, (int) $variation_a->get_id(), true );
		}

		// Trigger completion evaluation via an empty submission.
		$_POST = array(
			'order_id' => $order_id,
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(),
		);

		$this->dispatch();

		$fresh = wc_get_order( $order_id );
		$this->assertEmpty(
			$fresh->get_meta( SubmissionHandler::COMPLETED_META_KEY ),
			'Two comments for variation A must not fill variation B\'s slot.'
		);
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

	/**
	 * @testdox Resubmitting for the same order updates the existing review in place (no duplicate row).
	 */
	public function test_resubmit_for_same_order_updates_existing_review(): void {
		$built      = $this->make_order( 1 );
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		// First submission inserts the comment with the order-id meta.
		$_POST      = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 3,
					'text'          => 'First take.',
				),
			),
		);
		$first      = $this->dispatch();
		$first_row  = reset( $first['data']['results'] );
		$comment_id = (int) $first_row['comment_id'];
		$this->assertGreaterThan( 0, $comment_id );

		// Second submission edits the same row.
		$_POST      = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 5,
					'text'          => 'On reflection — outstanding.',
				),
			),
		);
		$second     = $this->dispatch();
		$second_row = reset( $second['data']['results'] );

		$this->assertSame( 'ok', $second_row['status'] );
		$this->assertSame( $comment_id, (int) $second_row['comment_id'], 'Re-submit must update the existing comment, not create a new one.' );

		$updated = get_comment( $comment_id );
		$this->assertSame( 'On reflection — outstanding.', $updated->comment_content );
		$this->assertSame( '5', get_comment_meta( $comment_id, 'rating', true ) );

		$total = (int) get_comments(
			array(
				'post_id'      => $product_id,
				'author_email' => $order->get_billing_email(),
				'type'         => 'review',
				'count'        => true,
				'status'       => 'all',
			)
		);
		$this->assertSame( 1, $total, 'No duplicate comment may exist after an edit-resubmit.' );
	}

	/**
	 * @testdox A review left for a previous order does not block re-reviewing the same product on a new order.
	 */
	public function test_review_from_previous_order_does_not_block_new_review(): void {
		$built      = $this->make_order( 1 );
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		// Simulate a review from a different order: same email + product, different
		// _review_order_id meta so the scoping doesn't surface it for this order.
		$older_comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => 'Jane Doe',
				'comment_author_email' => $order->get_billing_email(),
				'comment_content'      => 'First time round.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		add_comment_meta( $older_comment_id, ItemEligibility::ORDER_META_KEY, (int) $order->get_id() + 999, true );

		$_POST    = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 5,
					'text'          => 'Second purchase, even better.',
				),
			),
		);
		$response = $this->dispatch();
		$row      = reset( $response['data']['results'] );

		$this->assertSame( 'ok', $row['status'] );
		$this->assertNotSame( $older_comment_id, (int) $row['comment_id'], 'New order must produce a fresh comment, not edit the previous order\'s review.' );

		// Two comments exist now: the legacy one and the new one for this order.
		$total = (int) get_comments(
			array(
				'post_id'      => $product_id,
				'author_email' => $order->get_billing_email(),
				'type'         => 'review',
				'count'        => true,
				'status'       => 'all',
			)
		);
		$this->assertSame( 2, $total );
	}

	/**
	 * @testdox A row whose product has comments closed is rejected with reviews_not_open.
	 */
	public function test_rejects_row_when_reviews_disabled_on_product(): void {
		$built      = $this->make_order( 1 );
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		wp_update_post(
			array(
				'ID'             => $product_id,
				'comment_status' => 'closed',
			)
		);

		$_POST = array(
			'order_id' => $order->get_id(),
			'key'      => $order->get_order_key(),
			'_wcnonce' => wp_create_nonce( SubmissionHandler::ACTION ),
			'reviews'  => array(
				array(
					'product_id'    => $product_id,
					'order_item_id' => $item_id,
					'rating'        => 5,
				),
			),
		);

		$response = $this->dispatch();
		$results  = $response['data']['results'];
		$row      = reset( $results );
		$this->assertSame( 'error', $row['status'] );
		$this->assertSame( 'reviews_not_open', $row['error'] );

		$total = (int) get_comments(
			array(
				'post_id'      => $product_id,
				'author_email' => $order->get_billing_email(),
				'type'         => 'review',
				'count'        => true,
				'status'       => 'all',
			)
		);
		$this->assertSame( 0, $total );
	}

	/**
	 * @testdox A row for a fully-refunded line item is rejected via the eligible-items filter.
	 */
	public function test_rejects_row_for_fully_refunded_item(): void {
		$built      = $this->make_order( 1 );
		$order      = $built['order'];
		$product_id = $built['product_ids'][0];
		$item_id    = $built['item_ids'][0];

		// Stand in for the round-1 default callback that would normally
		// drop fully-refunded items. The handler uses the same filter, so
		// dropping the item here mirrors what the WC default does.
		add_filter(
			'woocommerce_review_order_eligible_items',
			static function ( $items, $order_arg ) use ( $item_id ) {
				unset( $order_arg );
				$filtered = array();
				foreach ( $items as $key => $item ) {
					if ( $item->get_id() !== $item_id ) {
						$filtered[ $key ] = $item;
					}
				}
				return $filtered;
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
					'rating'        => 5,
				),
			),
		);

		$response = $this->dispatch();
		$results  = $response['data']['results'];
		$row      = reset( $results );
		$this->assertSame( 'error', $row['status'] );
		$this->assertSame( 'invalid_row', $row['error'] );

		remove_all_filters( 'woocommerce_review_order_eligible_items' );

		$total = (int) get_comments(
			array(
				'post_id'      => $product_id,
				'author_email' => $order->get_billing_email(),
				'type'         => 'review',
				'count'        => true,
				'status'       => 'all',
			)
		);
		$this->assertSame( 0, $total );
	}
}
