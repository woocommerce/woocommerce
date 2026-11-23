<?php
declare( strict_types=1 );

use Automattic\WooCommerce\Enums\OrderStatus;
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
		$this->setExpectedDeprecated( 'WC_Comments::clear_transients' );

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
	 * @testdox maybe_update_product_average_rating updates review count and rating data when a new review is added.
	 */
	public function test_maybe_update_product_average_rating_with_valid_product(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();
		$review_id  = ProductHelper::create_product_review( $product_id, 'Great product!' );

		update_comment_meta( $review_id, 'rating', 5 );
		WC_Comments::maybe_update_product_average_rating( $product_id );

		$this->assertSame( 1, wc_get_product( $product_id )->get_review_count() );

		$product->delete( true );
	}

	/**
	 * @testdox maybe_update_product_average_rating skips product save when review count has not changed.
	 */
	public function test_maybe_update_product_average_rating_skips_save_when_review_count_unchanged(): void {
		$product    = WC_Helper_Product::create_simple_product();
		$product_id = $product->get_id();
		$review_id  = ProductHelper::create_product_review( $product_id, 'Great product!' );

		update_comment_meta( $review_id, 'rating', 5 );
		WC_Comments::maybe_update_product_average_rating( $product_id );

		$save_count = did_action( 'woocommerce_update_product' );
		WC_Comments::maybe_update_product_average_rating( $product_id );
		$this->assertSame( $save_count, did_action( 'woocommerce_update_product' ), 'Product save should not fire when review count is unchanged.' );

		$product->delete( true );
	}

	/**
	 * Test clear_transients with invalid product ID.
	 */
	public function test_clear_transients_with_invalid_product_id() {
		$this->setExpectedDeprecated( 'WC_Comments::clear_transients' );

		$invalid_product_id = 99999;

		WC_Comments::clear_transients( $invalid_product_id );
	}

	/**
	 * Test clear_transients with non-product post ID.
	 */
	public function test_clear_transients_with_non_product_post() {
		$this->setExpectedDeprecated( 'WC_Comments::clear_transients' );

		// Create a regular post (not a product).
		$post_id = wp_insert_post(
			array(
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			)
		);

		WC_Comments::clear_transients( $post_id );

		// Clean up.
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test hook akismet_excluded_comment_types integration.
	 */
	public function test_integrates_akismet_excluded_comment_types(): void {
		$this->assertTrue( has_filter( 'akismet_excluded_comment_types' ) );
		$this->assertSame( array( 'order_note' ), apply_filters( 'akismet_excluded_comment_types', array() ) );
	}

	/**
	 * @testdox Should not increment the cached comment count for comment types excluded from the counts.
	 *
	 * @testWith ["action_log"]
	 *           ["note"]
	 *           ["order_note"]
	 *           ["reaction"]
	 *           ["webhook_delivery"]
	 *
	 * @param string $comment_type Comment type that should be excluded from the counts.
	 */
	public function test_excluded_comment_types_do_not_increment_the_count_cache( string $comment_type ): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'A post that is not a product',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		wp_cache_set( 'wc_count_comments_unapproved', 0, 'wc_comment_counts' );

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $post_id,
				'comment_type'     => $comment_type,
				'comment_approved' => '0',
			)
		);

		$this->assertSame(
			0,
			wp_cache_get( 'wc_count_comments_unapproved', 'wc_comment_counts' ),
			"Inserting a '{$comment_type}' comment should leave the cached unapproved count untouched"
		);

		// Clean up.
		wp_delete_comment( $comment_id, true );
		wp_delete_post( $post_id, true );
	}

	/**
	 * @testdox Should still increment the cached comment count for a regular comment.
	 *
	 * Guards the test above: without this, the exclusion assertions could pass for the wrong reason.
	 */
	public function test_regular_comments_still_increment_the_count_cache(): void {
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'A post that is not a product',
				'post_status' => 'publish',
				'post_type'   => 'post',
			)
		);

		wp_cache_set( 'wc_count_comments_unapproved', 0, 'wc_comment_counts' );

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $post_id,
				'comment_type'     => 'comment',
				'comment_approved' => '0',
			)
		);

		$this->assertSame(
			1,
			wp_cache_get( 'wc_count_comments_unapproved', 'wc_comment_counts' ),
			'A regular comment should increment the cached unapproved count'
		);

		// Clean up.
		wp_delete_comment( $comment_id, true );
		wp_delete_post( $post_id, true );
	}

	/**
	 * @testdox add_comment_rating() stores a rating only for whole numbers from 1 to 5.
	 *
	 * @testWith ["1", 1]
	 *           ["3", 3]
	 *           ["5", 5]
	 *           ["05", 5]
	 *           ["5.0", 5]
	 *           ["4.7", 4]
	 *           ["3abc", 3]
	 *           ["", null]
	 *           ["0", null]
	 *           ["6", null]
	 *           ["-1", null]
	 *           ["-0.5", null]
	 *           ["0.5", null]
	 *           ["0.9", null]
	 *           ["5.9", null]
	 *           ["abc", null]
	 *
	 * @param string   $posted_rating   Raw value posted in the rating field.
	 * @param int|null $expected_rating Rating expected in comment meta, or null when nothing should be stored.
	 */
	public function test_add_comment_rating_stores_only_whole_ratings_in_range( string $posted_rating, ?int $expected_rating ): void {
		$this->assert_posted_rating_is_stored_as( $posted_rating, $expected_rating );
	}

	/**
	 * @testdox add_comment_rating() stores nothing when the rating field is posted as an array.
	 */
	public function test_add_comment_rating_ignores_an_array_rating(): void {
		$this->assert_posted_rating_is_stored_as( array( '5' ), null );
	}

	/**
	 * Post a rating for a fresh product review and assert what ends up in comment meta.
	 *
	 * @param mixed    $posted_rating   Raw value to place in $_POST['rating'].
	 * @param int|null $expected_rating Rating expected in comment meta, or null when nothing should be stored.
	 */
	private function assert_posted_rating_is_stored_as( $posted_rating, ?int $expected_rating ): void {
		$product    = WC_Helper_Product::create_simple_product();
		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID'  => $product->get_id(),
				'comment_type'     => 'review',
				'comment_approved' => '1',
			)
		);

		$_POST['comment_post_ID'] = (string) $product->get_id();
		$_POST['rating']          = $posted_rating;

		try {
			WC_Comments::add_comment_rating( $comment_id );

			$stored = get_comment_meta( $comment_id, 'rating', true );

			if ( null === $expected_rating ) {
				$this->assertSame( '', $stored, 'No rating should have been stored.' );
			} else {
				$this->assertSame( $expected_rating, (int) $stored, 'The stored rating does not match.' );
			}
		} finally {
			unset( $_POST['comment_post_ID'], $_POST['rating'] );
			wp_delete_comment( $comment_id, true );
			$product->delete( true );
		}
	}

	/**
	 * @testdox handle_pre_comment_approved() refuses default-type product comments from non-buyers only while the setting is on.
	 *
	 * @testWith ["yes", "non_buyer", "product", "comment", true]
	 *           ["yes", "non_buyer", "product", "", true]
	 *           ["yes", "guest", "product", "comment", true]
	 *           ["yes", "buyer", "product", "comment", false]
	 *           ["yes", "product_editor", "product", "comment", false]
	 *           ["yes", "non_buyer", "product", "review", false]
	 *           ["yes", "non_buyer", "post", "comment", false]
	 *           ["no", "non_buyer", "product", "comment", false]
	 *
	 * @param string $setting        Value of the verified-owner setting.
	 * @param string $reviewer       Kind of user submitting the comment.
	 * @param string $post_type      Post type receiving the comment.
	 * @param string $comment_type   Comment type being submitted.
	 * @param bool   $expect_refusal Whether the comment should be refused.
	 */
	public function test_pre_comment_approved_applies_verified_owner_setting( string $setting, string $reviewer, string $post_type, string $comment_type, bool $expect_refusal ): void {
		update_option( 'woocommerce_review_rating_verification_required', $setting );
		$product = WC_Helper_Product::create_simple_product();
		$post_id = 'product' === $post_type ? $product->get_id() : self::factory()->post->create();
		wp_set_current_user( $this->create_reviewer( $reviewer, $product ) );

		$result = WC_Comments::handle_pre_comment_approved(
			1,
			array(
				'comment_post_ID' => $post_id,
				'comment_type'    => $comment_type,
			)
		);

		if ( $expect_refusal ) {
			$this->assertWPError( $result, 'The comment should have been refused.' );
			$this->assertSame( 'woocommerce_review_verified_owner_required', $result->get_error_code() );
			$this->assertSame( array( 'status' => 403 ), $result->get_error_data() );
		} else {
			$this->assertSame( 1, $result, 'The approval status should pass through unchanged.' );
		}
	}

	/**
	 * @testdox The WordPress REST comments route refuses product comments from non-buyers, even under a buyer's name and email.
	 *
	 * @testWith ["non_buyer", false, 403]
	 *           ["non_buyer", true, 403]
	 *           ["buyer", false, 201]
	 *
	 * @param string $reviewer        Kind of user sending the request.
	 * @param bool   $use_buyer_email Whether the request names another customer, who bought the product, as the author.
	 * @param int    $expected_status Expected HTTP status.
	 */
	public function test_rest_comments_route_applies_verified_owner_setting( string $reviewer, bool $use_buyer_email, int $expected_status ): void {
		update_option( 'woocommerce_review_rating_verification_required', 'yes' );
		$product = WC_Helper_Product::create_simple_product();
		wp_set_current_user( $this->create_reviewer( $reviewer, $product ) );

		$request = new WP_REST_Request( 'POST', '/wp/v2/comments' );
		$request->set_param( 'post', $product->get_id() );
		$request->set_param( 'content', 'A product review sent through the REST API.' );
		if ( $use_buyer_email ) {
			$buyer = get_userdata( $this->create_reviewer( 'buyer', $product ) );
			$request->set_param( 'author_name', $buyer->display_name );
			$request->set_param( 'author_email', $buyer->user_email );
		}

		$server = $this->create_rest_server_with_routes(
			array(
				static function () {
					( new WP_REST_Comments_Controller() )->register_routes();
				},
			)
		);
		try {
			$response = $server->dispatch( $request );
		} finally {
			$this->clear_rest_server();
		}

		$this->assertSame( $expected_status, $response->get_status() );
		if ( 403 === $expected_status ) {
			$this->assertSame( 'woocommerce_review_verified_owner_required', $response->get_data()['code'] );
		}
		$this->assertSame( 201 === $expected_status ? 1 : 0, $this->count_comments( $product->get_id() ), 'Only an accepted comment should be stored.' );
	}

	/**
	 * @testdox The XML-RPC wp.newComment method refuses product comments from non-buyers.
	 *
	 * @testWith ["non_buyer", false]
	 *           ["buyer", true]
	 *
	 * @param string $reviewer       Kind of user calling the method.
	 * @param bool   $expect_comment Whether the comment should be stored.
	 */
	public function test_xmlrpc_new_comment_applies_verified_owner_setting( string $reviewer, bool $expect_comment ): void {
		require_once ABSPATH . WPINC . '/class-IXR.php';
		require_once ABSPATH . WPINC . '/class-wp-xmlrpc-server.php';

		update_option( 'woocommerce_review_rating_verification_required', 'yes' );
		$product = WC_Helper_Product::create_simple_product();
		$user    = get_userdata( $this->create_reviewer( $reviewer, $product ) );

		$result = ( new wp_xmlrpc_server() )->wp_newComment(
			array( 1, $user->user_login, 'password', $product->get_id(), array( 'content' => 'A product review sent through XML-RPC.' ) )
		);

		if ( $expect_comment ) {
			$this->assertIsInt( $result, 'A buyer should be able to comment through XML-RPC.' );
		} else {
			$this->assertInstanceOf( IXR_Error::class, $result );
			$this->assertSame( 403, $result->code );
			$this->assertSame( 'Only logged in customers who have purchased this product may leave a review.', $result->message );
		}
		$this->assertSame( $expect_comment ? 1 : 0, $this->count_comments( $product->get_id() ), 'Only an accepted comment should be stored.' );
	}

	/**
	 * @testdox The review form stores a buyer's review and refuses a non-buyer with a 403.
	 *
	 * @testWith ["buyer"]
	 *           ["non_buyer"]
	 *
	 * @param string $reviewer Kind of user submitting the form.
	 */
	public function test_review_form_applies_verified_owner_setting( string $reviewer ): void {
		update_option( 'woocommerce_review_rating_verification_required', 'yes' );
		$product = WC_Helper_Product::create_simple_product();
		wp_set_current_user( $this->create_reviewer( $reviewer, $product ) );
		$_POST['comment_post_ID'] = (string) $product->get_id();
		$_POST['rating']          = '5';

		if ( 'non_buyer' === $reviewer ) {
			$this->expectException( WPDieException::class );
			$this->expectExceptionMessage( 'Only logged in customers who have purchased this product may leave a review.' );
			$this->expectExceptionCode( 403 );
		}

		try {
			$comment = wp_handle_comment_submission(
				array(
					'comment_post_ID' => $product->get_id(),
					'comment'         => 'A product review sent through the review form.',
				)
			);
		} finally {
			unset( $_POST['comment_post_ID'], $_POST['rating'] );
		}

		$this->assertInstanceOf( WP_Comment::class, $comment, 'A buyer should be able to use the review form.' );
		$this->assertSame( 'review', $comment->comment_type );
	}

	/**
	 * Create a user to submit product comments, or return 0 for a guest.
	 *
	 * @param string     $kind    One of "buyer", "non_buyer", "product_editor" or "guest".
	 * @param WC_Product $product Product that a buyer has a completed order for.
	 * @return int User ID.
	 */
	private function create_reviewer( string $kind, WC_Product $product ): int {
		if ( 'guest' === $kind ) {
			return 0;
		}

		$user = self::factory()->user->create_and_get(
			array(
				'role'      => 'customer',
				'user_pass' => 'password',
			)
		);

		if ( 'product_editor' === $kind ) {
			$user->add_cap( 'edit_products' );
			$user->add_cap( 'edit_others_products' );
			$user->add_cap( 'edit_published_products' );
		}

		if ( 'buyer' === $kind ) {
			$order = wc_create_order( array( 'customer_id' => $user->ID ) );
			$order->add_product( $product );
			$order->set_status( OrderStatus::COMPLETED );
			$order->save();
		}

		return $user->ID;
	}

	/**
	 * Count the comments on a post, approved or held for moderation.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	private function count_comments( int $post_id ): int {
		return (int) get_comments(
			array(
				'post_id' => $post_id,
				'status'  => 'all',
				'count'   => true,
			)
		);
	}
}
