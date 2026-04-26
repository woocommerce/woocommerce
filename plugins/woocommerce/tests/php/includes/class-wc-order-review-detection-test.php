<?php
/**
 * Tests for the order review detection functions.
 *
 * @package WooCommerce\Tests
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Enums\OrderStatus;

/**
 * Tests for wc_get_order_product_existing_review(),
 * wc_order_item_is_already_reviewed(), and wc_get_order_review_items_data().
 */
class WC_Order_Review_Detection_Test extends WC_Unit_Test_Case {

	/**
	 * Helper: create a simple product.
	 *
	 * @return WC_Product
	 */
	private function create_product(): WC_Product {
		$product = WC_Helper_Product::create_simple_product();
		// Ensure comments/reviews are open.
		wp_update_post(
			array(
				'ID'             => $product->get_id(),
				'comment_status' => 'open',
			)
		);
		return $product;
	}

	/**
	 * Helper: create a product with reviews closed.
	 *
	 * @return WC_Product
	 */
	private function create_product_reviews_closed(): WC_Product {
		$product = WC_Helper_Product::create_simple_product();
		wp_update_post(
			array(
				'ID'             => $product->get_id(),
				'comment_status' => 'closed',
			)
		);
		return $product;
	}

	/**
	 * Helper: create an order with given products.
	 *
	 * @param WC_Product[] $products Products to add to the order.
	 * @return WC_Order
	 */
	private function create_order( array $products ): WC_Order {
		$order = wc_create_order(
			array(
				'status' => OrderStatus::COMPLETED,
			)
		);

		$order->set_billing_first_name( 'Jane' );
		$order->set_billing_last_name( 'Smith' );
		$order->set_billing_email( 'jane@example.com' );

		foreach ( $products as $product ) {
			$order->add_product( $product, 1 );
		}

		$order->save();

		return $order;
	}

	/**
	 * Helper: insert a product review comment.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $email      Author email.
	 * @param int    $rating     Star rating.
	 * @param string $content    Review text.
	 * @param string $approved   Comment approved status.
	 * @return int Comment ID.
	 */
	private function insert_review( int $product_id, string $email, int $rating = 5, string $content = 'Great product!', string $approved = '1' ): int {
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'      => $product_id,
				'comment_author'       => 'Jane Smith',
				'comment_author_email' => $email,
				'comment_content'      => $content,
				'comment_type'         => 'review',
				'comment_approved'     => $approved,
			)
		);

		update_comment_meta( $comment_id, 'rating', $rating );

		return $comment_id;
	}

	// ---------------------------------------------------------------
	// wc_get_order_product_existing_review()
	// ---------------------------------------------------------------

	/**
	 * @testdox Returns null when no review exists for the product/email pair.
	 */
	public function test_get_existing_review_returns_null_when_none_exists(): void {
		$product = $this->create_product();

		$result = wc_get_order_product_existing_review( $product->get_id(), 'nobody@example.com' );

		$this->assertNull( $result );
	}

	/**
	 * @testdox Returns the review comment when one exists.
	 */
	public function test_get_existing_review_returns_comment_when_exists(): void {
		$product    = $this->create_product();
		$comment_id = $this->insert_review( $product->get_id(), 'jane@example.com' );

		$result = wc_get_order_product_existing_review( $product->get_id(), 'jane@example.com' );

		$this->assertInstanceOf( WP_Comment::class, $result );
		$this->assertEquals( $comment_id, $result->comment_ID );
	}

	/**
	 * @testdox Returns the most recent review when multiple exist.
	 */
	public function test_get_existing_review_returns_most_recent(): void {
		$product = $this->create_product();

		$this->insert_review( $product->get_id(), 'jane@example.com', 3, 'Old review' );
		// Advance time to ensure ordering.
		sleep( 1 );
		$newer_id = $this->insert_review( $product->get_id(), 'jane@example.com', 5, 'New review' );

		$result = wc_get_order_product_existing_review( $product->get_id(), 'jane@example.com' );

		$this->assertNotNull( $result );
		$this->assertEquals( $newer_id, $result->comment_ID );
		$this->assertEquals( 'New review', $result->comment_content );
	}

	/**
	 * @testdox Returns null for invalid product ID.
	 */
	public function test_get_existing_review_returns_null_for_invalid_product(): void {
		$result = wc_get_order_product_existing_review( 0, 'jane@example.com' );

		$this->assertNull( $result );
	}

	/**
	 * @testdox Returns null for empty email.
	 */
	public function test_get_existing_review_returns_null_for_empty_email(): void {
		$product = $this->create_product();

		$result = wc_get_order_product_existing_review( $product->get_id(), '' );

		$this->assertNull( $result );
	}

	/**
	 * @testdox Includes pending/unapproved reviews.
	 */
	public function test_get_existing_review_includes_unapproved(): void {
		$product    = $this->create_product();
		$comment_id = $this->insert_review( $product->get_id(), 'jane@example.com', 4, 'Pending review', '0' );

		$result = wc_get_order_product_existing_review( $product->get_id(), 'jane@example.com' );

		$this->assertNotNull( $result );
		$this->assertEquals( $comment_id, $result->comment_ID );
	}

	/**
	 * @testdox Does not match reviews from a different email.
	 */
	public function test_get_existing_review_different_email_returns_null(): void {
		$product = $this->create_product();
		$this->insert_review( $product->get_id(), 'other@example.com' );

		$result = wc_get_order_product_existing_review( $product->get_id(), 'jane@example.com' );

		$this->assertNull( $result );
	}

	// ---------------------------------------------------------------
	// wc_order_item_is_already_reviewed()
	// ---------------------------------------------------------------

	/**
	 * @testdox Returns true when a review exists.
	 */
	public function test_is_already_reviewed_returns_true(): void {
		$product = $this->create_product();
		$order   = $this->create_order( array( $product ) );
		$this->insert_review( $product->get_id(), 'jane@example.com' );

		$this->assertTrue(
			wc_order_item_is_already_reviewed( $product->get_id(), $order, 'jane@example.com' )
		);
	}

	/**
	 * @testdox Returns false when no review exists.
	 */
	public function test_is_already_reviewed_returns_false(): void {
		$product = $this->create_product();
		$order   = $this->create_order( array( $product ) );

		$this->assertFalse(
			wc_order_item_is_already_reviewed( $product->get_id(), $order, 'jane@example.com' )
		);
	}

	/**
	 * @testdox Filter can override the detection result to false.
	 */
	public function test_filter_can_override_to_false(): void {
		$product = $this->create_product();
		$order   = $this->create_order( array( $product ) );
		$this->insert_review( $product->get_id(), 'jane@example.com' );

		$callback = function () {
			return false;
		};
		add_filter( 'woocommerce_review_order_item_already_reviewed', $callback );

		$this->assertFalse(
			wc_order_item_is_already_reviewed( $product->get_id(), $order, 'jane@example.com' )
		);

		remove_filter( 'woocommerce_review_order_item_already_reviewed', $callback );
	}

	/**
	 * @testdox Filter can override the detection result to true.
	 */
	public function test_filter_can_override_to_true(): void {
		$product = $this->create_product();
		$order   = $this->create_order( array( $product ) );

		$callback = function () {
			return true;
		};
		add_filter( 'woocommerce_review_order_item_already_reviewed', $callback );

		$this->assertTrue(
			wc_order_item_is_already_reviewed( $product->get_id(), $order, 'jane@example.com' )
		);

		remove_filter( 'woocommerce_review_order_item_already_reviewed', $callback );
	}

	/**
	 * @testdox Filter receives the correct arguments.
	 */
	public function test_filter_receives_correct_args(): void {
		$product = $this->create_product();
		$order   = $this->create_order( array( $product ) );

		$captured = array();
		$callback = function ( $is_reviewed, $pid, $ord, $email ) use ( &$captured ) {
			$captured = array(
				'is_reviewed' => $is_reviewed,
				'product_id'  => $pid,
				'order'       => $ord,
				'email'       => $email,
			);
			return $is_reviewed;
		};
		add_filter( 'woocommerce_review_order_item_already_reviewed', $callback, 10, 4 );

		wc_order_item_is_already_reviewed( $product->get_id(), $order, 'jane@example.com' );

		$this->assertFalse( $captured['is_reviewed'] );
		$this->assertEquals( $product->get_id(), $captured['product_id'] );
		$this->assertInstanceOf( WC_Order::class, $captured['order'] );
		$this->assertEquals( 'jane@example.com', $captured['email'] );

		remove_filter( 'woocommerce_review_order_item_already_reviewed', $callback );
	}

	// ---------------------------------------------------------------
	// wc_get_order_review_items_data()
	// ---------------------------------------------------------------

	/**
	 * @testdox Returns enriched data for each order item with reviews open.
	 */
	public function test_get_review_items_data_basic(): void {
		$product = $this->create_product();
		$order   = $this->create_order( array( $product ) );

		$data = wc_get_order_review_items_data( $order, 'jane@example.com' );

		$this->assertCount( 1, $data );
		$this->assertArrayHasKey( $product->get_id(), $data );

		$item = $data[ $product->get_id() ];
		$this->assertEquals( $product->get_id(), $item['product_id'] );
		$this->assertFalse( $item['is_reviewed'] );
		$this->assertNull( $item['review'] );
		$this->assertEquals( 0, $item['rating'] );
		$this->assertEquals( '', $item['review_body'] );
	}

	/**
	 * @testdox Items with reviews closed are excluded.
	 */
	public function test_get_review_items_data_excludes_closed(): void {
		$open   = $this->create_product();
		$closed = $this->create_product_reviews_closed();
		$order  = $this->create_order( array( $open, $closed ) );

		$data = wc_get_order_review_items_data( $order, 'jane@example.com' );

		$this->assertCount( 1, $data );
		$this->assertArrayHasKey( $open->get_id(), $data );
		$this->assertArrayNotHasKey( $closed->get_id(), $data );
	}

	/**
	 * @testdox Already-reviewed items include the review data.
	 */
	public function test_get_review_items_data_includes_review(): void {
		$product = $this->create_product();
		$order   = $this->create_order( array( $product ) );

		$comment_id = $this->insert_review( $product->get_id(), 'jane@example.com', 4, 'Nice!' );

		$data = wc_get_order_review_items_data( $order, 'jane@example.com' );

		$item = $data[ $product->get_id() ];
		$this->assertTrue( $item['is_reviewed'] );
		$this->assertInstanceOf( WP_Comment::class, $item['review'] );
		$this->assertEquals( $comment_id, $item['review']->comment_ID );
		$this->assertEquals( 4, $item['rating'] );
		$this->assertEquals( 'Nice!', $item['review_body'] );
	}

	/**
	 * @testdox Duplicate product IDs (same product, qty > 1) produce only one entry.
	 */
	public function test_get_review_items_data_deduplicates(): void {
		$product = $this->create_product();
		$order   = wc_create_order(
			array(
				'status' => OrderStatus::COMPLETED,
			)
		);
		$order->set_billing_email( 'jane@example.com' );
		$order->add_product( $product, 3 );
		$order->save();

		$data = wc_get_order_review_items_data( $order, 'jane@example.com' );

		$this->assertCount( 1, $data );
	}

	/**
	 * @testdox Mixed reviewed and unreviewed items return correct flags.
	 */
	public function test_get_review_items_data_mixed(): void {
		$reviewed   = $this->create_product();
		$unreviewed = $this->create_product();
		$order      = $this->create_order( array( $reviewed, $unreviewed ) );

		$this->insert_review( $reviewed->get_id(), 'jane@example.com', 5, 'Awesome!' );

		$data = wc_get_order_review_items_data( $order, 'jane@example.com' );

		$this->assertCount( 2, $data );
		$this->assertTrue( $data[ $reviewed->get_id() ]['is_reviewed'] );
		$this->assertFalse( $data[ $unreviewed->get_id() ]['is_reviewed'] );
	}
}
