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
	 * Reset between tests.
	 */
	public function tearDown(): void {
		ItemEligibility::reset_cache();
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
	 * Insert a customer review for a product, optionally tagged with the source order id.
	 *
	 * @param int      $product_id Product post id.
	 * @param string   $email      Author email.
	 * @param string   $body       Comment body.
	 * @param int      $rating     Rating value 1-5.
	 * @param int|null $order_id   Source order id stamped as `_review_order_id` commentmeta. Pass null to skip.
	 * @param int      $approved   1 for approved, 0 for pending moderation.
	 * @return int Inserted comment id.
	 */
	private function insert_review( int $product_id, string $email, string $body, int $rating, ?int $order_id = null, int $approved = 1 ): int {
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
}
