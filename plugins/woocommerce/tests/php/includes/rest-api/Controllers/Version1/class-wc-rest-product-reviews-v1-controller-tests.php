<?php

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests relating to WC_REST_Product_Reviews_V1_Controller.
 */
class WC_REST_Product_Reviews_V1_Controller_Tests extends WC_Unit_Test_Case {
	/**
	 * @var int
	 */
	private $customer_id;

	/**
	 * @var int
	 */
	private $editor_id;

	/**
	 * @var int
	 */
	private $shop_manager_id;

	/**
	 * @var int
	 */
	private $product_id;

	/**
	 * @var int
	 */
	private $review_id;

	/**
	 * @var WC_REST_Product_Reviews_V1_Controller
	 */
	private $sut;

	/**
	 * Creates test users with varying permissions.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut             = new WC_REST_Product_Reviews_V1_Controller();
		$this->shop_manager_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->editor_id       = self::factory()->user->create( array( 'role' => 'editor' ) );
		$this->customer_id     = self::factory()->user->create( array( 'role' => 'customer' ) );
		$this->product_id      = ProductHelper::create_simple_product()->get_id();
		$this->review_id       = ProductHelper::create_product_review(
			$this->product_id,
			'Supposed to be made from real unicorn horn but was actually cheap cardboard. OK for the price.'
		);
	}

	public function test_permissions_for_reading_product_reviews() {
		$api_request = new WP_REST_Request( 'GET', '/wc/v1/products/' . $this->product_id . '/reviews/' );

		wp_set_current_user( $this->customer_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_view',
			$this->sut->get_items_permissions_check( $api_request )->get_error_code(),
			'A user (such as a customer) lacking the moderate_comments capability cannot list reviews.'
		);

		wp_set_current_user( $this->editor_id );
		$this->assertTrue(
			$this->sut->get_items_permissions_check( $api_request ),
			'A user (such as an editor) who has the moderate_comments capability can list reviews.'
		);
	}
	/**
	 * @testdox Ensure attempts to create product reviews are checked for user permissions.
	 */
	public function test_permissions_for_updating_product_reviews() {
		$api_request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $this->product_id . '/reviews/' . $this->review_id );
		$api_request->set_param( 'product_id', $this->product_id );
		$api_request->set_param( 'id', $this->review_id );
		$api_request->set_body( '{ "review": "Modified automatically." }' );

		wp_set_current_user( $this->editor_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_edit',
			$this->sut->update_item_permissions_check( $api_request )->get_error_code(),
			'A user (such as an editor) lacking edit_comment permissions cannot update a product review.'
		);

		wp_set_current_user( $this->shop_manager_id );
		$this->assertTrue(
			$this->sut->update_item_permissions_check( $api_request ),
			'A user (such as a shop manager) who has edit_comment permissions can update a product review.'
		);

		$nonexistent_product_id = $this->product_id * 10;
		$api_request->set_route( "/wc/v1/products/{$nonexistent_product_id}/reviews/" . $this->review_id );
		$api_request->set_param( 'product_id', $nonexistent_product_id );

		$this->assertEquals(
			'woocommerce_rest_product_invalid_id',
			$this->sut->update_item( $api_request )->get_error_code(),
			'Attempts to edit reviews for non-existent products are rejected.'
		);
	}

	/**
	 * @testdox Ensure attempts to delete product reviews are checked for user permissions.
	 */
	public function test_permissions_for_deleting_product_reviews() {
		$api_request = new WP_REST_Request( 'DELETE', '/wc/v1/products/' . $this->product_id . '/reviews/' . $this->review_id );
		$api_request->set_param( 'product_id', $this->product_id );
		$api_request->set_param( 'id', $this->review_id );

		wp_set_current_user( $this->editor_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_delete',
			$this->sut->delete_item_permissions_check( $api_request )->get_error_code(),
			'A user lacking edit_comment permissions (such as an editor) cannot delete a product review.'
		);

		wp_set_current_user( $this->shop_manager_id );
		$this->assertTrue(
			$this->sut->delete_item_permissions_check( $api_request ),
			'A user (such as a shop manager) who has the edit_comment permission can delete a product review.'
		);

		$nonexistent_product_id = $this->product_id * 10;
		$api_request = new WP_REST_Request( 'DELETE', '/wc/v1/products/' . $nonexistent_product_id . '/reviews/' . $this->review_id );
		$api_request->set_param( 'product_id', $nonexistent_product_id );
		$api_request->set_param( 'id', $this->review_id );

		$this->assertEquals(
			'woocommerce_rest_product_invalid_id',
			$this->sut->delete_item( $api_request )->get_error_code(),
			'Attempts to delete reviews for non-existent products are rejected, even if the review ID is valid.'
		);
	}

	/**
	 * @testdox Ensure attempts to delete comments other than product reviews are not possible via the product review endpoints.
	 */
	public function test_cannot_delete_other_comment_types() {
		$order         = OrderHelper::create_order();
		$order_note_id = $order->add_order_note( 'Dispatched with all due haste.' );

		wp_set_current_user( $this->shop_manager_id );
		$request = new WP_REST_Request( 'DELETE', '/wc/v1/products/123456789/reviews/' . $order_note_id );
		$request->set_param( 'id', $order_note_id );

		$this->assertEquals(
			'woocommerce_rest_product_invalid_id',
			$this->sut->delete_item( $request )->get_error_code(),
			'Comments that are not product reviews cannot be deleted via this endpoint.'
		);

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID' => $this->product_id,
				'comment_type'    => 'comment',
				'comment_content' => 'I am a regular comment (typically left by an admin/shop manager as a response to product reviews.'
			)
		);

		$request = new WP_REST_Request( 'DELETE', '/wc/v1/products/123456789/reviews/' . $comment_id );
		$request->set_param( 'id', $comment_id );

		$this->assertEquals(
			'woocommerce_rest_product_invalid_id',
			$this->sut->delete_item( $request )->get_error_code(),
			'Comments that are not product reviews (including other types of comments belonging to products) cannot be deleted via this endpoint.'
		);
	}

	/**
	 * @testdox Creating a review updates the product rating aggregates within the same request.
	 */
	public function test_create_item_updates_the_product_rating_aggregates() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$response = $this->create_review( $product_id, 'Holds up to daily use.', 5 );
		$this->assertEquals( 201, $response->get_status(), 'The review is created successfully.' );

		$product = wc_get_product( $product_id );
		$this->assertEquals( 5, $product->get_average_rating(), 'The average rating includes the review created in the same request.' );
		$this->assertEquals( array( 5 => 1 ), $product->get_rating_counts(), 'The rating counts include the review created in the same request.' );
		$this->assertEquals( 1, $product->get_review_count(), 'The review count includes the review created in the same request.' );
	}

	/**
	 * @testdox Creating a held review does not save the product after rating meta is written.
	 */
	public function test_create_item_does_not_save_the_product_for_a_review_held_for_moderation() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$hold_review = function ( $prepared_review ) {
			$prepared_review['comment_approved'] = 0;
			return $prepared_review;
		};
		$saves       = 0;
		$count_save  = function ( $updated_product_id ) use ( &$saves, $product_id ) {
			if ( $product_id === (int) $updated_product_id ) {
				++$saves;
			}
		};

		add_filter( 'rest_pre_insert_product_review', $hold_review );
		add_action( 'woocommerce_update_product', $count_save );
		try {
			$this->create_review( $product_id, 'Waiting for moderation.', 4 );
		} finally {
			remove_action( 'woocommerce_update_product', $count_save );
			remove_filter( 'rest_pre_insert_product_review', $hold_review );
		}

		$this->assertSame( 0, $saves );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_average_rating() );
	}

	/**
	 * @testdox Creating an unrated review saves the product only for Core's comment-count update.
	 */
	public function test_create_item_without_a_rating_saves_the_product_once() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$saves      = 0;
		$count_save = function ( $updated_product_id ) use ( &$saves, $product_id ) {
			if ( $product_id === (int) $updated_product_id ) {
				++$saves;
			}
		};

		add_action( 'woocommerce_update_product', $count_save );
		try {
			$response = $this->create_review( $product_id, 'Arrived on time.', null );
		} finally {
			remove_action( 'woocommerce_update_product', $count_save );
		}

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 1, $saves, 'An unrated review does not trigger a second aggregate refresh.' );

		$product = wc_get_product( $product_id );
		$this->assertEquals( 0, $product->get_average_rating() );
		$this->assertSame( array(), $product->get_rating_counts() );
		$this->assertEquals( 1, $product->get_review_count() );
	}

	/**
	 * @testdox A rating-only edit stores the rating and refreshes aggregates in one product save.
	 */
	public function test_update_item_accepts_an_edit_that_changes_only_the_rating() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$this->create_review( $product_id, 'Holds up to daily use.', 5 );
		$review_id = $this->create_review( $product_id, 'Fell apart in a week.', 1 )->get_data()['id'];

		$saves      = 0;
		$count_save = function ( $updated_product_id ) use ( &$saves, $product_id ) {
			if ( $product_id === (int) $updated_product_id ) {
				++$saves;
			}
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $product_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $product_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 3 );

		add_action( 'woocommerce_update_product', $count_save );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_action( 'woocommerce_update_product', $count_save );
		}

		$this->assertNotWPError( $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 3, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertSame( 1, $saves, 'The core count callback sees the new rating, so no second save is needed.' );

		$product = wc_get_product( $product_id );
		$this->assertEquals( 4, $product->get_average_rating(), 'The average rating reflects the new rating, not the one it replaced.' );
		$this->assertEquals(
			array(
				3 => 1,
				5 => 1,
			),
			$product->get_rating_counts(),
			'The rating counts drop the replaced rating.'
		);
	}

	/**
	 * @testdox A genuine comment update failure returns an error and does not write the rating.
	 */
	public function test_update_item_does_not_write_rating_after_a_comment_update_failure() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();
		$review_id  = $this->create_review( $product_id, 'Original review.', 1 )->get_data()['id'];

		$fail_update = static function () {
			return new WP_Error( 'forced_comment_update_failure' );
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $product_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $product_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'review', 'This must not be stored.' );
		$request->set_param( 'rating', 3 );

		add_filter( 'wp_update_comment_data', $fail_update );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_filter( 'wp_update_comment_data', $fail_update );
		}

		$this->assertWPError( $response );
		$this->assertSame( 'rest_product_review_failed_edit', $response->get_error_code() );
		$this->assertSame( 'Original review.', get_comment( $review_id )->comment_content );
		$this->assertSame( 1, (int) get_comment_meta( $review_id, 'rating', true ) );
	}

	/**
	 * @testdox A WP_Error from the preprocess filter is returned unchanged.
	 */
	public function test_update_item_returns_filtered_wp_error_unchanged() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();
		$review_id  = $this->create_review( $product_id, 'Original review.', 5 )->get_data()['id'];

		$filtered_error = new WP_Error( 'filtered_product_review_error', 'The filter rejected this review.' );
		$return_error   = static function () use ( $filtered_error ) {
			return $filtered_error;
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $product_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $product_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 3 );

		add_filter( 'rest_preprocess_product_review', $return_error );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_filter( 'rest_preprocess_product_review', $return_error );
		}

		$this->assertSame( $filtered_error, $response, 'The controller preserves the filter error object.' );
		$this->assertSame( 5, (int) get_comment_meta( $review_id, 'rating', true ) );
	}

	/**
	 * @testdox A zero rating remains a successful no-op.
	 */
	public function test_update_item_keeps_zero_rating_as_a_no_op() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();
		$review_id  = $this->create_review( $product_id, 'Still five stars.', 5 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $product_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $product_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 0 );

		$response = $this->sut->update_item( $request );

		$this->assertNotWPError( $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 5, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertEquals( 5, wc_get_product( $product_id )->get_average_rating() );
	}

	/**
	 * Provides malformed review values returned by the preprocess filter.
	 *
	 * @return array<string, array{callable}> Malformed review callbacks.
	 */
	public function data_provider_for_test_update_item_rejects_malformed_filtered_review(): array {
		return array(
			'scalar review'       => array(
				static function () {
					return 'not-an-array';
				},
			),
			'scalar comment meta' => array(
				static function ( $prepared_review ) {
					$prepared_review['comment_meta'] = 'not-an-array';
					return $prepared_review;
				},
			),
		);
	}

	/**
	 * @testdox A malformed review from the preprocess filter returns an update error.
	 * @dataProvider data_provider_for_test_update_item_rejects_malformed_filtered_review
	 *
	 * @param callable $filter_callback Callback that returns a malformed review value.
	 */
	public function test_update_item_rejects_malformed_filtered_review( callable $filter_callback ) {
		wp_set_current_user( $this->shop_manager_id );
		$product_id            = ProductHelper::create_simple_product()->get_id();
		$review_id             = $this->create_review( $product_id, 'Still five stars.', 5 )->get_data()['id'];
		$average_rating_before = wc_get_product( $product_id )->get_average_rating();

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $product_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $product_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 3 );

		add_filter( 'rest_preprocess_product_review', $filter_callback );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_filter( 'rest_preprocess_product_review', $filter_callback );
		}

		$this->assertWPError( $response );
		$this->assertSame( 'rest_product_review_failed_edit', $response->get_error_code() );
		$this->assertSame( 5, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertSame( $average_rating_before, wc_get_product( $product_id )->get_average_rating() );
	}

	/**
	 * @testdox Creating a review updates the aggregates of the product the review was written to.
	 */
	public function test_create_item_updates_the_aggregates_of_the_product_the_review_was_written_to() {
		wp_set_current_user( $this->shop_manager_id );
		$requested_product_id = ProductHelper::create_simple_product()->get_id();
		$written_product_id   = ProductHelper::create_simple_product()->get_id();

		// Third party code can send the review to a different product than the request named.
		$send_to_other_product = function ( $prepared_review ) use ( $written_product_id ) {
			$prepared_review['comment_post_ID'] = $written_product_id;
			return $prepared_review;
		};

		add_filter( 'rest_pre_insert_product_review', $send_to_other_product );
		try {
			$this->create_review( $requested_product_id, 'Holds up to daily use.', 5 );
		} finally {
			remove_filter( 'rest_pre_insert_product_review', $send_to_other_product );
		}

		$written_product = wc_get_product( $written_product_id );
		$this->assertEquals( 5, $written_product->get_average_rating(), 'The product the review was written to has the new average rating.' );
		$this->assertEquals( array( 5 => 1 ), $written_product->get_rating_counts(), 'The product the review was written to has the new rating counts.' );
		$this->assertEquals( 1, $written_product->get_review_count(), 'The product the review was written to counts the review.' );
	}

	/**
	 * @testdox A filter-driven move refreshes both products and their core comment counts.
	 */
	public function test_update_item_recalculates_both_products_when_a_filter_moves_the_review() {
		wp_set_current_user( $this->shop_manager_id );
		$source_id      = ProductHelper::create_simple_product()->get_id();
		$destination_id = ProductHelper::create_simple_product()->get_id();

		$this->create_review( $destination_id, 'Already here.', 1 );
		$review_id = $this->create_review( $source_id, 'Starts at five.', 5 )->get_data()['id'];

		$send_to_destination = function ( $prepared_review ) use ( $destination_id ) {
			$prepared_review['comment_post_ID'] = $destination_id;
			return $prepared_review;
		};
		$counted_products    = array();
		$record_count        = function ( $post_id ) use ( &$counted_products ) {
			$counted_products[] = (int) $post_id;
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $source_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $source_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 2 );

		add_filter( 'rest_preprocess_product_review', $send_to_destination );
		add_action( 'wp_update_comment_count', $record_count );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_action( 'wp_update_comment_count', $record_count );
			remove_filter( 'rest_preprocess_product_review', $send_to_destination );
		}

		$this->assertNotWPError( $response );
		$this->assertSame( $destination_id, (int) get_comment( $review_id )->comment_post_ID );
		$this->assertSame( 2, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertEqualsCanonicalizing( array( $source_id, $destination_id ), $counted_products );

		$source = wc_get_product( $source_id );
		$this->assertEquals( 0, $source->get_average_rating() );
		$this->assertSame( array(), $source->get_rating_counts() );
		$this->assertEquals( 0, $source->get_review_count() );

		$destination = wc_get_product( $destination_id );
		$this->assertEquals( 1.5, $destination->get_average_rating() );
		$this->assertEquals(
			array(
				1 => 1,
				2 => 1,
			),
			$destination->get_rating_counts()
		);
		$this->assertEquals( 2, $destination->get_review_count() );

		clean_post_cache( $source_id );
		clean_post_cache( $destination_id );
		$this->assertSame( 0, (int) get_post( $source_id )->comment_count );
		$this->assertSame( 2, (int) get_post( $destination_id )->comment_count );
	}

	/**
	 * @testdox A Core filter redirect recounts the original and persisted products exactly once.
	 */
	public function test_update_item_recounts_both_products_when_wp_update_comment_data_moves_the_review() {
		wp_set_current_user( $this->shop_manager_id );
		$source_id      = ProductHelper::create_simple_product()->get_id();
		$destination_id = ProductHelper::create_simple_product()->get_id();
		$review_id      = $this->create_review( $source_id, 'Starts at five.', 5 )->get_data()['id'];

		$redirect_review  = static function ( $comment_data ) use ( $destination_id ) {
			$comment_data['comment_post_ID'] = $destination_id;
			return $comment_data;
		};
		$counted_products = array();
		$record_count     = static function ( $post_id ) use ( &$counted_products ) {
			$counted_products[] = (int) $post_id;
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $source_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $source_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'review', 'Moved by a Core filter.' );

		add_filter( 'wp_update_comment_data', $redirect_review );
		add_action( 'wp_update_comment_count', $record_count );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_action( 'wp_update_comment_count', $record_count );
			remove_filter( 'wp_update_comment_data', $redirect_review );
		}

		$this->assertNotWPError( $response );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $destination_id, (int) get_comment( $review_id )->comment_post_ID );
		$this->assertEqualsCanonicalizing( array( $source_id, $destination_id ), $counted_products );

		$source = wc_get_product( $source_id );
		$this->assertEquals( 0, $source->get_average_rating() );
		$this->assertEquals( 0, $source->get_review_count() );

		$destination = wc_get_product( $destination_id );
		$this->assertEquals( 5, $destination->get_average_rating() );
		$this->assertEquals( 1, $destination->get_review_count() );

		clean_post_cache( $source_id );
		clean_post_cache( $destination_id );
		$this->assertSame( 0, (int) get_post( $source_id )->comment_count );
		$this->assertSame( 1, (int) get_post( $destination_id )->comment_count );
	}

	/**
	 * @testdox A filter-driven move to post zero still refreshes the source product.
	 */
	public function test_update_item_refreshes_the_source_when_a_filter_moves_the_review_to_post_zero() {
		wp_set_current_user( $this->shop_manager_id );
		$source_id = ProductHelper::create_simple_product()->get_id();
		$review_id = $this->create_review( $source_id, 'Starts at five.', 5 )->get_data()['id'];

		$send_to_post_zero = static function ( $prepared_review ) {
			$prepared_review['comment_post_ID'] = 0;
			return $prepared_review;
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v1/products/' . $source_id . '/reviews/' . $review_id );
		$request->set_param( 'product_id', $source_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 2 );

		add_filter( 'rest_preprocess_product_review', $send_to_post_zero );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_filter( 'rest_preprocess_product_review', $send_to_post_zero );
		}

		$this->assertNotWPError( $response );
		$this->assertSame( 0, (int) get_comment( $review_id )->comment_post_ID );
		$this->assertEquals( 0, wc_get_product( $source_id )->get_average_rating() );
		$this->assertEquals( 0, wc_get_product( $source_id )->get_review_count() );

		clean_post_cache( $source_id );
		$this->assertSame( 0, (int) get_post( $source_id )->comment_count );
	}

	/**
	 * Creates a product review through the controller.
	 *
	 * @param int      $product_id ID of the product being reviewed.
	 * @param string   $content    Review content.
	 * @param int|null $rating     Rating to submit, or null to omit the rating field.
	 * @return WP_REST_Response
	 */
	private function create_review( int $product_id, string $content, ?int $rating ) {
		$request = new WP_REST_Request( 'POST', '/wc/v1/products/' . $product_id . '/reviews' );
		$request->set_param( 'product_id', $product_id );
		$request->set_param( 'review', $content );
		$request->set_param( 'name', 'Jane Smith' );
		$request->set_param( 'email', 'jane.smith@example.org' );

		if ( null !== $rating ) {
			$request->set_param( 'rating', $rating );
		}

		return $this->sut->create_item( $request );
	}
}
