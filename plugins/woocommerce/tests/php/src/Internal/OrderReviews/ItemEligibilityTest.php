<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Helper_Product;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Tests for ItemEligibility.
 *
 * @covers \Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility
 */
class ItemEligibilityTest extends WC_Unit_Test_Case {

	/**
	 * Feature flag gates the OrderReviews stack.
	 */
	public function setUp(): void {
		parent::setUp();
		update_option( 'woocommerce_feature_customer_review_request_enabled', 'yes' );
	}

	/**
	 * Reset between tests.
	 */
	public function tearDown(): void {
		ItemEligibility::reset_cache();
		delete_option( 'woocommerce_feature_customer_review_request_enabled' );
		parent::tearDown();
	}

	/**
	 * Build a 1-product completed order.
	 *
	 * @param string $email Billing email to set on the order.
	 * @return array Map with `order`, `item`, and `product_id`.
	 */
	private function make_order( string $email = 'jane@example.test' ): array {
		$order = OrderHelper::create_order();
		foreach ( $order->get_items() as $line ) {
			$order->remove_item( $line->get_id() );
		}
		$order->set_billing_email( $email );
		$order->set_status( OrderStatus::COMPLETED );

		$product = WC_Helper_Product::create_simple_product();
		$order->add_product( $product, 1 );
		$order->save();

		$items = $order->get_items();
		$item  = reset( $items );

		return array(
			'order'      => $order,
			'item'       => $item,
			'product_id' => $product->get_id(),
		);
	}

	/**
	 * Insert a customer review for a product, tagged with the source order
	 * and (optionally) variation id.
	 *
	 * @param int      $product_id   Product post id.
	 * @param string   $email        Author email.
	 * @param string   $body         Comment body.
	 * @param int      $rating       Rating value 1-5.
	 * @param int|null $order_id     Source order id stamped as `_review_order_id` commentmeta. Pass null to skip.
	 * @param int      $variation_id Variation id stamped as `_review_variation_id` commentmeta. 0 for simple products.
	 * @param int      $approved     1 for approved, 0 for pending moderation.
	 * @return int Inserted comment id.
	 */
	private function insert_review( int $product_id, string $email, string $body, int $rating, ?int $order_id = null, int $variation_id = 0, int $approved = 1 ): int {
		$comment_id = (int) wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => 'Reviewer',
				'comment_author_email' => $email,
				'comment_content'      => $body,
				'comment_type'         => 'review',
				'comment_approved'     => $approved,
			)
		);
		add_comment_meta( $comment_id, 'rating', $rating, true );
		if ( null !== $order_id ) {
			add_comment_meta( $comment_id, ItemEligibility::ORDER_META_KEY, $order_id, true );
		}
		add_comment_meta( $comment_id, ItemEligibility::VARIATION_META_KEY, $variation_id, true );
		return $comment_id;
	}

	/**
	 * @testdox decide() returns `form` and no comment when no review exists for this order.
	 */
	public function test_decide_default_returns_form(): void {
		$built = $this->make_order();

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_FORM, $decision['status'] );
		$this->assertNull( $decision['comment'] );
	}

	/**
	 * @testdox decide() returns `skip` when comments are closed on the product.
	 */
	public function test_decide_skip_when_comments_closed(): void {
		$built = $this->make_order();
		wp_update_post(
			array(
				'ID'             => $built['product_id'],
				'comment_status' => 'closed',
			)
		);

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_SKIP, $decision['status'] );
	}

	/**
	 * @testdox decide() returns the matching review when one exists for *this* order.
	 */
	public function test_decide_surfaces_review_from_same_order(): void {
		$built      = $this->make_order( 'match@example.test' );
		$comment_id = $this->insert_review( $built['product_id'], 'match@example.test', 'Worked great.', 5, (int) $built['order']->get_id() );

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_FORM, $decision['status'] );
		$this->assertNotNull( $decision['comment'] );
		$this->assertSame( $comment_id, (int) $decision['comment']->comment_ID );
	}

	/**
	 * @testdox decide() ignores reviews tagged to a different order (re-reviewing is allowed).
	 */
	public function test_decide_ignores_review_from_different_order(): void {
		$built = $this->make_order( 'repeat@example.test' );
		// Same customer + product, but review came from a different (older) order.
		$this->insert_review( $built['product_id'], 'repeat@example.test', 'First time.', 4, (int) $built['order']->get_id() + 999 );

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_FORM, $decision['status'] );
		$this->assertNull( $decision['comment'], 'Reviews from a different order must not pre-fill the current row.' );
	}

	/**
	 * @testdox exclude_fully_refunded_items drops items whose full quantity has been refunded.
	 */
	public function test_exclude_fully_refunded_items_drops_full_refunds(): void {
		$built = $this->make_order();
		$order = $built['order'];
		$item  = $built['item'];

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => $item->get_total(),
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => $item->get_quantity(),
						'refund_total' => $item->get_total(),
					),
				),
			)
		);

		$fresh    = wc_get_order( $order->get_id() );
		$filtered = ItemEligibility::exclude_fully_refunded_items( $fresh->get_items(), $fresh );

		$this->assertCount( 0, $filtered, 'Fully refunded line item should be excluded.' );
	}

	/**
	 * @testdox exclude_fully_refunded_items keeps partially-refunded items.
	 */
	public function test_exclude_fully_refunded_items_keeps_partial_refunds(): void {
		$order = OrderHelper::create_order();
		foreach ( $order->get_items() as $line ) {
			$order->remove_item( $line->get_id() );
		}
		$order->set_billing_email( 'jane@example.test' );
		$order->set_status( OrderStatus::COMPLETED );

		$product = WC_Helper_Product::create_simple_product();
		$order->add_product( $product, 3 );
		$order->save();

		$items = $order->get_items();
		/** @var WC_Order_Item_Product $item */
		$item = reset( $items );

		wc_create_refund(
			array(
				'order_id'   => $order->get_id(),
				'amount'     => (float) $item->get_total() / 3,
				'line_items' => array(
					$item->get_id() => array(
						'qty'          => 1,
						'refund_total' => (float) $item->get_total() / 3,
					),
				),
			)
		);

		$fresh    = wc_get_order( $order->get_id() );
		$filtered = ItemEligibility::exclude_fully_refunded_items( $fresh->get_items(), $fresh );

		$this->assertCount( 1, $filtered, 'Partially refunded line item should still be eligible.' );
	}

	/**
	 * @testdox decide() ignores reviews without the order meta (default for legacy reviews).
	 */
	public function test_decide_ignores_review_without_order_meta(): void {
		$built = $this->make_order( 'legacy@example.test' );
		$this->insert_review( $built['product_id'], 'legacy@example.test', 'Pre-feature review.', 3, null );

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_FORM, $decision['status'] );
		$this->assertNull( $decision['comment'] );
	}

	/**
	 * @testdox prefill_for_item() returns rating + text + comment id when this order has a review.
	 */
	public function test_prefill_returns_existing_review_data(): void {
		$built      = $this->make_order( 'prefill@example.test' );
		$comment_id = $this->insert_review( $built['product_id'], 'prefill@example.test', 'Solid 4 stars.', 4, (int) $built['order']->get_id() );

		$prefill = ItemEligibility::prefill_for_item( $built['item'], $built['order'] );

		$this->assertSame( 4, $prefill['rating'] );
		$this->assertSame( 'Solid 4 stars.', $prefill['text'] );
		$this->assertSame( $comment_id, $prefill['comment_id'] );
	}

	/**
	 * @testdox prefill_for_item() returns zeros / empty when no review for this order.
	 */
	public function test_prefill_returns_empty_when_no_review(): void {
		$built = $this->make_order();

		$prefill = ItemEligibility::prefill_for_item( $built['item'], $built['order'] );

		$this->assertSame( 0, $prefill['rating'] );
		$this->assertSame( '', $prefill['text'] );
		$this->assertSame( 0, $prefill['comment_id'] );
	}

	/**
	 * @testdox preload_for_items() caches per-order so decide() does not requery.
	 */
	public function test_preload_caches_results(): void {
		$built = $this->make_order( 'cache@example.test' );
		$this->insert_review( $built['product_id'], 'cache@example.test', 'Cached.', 5, (int) $built['order']->get_id() );

		ItemEligibility::preload_for_items( $built['order']->get_items(), $built['order'] );

		$call_count = 0;
		$counter    = static function ( $value ) use ( &$call_count ) {
			++$call_count;
			return $value;
		};
		add_filter( 'comments_pre_query', $counter );

		try {
			$decision = ItemEligibility::decide( $built['item'], $built['order'] );
		} finally {
			remove_filter( 'comments_pre_query', $counter );
		}

		$this->assertNotNull( $decision['comment'] );
		$this->assertSame( 0, $call_count, 'decide() should not query when preload_for_items() has cached the result.' );
	}

	/**
	 * @testdox has_actionable_items() returns true when at least one item is reviewable.
	 */
	public function test_has_actionable_items_true_for_default_order(): void {
		$built = $this->make_order();

		$this->assertTrue( ItemEligibility::has_actionable_items( $built['order'] ) );
	}

	/**
	 * @testdox has_actionable_items() returns false when every product has reviews disabled.
	 */
	public function test_has_actionable_items_false_when_all_items_disabled(): void {
		$built   = $this->make_order();
		$product = wc_get_product( $built['product_id'] );
		$product->set_reviews_allowed( false );
		$product->save();

		$this->assertFalse( ItemEligibility::has_actionable_items( $built['order'] ) );
	}

	/**
	 * @testdox has_actionable_items() returns false when reviews are disabled site-wide.
	 */
	public function test_has_actionable_items_false_when_site_wide_reviews_disabled(): void {
		$built    = $this->make_order();
		$previous = get_option( 'woocommerce_enable_reviews', 'yes' );
		update_option( 'woocommerce_enable_reviews', 'no' );
		remove_post_type_support( 'product', 'comments' );

		try {
			$this->assertFalse( ItemEligibility::has_actionable_items( $built['order'] ) );
		} finally {
			update_option( 'woocommerce_enable_reviews', $previous );
			if ( 'yes' === $previous ) {
				add_post_type_support( 'product', 'comments' );
			}
		}
	}

	/**
	 * @testdox has_actionable_items() returns false once every reviewable item is reviewed.
	 */
	public function test_has_actionable_items_false_when_all_items_reviewed(): void {
		$built = $this->make_order( 'all-done@example.test' );
		$this->insert_review( $built['product_id'], 'all-done@example.test', 'Done.', 5, (int) $built['order']->get_id() );

		$this->assertFalse( ItemEligibility::has_actionable_items( $built['order'] ) );
	}

	/**
	 * @testdox decide() scopes the existing-review lookup by variation id.
	 *
	 * Two variation rows of the same parent product on the same order: a
	 * review tagged with variation A's id must prefill only the row whose
	 * line item is variation A. The variation B row stays unreviewed.
	 */
	public function test_decide_scopes_by_variation_id(): void {
		$built = $this->make_variation_order( 'shopper@example.test' );

		$this->insert_review(
			$built['product_id'],
			'shopper@example.test',
			'Loved the Small.',
			5,
			(int) $built['order']->get_id(),
			$built['variation_a_id']
		);

		// Mirror the page-load path: bulk preload, then per-item decide(). This
		// exercises the preload bucketing logic as well, not just the
		// fallback single-item query inside `find_existing_review()`.
		$items = $built['order']->get_items();
		ItemEligibility::preload_for_items( $items, $built['order'] );

		$decision_a = ItemEligibility::decide( $built['item_a'], $built['order'] );
		$decision_b = ItemEligibility::decide( $built['item_b'], $built['order'] );

		$this->assertNotNull( $decision_a['comment'], 'Variation A row should prefill from its own review.' );
		$this->assertNull( $decision_b['comment'], 'Variation B row should stay unreviewed.' );
		$this->assertSame( $built['variation_a_id'], $decision_a['variation_id'] );
		$this->assertSame( $built['variation_b_id'], $decision_b['variation_id'] );
	}

	/**
	 * Build a completed order with two variations of one parent variable product.
	 *
	 * @param string $email Billing email to set on the order.
	 * @return array{order:\WC_Order, item_a:\WC_Order_Item_Product, item_b:\WC_Order_Item_Product, product_id:int, variation_a_id:int, variation_b_id:int}
	 */
	private function make_variation_order( string $email ): array {
		$variable      = WC_Helper_Product::create_variation_product();
		$variation_ids = $variable->get_children();
		$variation_a   = wc_get_product( $variation_ids[0] );
		$variation_b   = wc_get_product( $variation_ids[1] );

		$order = OrderHelper::create_order();
		foreach ( $order->get_items() as $line ) {
			$order->remove_item( $line->get_id() );
		}
		$order->set_billing_email( $email );
		$order->set_status( OrderStatus::COMPLETED );

		$order->add_product( $variation_a, 1 );
		$order->add_product( $variation_b, 1 );
		$order->save();

		$items = array_values( $order->get_items() );

		return array(
			'order'          => $order,
			'item_a'         => $items[0],
			'item_b'         => $items[1],
			'product_id'     => $variable->get_id(),
			'variation_a_id' => (int) $variation_a->get_id(),
			'variation_b_id' => (int) $variation_b->get_id(),
		);
	}
}
