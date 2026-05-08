<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\OrderReviews;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\OrderReviews\ItemEligibility;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use WC_Helper_Product;
use WC_Order;
use WC_Order_Item_Product;
use WC_Unit_Test_Case;

/**
 * Tests for ItemEligibility.
 */
class ItemEligibilityTest extends WC_Unit_Test_Case {

	/**
	 * Reset between tests.
	 */
	public function tearDown(): void {
		remove_all_filters( 'woocommerce_review_order_item_already_reviewed' );
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
	 * @testdox Returns `form` when no existing review and comments are open.
	 */
	public function test_default_returns_form(): void {
		$built = $this->make_order();

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_FORM, $decision['status'] );
		$this->assertNull( $decision['comment'] );
	}

	/**
	 * @testdox Returns `skip` when comments are closed on the product.
	 */
	public function test_skip_when_comments_closed(): void {
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
	 * @testdox Returns `reviewed` when the customer's email already has a review on the product.
	 */
	public function test_reviewed_when_existing_match(): void {
		$built      = $this->make_order( 'match@example.test' );
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $built['product_id'],
				'comment_author'       => 'Match',
				'comment_author_email' => 'match@example.test',
				'comment_content'      => 'Worked great.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		$this->assertNotFalse( $comment_id );

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_REVIEWED, $decision['status'] );
		$this->assertNotNull( $decision['comment'] );
		$this->assertSame( (int) $comment_id, (int) $decision['comment']->comment_ID );
	}

	/**
	 * @testdox Reviews on the product by a different email do not lock the row.
	 */
	public function test_returns_form_when_review_is_by_another_author(): void {
		$built = $this->make_order( 'me@example.test' );
		wp_insert_comment(
			array(
				'comment_post_ID'      => $built['product_id'],
				'comment_author'       => 'Stranger',
				'comment_author_email' => 'stranger@example.test',
				'comment_content'      => 'Their review.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_FORM, $decision['status'] );
	}

	/**
	 * @testdox The filter can override the default decision in either direction.
	 */
	public function test_filter_overrides_decision(): void {
		$built = $this->make_order();

		// No matching review exists, but filter forces reviewed=true.
		add_filter(
			'woocommerce_review_order_item_already_reviewed',
			static function () {
				return true;
			}
		);

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );
		$this->assertSame( ItemEligibility::STATUS_REVIEWED, $decision['status'] );
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
	 * @testdox Filter receives product_id, order, and customer_email.
	 */
	public function test_filter_receives_context(): void {
		$built    = $this->make_order( 'context@example.test' );
		$received = array();

		add_filter(
			'woocommerce_review_order_item_already_reviewed',
			static function ( $current, $product_id, $order, $customer_email ) use ( &$received ) {
				$received = array(
					'product_id'     => (int) $product_id,
					'order_id'       => $order instanceof WC_Order ? $order->get_id() : 0,
					'customer_email' => (string) $customer_email,
				);
				return $current;
			},
			10,
			4
		);

		ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( (int) $built['product_id'], $received['product_id'] );
		$this->assertSame( (int) $built['order']->get_id(), $received['order_id'] );
		$this->assertSame( 'context@example.test', $received['customer_email'] );
	}

	/**
	 * @testdox prime() pre-fills the cache so subsequent describe() calls do not requery.
	 */
	public function test_prime_caches_results(): void {
		$built      = $this->make_order( 'cache@example.test' );
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $built['product_id'],
				'comment_author'       => 'Cache',
				'comment_author_email' => 'cache@example.test',
				'comment_content'      => 'Worked.',
				'comment_type'         => 'review',
				'comment_approved'     => 1,
			)
		);
		$this->assertNotFalse( $comment_id );

		ItemEligibility::preload_for_items( $built['order']->get_items(), $built['order'] );

		// Count get_comments calls during describe(); cache should serve the answer.
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

		$this->assertSame( ItemEligibility::STATUS_REVIEWED, $decision['status'] );
		$this->assertSame( 0, $call_count, 'describe() should not query when prime() has cached the result.' );
	}

	/**
	 * @testdox Filter forcing reviewed=true with no comment yields STATUS_REVIEWED with null comment.
	 */
	public function test_filter_forced_reviewed_with_no_comment(): void {
		$built = $this->make_order();

		add_filter(
			'woocommerce_review_order_item_already_reviewed',
			'__return_true'
		);

		$decision = ItemEligibility::decide( $built['item'], $built['order'] );

		$this->assertSame( ItemEligibility::STATUS_REVIEWED, $decision['status'] );
		$this->assertNull( $decision['comment'], 'Filter-only reviewed state should leave comment as null.' );
	}
}
