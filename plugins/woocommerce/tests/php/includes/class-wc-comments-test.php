<?php
declare( strict_types=1 );

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests for WC_Comments class.
 */
class WC_Comments_Tests extends \WC_Unit_Test_Case {
	/**
	 * Test get_review_counts_for_product_ids().
	 */
	public function test_get_review_counts_for_product_ids() {
		$product1 = WC_Helper_Product::create_simple_product();
		$product2 = WC_Helper_Product::create_simple_product();
		$product3 = WC_Helper_Product::create_simple_product();

		$expected_review_count = array(
			$product1->get_id() => 0,
			$product2->get_id() => 0,
			$product3->get_id() => 0,
		);
		$product_id_array      = array_keys( $expected_review_count );

		$this->assertEquals( $expected_review_count, WC_Comments::get_review_counts_for_product_ids( $product_id_array ) );

		ProductHelper::create_product_review( $product2->get_id() );
		ProductHelper::create_product_review( $product3->get_id() );
		ProductHelper::create_product_review( $product3->get_id() );

		$expected_review_count = array(
			$product1->get_id() => 0,
			$product2->get_id() => 1,
			$product3->get_id() => 2,
		);
		$this->assertEquals( $expected_review_count, WC_Comments::get_review_counts_for_product_ids( $product_id_array ) );
	}

	/**
	 * Test get_review_count_for_product.
	 */
	public function test_get_review_count_for_product() {
		$product = WC_Helper_Product::create_simple_product();
		$this->assertEquals( 0, WC_Comments::get_review_count_for_product( $product ) );

		ProductHelper::create_product_review( $product->get_id() );
		$this->assertEquals( 1, WC_Comments::get_review_count_for_product( $product ) );

		ProductHelper::create_product_review( $product->get_id() );
		$this->assertEquals( 2, WC_Comments::get_review_count_for_product( $product ) );
	}

	/**
	 * Test get_products_reviews_pending_moderation_counter.
	 */
	public function test_get_pending_review_count_is_getting_updated() {
		$this->assertSame( 0, WC_Comments::get_products_reviews_pending_moderation_counter() );

		// Ensure the newly posted reviews processing is handled as intended.
		$product            = WC_Helper_Product::create_simple_product();
		$approved_reviews   = array(
			ProductHelper::create_product_review( $product->get_id(), '...' ),
		);
		$unapproved_reviews = array(
			ProductHelper::create_product_review( $product->get_id(), '...', '0' ),
			ProductHelper::create_product_review( $product->get_id(), '...', '0' ),
		);
		$this->assertSame( count( $unapproved_reviews ), WC_Comments::get_products_reviews_pending_moderation_counter() );

		// Ensure the existing reviews status changes are handled as intended (flip-flop approved statuses).
		foreach ( $unapproved_reviews as $review_id ) {
			\wp_update_comment(
				array(
					'comment_ID'       => $review_id,
					'comment_approved' => '1',
				)
			);
		}
		$this->assertSame( 0, WC_Comments::get_products_reviews_pending_moderation_counter() );
		foreach ( $unapproved_reviews as $review_id ) {
			\wp_update_comment(
				array(
					'comment_ID'       => $review_id,
					'comment_approved' => '0',
				)
			);
		}
		$this->assertSame( count( $unapproved_reviews ), WC_Comments::get_products_reviews_pending_moderation_counter() );
	}

	/**
	 * Test clear_transients with valid product ID.
	 */
	public function test_clear_transients_with_valid_product() {
		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();

		// Add a review to the product.
		$review_id = ProductHelper::create_product_review( $product_id, 'Great product!' );

		// Add rating meta to the review.
		update_comment_meta( $review_id, 'rating', 5 );

		// Clear transients and verify no fatal error occurs.
		WC_Comments::clear_transients( $product_id );

		// Verify the product still exists and has been updated.
		$updated_product = wc_get_product( $product_id );
		$this->assertInstanceOf( 'WC_Product', $updated_product );
		$this->assertEquals( 1, $updated_product->get_review_count() );
	}

	/**
	 * Test clear_transients with invalid product ID.
	 */
	public function test_clear_transients_with_invalid_product_id() {
		$invalid_product_id = 99999;

		// This should not cause any errors or exceptions.
		$this->expectNotToPerformAssertions();
		WC_Comments::clear_transients( $invalid_product_id );
	}

	/**
	 * Test clear_transients with non-product post ID.
	 */
	public function test_clear_transients_with_non_product_post() {
		// Create a regular post (not a product).
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		// This should not cause any errors or exceptions.
		$this->expectNotToPerformAssertions();
		WC_Comments::clear_transients( $post_id );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test hook akismet_excluded_comment_types integration.
	 */
	public function test_integrates_akismet_excluded_comment_types(): void {
		$this->assertTrue( has_filter( 'akismet_excluded_comment_types' ) );
		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
		$this->assertSame( array( 'order_note' ), apply_filters( 'akismet_excluded_comment_types', array() ) );
	}
}
