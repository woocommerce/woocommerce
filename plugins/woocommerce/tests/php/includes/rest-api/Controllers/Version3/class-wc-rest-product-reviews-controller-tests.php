<?php

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;

/**
 * Tests relating to the Product Reviews controller in APIv3.
 */
class WC_REST_Product_Reviews_Controller_Tests extends WC_REST_Unit_Test_Case {
	/**
	 * @var WC_REST_Product_Reviews_Controller
	 * @var WC_REST_Product_Reviews_Controller
	 */
	private $sut;

	/**
	 * @var int
	 */
	private $shop_manager_id;

	/**
	 * @var int
	 */
	private $editor_id;

	/**
	 * @var int
	 */
	private $customer_id;

	/**
	 * @var int
	 */
	private $review_id;

	public function setUp(): void {
		parent::setUp();

		$this->sut             = new WC_REST_Product_Reviews_Controller();
		$this->shop_manager_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->editor_id       = self::factory()->user->create( array( 'role' => 'editor' ) );
		$this->customer_id     = self::factory()->user->create( array( 'role' => 'customer' ) );
		$this->review_id       = ProductHelper::create_product_review(
			ProductHelper::create_simple_product()->get_id(),
			'Pretty good, but not suitable for deep-sea engineering.'
		);
	}

	public function test_permissions_for_creating_product_reviews() {
		$api_request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );

		wp_set_current_user( $this->editor_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_create',
			$this->sut->create_item_permissions_check( $api_request )->get_error_code(),
			'A user lacking edit_products permissions (such as an editor) cannot create product reviews.'
		);

		wp_set_current_user( $this->shop_manager_id );
		$this->assertTrue(
			$this->sut->create_item_permissions_check( $api_request ),
			'A user (such as a shop manager) who has edit_products permissions can create product reviews.'
		);
	}

	/**
	 * @testdox Ensure attempts to retrieve individual product reviews are subject to appropriate permission checks.
	 */
	public function test_permissions_for_retrieving_a_single_product_review() {
		$api_request = new WP_REST_Request( 'GET', '/wc/v3/products/reviews'  . $this->review_id );
		$api_request->set_param( 'id', $this->review_id );

		wp_set_current_user( $this->customer_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_view',
			$this->sut->get_item_permissions_check( $api_request )->get_error_code(),
			'A user lacking moderate_comments permissions (such as a customer) cannot retrieve a product review.'
		);

		wp_set_current_user( $this->editor_id );
		$this->assertTrue(
			$this->sut->get_item_permissions_check( $api_request ),
			'A user (such as a shop manager) who has edit_products permissions can retrieve a product review.'
		);
	}

	/**
	 * @testdox Ensure attempts to retrieve product reviews are subject to appropriate permission checks.
	 */
	public function test_permissions_for_retrieving_multiple_product_reviews() {
		$api_request = new WP_REST_Request( 'GET', '/wc/v3/products/reviews' );

		wp_set_current_user( $this->customer_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_view',
			$this->sut->get_items_permissions_check( $api_request )->get_error_code(),
			'A user lacking moderate_comments permissions (such as a customer) cannot retrieve product reviews.'
		);

		wp_set_current_user( $this->editor_id );
		$this->assertTrue(
			$this->sut->get_items_permissions_check( $api_request ),
			'A user (such as a shop manager) who has edit_products permissions can retrieve product reviews.'
		);
	}

	/**
	 * @testdox Ensure attempts to update product reviews are subject to appropriate permission checks.
	 */
	public function test_permissions_for_updating_product_reviews() {
		$api_request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $this->review_id );
		$api_request->set_param( 'id', $this->review_id );

		wp_set_current_user( $this->editor_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_edit',
			$this->sut->update_item_permissions_check( $api_request )->get_error_code(),
			'A user lacking edit_products permissions (such as an editor) cannot update product reviews.'
		);

		wp_set_current_user( $this->shop_manager_id );
		$this->assertTrue(
			$this->sut->update_item_permissions_check( $api_request ),
			'A user (such as a shop manager) who has edit_products permissions can update product reviews.'
		);
	}

	/**
	 * @testdox Ensure attempts to delete product reviews are subject to appropriate permission checks.
	 */
	public function test_permissions_for_deleting_product_reviews() {
		$api_request = new WP_REST_Request( 'DELETE', '/wc/v3/products/reviews/' . $this->review_id );
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
	}

	/**
	 * @testdox Ensure attempts to modify product reviews (via batches) are subject to appropriate permission checks.
	 */
	public function test_permissions_for_batch_product_reviews() {
		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews/batch' );

		wp_set_current_user( $this->editor_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_batch',
			$this->sut->batch_items_permissions_check( $request )->get_error_code(),
			'A user lacking edit_products permissions (such as an editor) cannot perform batch requests for product reviews.'
		);

		wp_set_current_user( $this->shop_manager_id );
		$this->assertTrue(
			$this->sut->batch_items_permissions_check( $request ),
			'A user (such as a shop manager) who has the edit_products permission can perform batch requests for product reviews.'
		);
	}

	/**
	 * @testdox Ensure attempts to delete comments other than product reviews are not possible via the product review endpoints.
	 */
	public function test_cannot_delete_other_comment_types() {
		$order         = OrderHelper::create_order();
		$order_note_id = $order->add_order_note( 'Updated quantities per customer request.' );

		wp_set_current_user( $this->shop_manager_id );
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/reviews/' . $order_note_id );
		$request->set_param( 'id', $order_note_id );

		$this->assertEquals(
			'woocommerce_rest_review_invalid_id',
			$this->sut->delete_item( $request )->get_error_code(),
			'Comments that are not product reviews cannot be deleted via this endpoint.'
		);

		$comment_id = wp_insert_comment(
			array(
				'comment_post_ID' => ProductHelper::create_simple_product()->get_id(),
				'comment_type'    => 'comment',
				'comment_content' => 'I am a regular comment (typically left by an admin/shop manager as a response to product reviews.'
			)
		);

		$request = new WP_REST_Request( 'DELETE', '/wc/v3/products/reviews/' . $comment_id );
		$request->set_param( 'id', $comment_id );

		$this->assertEquals(
			'woocommerce_rest_review_invalid_id',
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
	 * @testdox Creating a rated review on hold does not save the product.
	 */
	public function test_create_item_does_not_save_the_product_for_a_held_status() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$saves      = 0;
		$count_save = function ( $updated_product_id ) use ( &$saves, $product_id ) {
			if ( $product_id === (int) $updated_product_id ) {
				++$saves;
			}
		};

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_body_params(
			array(
				'product_id'     => $product_id,
				'review'         => 'Waiting for moderation.',
				'reviewer'       => 'Jane Smith',
				'reviewer_email' => 'jane.smith@example.org',
				'rating'         => 4,
				'status'         => 'hold',
			)
		);

		add_action( 'woocommerce_update_product', $count_save );
		try {
			$response = $this->server->dispatch( $request );
		} finally {
			remove_action( 'woocommerce_update_product', $count_save );
		}

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 0, $saves );
		$this->assertEquals( 0, wc_get_product( $product_id )->get_average_rating() );
	}

	/**
	 * @testdox Creating further reviews keeps the average rating in step with every stored rating.
	 */
	public function test_create_item_updates_the_average_rating_for_every_review() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$this->create_review( $product_id, 'Holds up to daily use.', 5 );
		$this->create_review( $product_id, 'Fell apart in a week.', 1 );

		$product = wc_get_product( $product_id );
		$this->assertEquals( 3, $product->get_average_rating(), 'The average rating covers both reviews, not just the earlier one.' );
		$this->assertEquals(
			array(
				1 => 1,
				5 => 1,
			),
			$product->get_rating_counts(),
			'The rating counts cover both reviews.'
		);
		$this->assertEquals( 2, $product->get_review_count(), 'Both reviews are counted.' );
	}

	/**
	 * @testdox Creating a review without a rating leaves the average rating untouched.
	 */
	public function test_create_item_without_a_rating_leaves_the_average_rating_untouched() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$this->create_review( $product_id, 'Holds up to daily use.', 4 );
		$this->create_review( $product_id, 'Arrived on time.', null );

		$product = wc_get_product( $product_id );
		$this->assertEquals( 4, $product->get_average_rating(), 'A review without a rating does not affect the average rating.' );
		$this->assertEquals( array( 4 => 1 ), $product->get_rating_counts(), 'A review without a rating is not counted as a rating.' );
		$this->assertEquals( 2, $product->get_review_count(), 'A review without a rating is still counted as a review.' );
	}

	/**
	 * @testdox Editing the rating of a review recalculates the product rating aggregates.
	 */
	public function test_update_item_recalculates_the_aggregates_when_the_rating_changes() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$this->create_review( $product_id, 'Holds up to daily use.', 5 );
		$review_id = $this->create_review( $product_id, 'Fell apart in a week.', 1 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params( array( 'rating' => 3 ) );

		$saves      = 0;
		$count_save = function ( $updated_product_id ) use ( &$saves, $product_id ) {
			if ( $product_id === (int) $updated_product_id ) {
				++$saves;
			}
		};

		add_action( 'woocommerce_update_product', $count_save );
		try {
			$response = $this->server->dispatch( $request );
		} finally {
			remove_action( 'woocommerce_update_product', $count_save );
		}

		$this->assertEquals( 200, $response->get_status(), 'The review is updated successfully.' );
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
	 * @testdox Editing review content and rating saves the product once.
	 */
	public function test_update_item_with_content_and_rating_saves_the_product_once() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();
		$review_id  = $this->create_review( $product_id, 'Original review.', 5 )->get_data()['id'];

		$saves      = 0;
		$count_save = function ( $updated_product_id ) use ( &$saves, $product_id ) {
			if ( $product_id === (int) $updated_product_id ) {
				++$saves;
			}
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params(
			array(
				'review' => 'Updated review.',
				'rating' => 2,
			)
		);

		add_action( 'woocommerce_update_product', $count_save );
		try {
			$response = $this->server->dispatch( $request );
		} finally {
			remove_action( 'woocommerce_update_product', $count_save );
		}

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 2, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertSame( 1, $saves );
	}

	/**
	 * @testdox A zero rating remains a successful no-op in wc/v3.
	 */
	public function test_update_item_keeps_zero_rating_as_a_no_op() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();
		$review_id  = $this->create_review( $product_id, 'Still five stars.', 5 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params( array( 'rating' => 0 ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 5, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertEquals( 5, wc_get_product( $product_id )->get_average_rating() );
	}

	/**
	 * @testdox A malformed comment_meta value from the preprocess filter returns an update error.
	 */
	public function test_update_item_rejects_malformed_filtered_comment_meta() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();
		$review_id  = $this->create_review( $product_id, 'Still five stars.', 5 )->get_data()['id'];

		$set_malformed_comment_meta = static function ( $prepared_review ) {
			$prepared_review['comment_meta'] = 'not-an-array';
			return $prepared_review;
		};

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 3 );

		add_filter( 'woocommerce_rest_preprocess_product_review', $set_malformed_comment_meta );
		try {
			$response = $this->sut->update_item( $request );
		} finally {
			remove_filter( 'woocommerce_rest_preprocess_product_review', $set_malformed_comment_meta );
		}

		$this->assertWPError( $response );
		$this->assertSame( 'woocommerce_rest_comment_failed_edit', $response->get_error_code() );
		$this->assertSame( 5, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertEquals( 5, wc_get_product( $product_id )->get_average_rating() );
	}

	/**
	 * @testdox Holding and re-approving a review excludes and re-includes its rating.
	 */
	public function test_update_item_status_changes_keep_the_aggregates_correct() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$this->create_review( $product_id, 'Holds up to daily use.', 5 );
		$review_id = $this->create_review( $product_id, 'Not for me.', 3 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params( array( 'status' => 'hold' ) );
		$this->server->dispatch( $request );

		$product = wc_get_product( $product_id );
		$this->assertEquals( 5, $product->get_average_rating(), 'A review on hold is excluded from the average rating.' );
		$this->assertEquals( 1, $product->get_review_count(), 'A review on hold is excluded from the review count.' );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params( array( 'status' => 'approved' ) );
		$this->server->dispatch( $request );

		$product = wc_get_product( $product_id );
		$this->assertEquals( 4, $product->get_average_rating(), 'An approved review is included in the average rating again.' );
		$this->assertEquals( 2, $product->get_review_count(), 'An approved review is included in the review count again.' );
	}

	/**
	 * @testdox Moving a review to another product updates the aggregates of both products.
	 */
	public function test_update_item_recalculates_the_aggregates_of_both_products_when_the_review_moves() {
		wp_set_current_user( $this->shop_manager_id );
		$source_id      = ProductHelper::create_simple_product()->get_id();
		$destination_id = ProductHelper::create_simple_product()->get_id();

		$review_id = $this->create_review( $source_id, 'Holds up to daily use.', 5 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params(
			array(
				'product_id' => $destination_id,
				'rating'     => 3,
			)
		);
		$counted_products = array();
		$record_count     = function ( $post_id ) use ( &$counted_products ) {
			$counted_products[] = (int) $post_id;
		};

		add_action( 'wp_update_comment_count', $record_count );
		try {
			$response = $this->server->dispatch( $request );
		} finally {
			remove_action( 'wp_update_comment_count', $record_count );
		}
		$this->assertEquals( 200, $response->get_status(), 'The review is moved successfully.' );
		$this->assertSame( array( $destination_id, $source_id ), $counted_products );

		$source = wc_get_product( $source_id );
		$this->assertEquals( 0, $source->get_average_rating(), 'The product the review left no longer counts its rating.' );
		$this->assertEquals( array(), $source->get_rating_counts(), 'The product the review left no longer counts it in the rating counts.' );
		$this->assertEquals( 0, $source->get_review_count(), 'The product the review left no longer counts it as a review.' );

		$destination = wc_get_product( $destination_id );
		$this->assertEquals( 3, $destination->get_average_rating(), 'The product the review moved to picks up the new rating.' );
		$this->assertEquals( array( 3 => 1 ), $destination->get_rating_counts(), 'The product the review moved to picks up the rating counts.' );
		$this->assertEquals( 1, $destination->get_review_count(), 'The product the review moved to counts the review.' );

		clean_post_cache( $source_id );
		clean_post_cache( $destination_id );
		$this->assertSame( 0, (int) get_post( $source_id )->comment_count );
		$this->assertSame( 1, (int) get_post( $destination_id )->comment_count );
	}

	/**
	 * @testdox A supplied zero product ID is rejected without moving the review.
	 */
	public function test_update_item_rejects_a_zero_product_id() {
		wp_set_current_user( $this->shop_manager_id );
		$source_id = ProductHelper::create_simple_product()->get_id();
		$review_id = $this->create_review( $source_id, 'Stays with its product.', 5 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params( array( 'product_id' => 0 ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_product_invalid_id', $response->get_data()['code'] );
		$this->assertSame( $source_id, (int) get_comment( $review_id )->comment_post_ID );
		$this->assertEquals( 5, wc_get_product( $source_id )->get_average_rating() );
		$this->assertEquals( 1, wc_get_product( $source_id )->get_review_count() );

		clean_post_cache( $source_id );
		$this->assertSame( 1, (int) get_post( $source_id )->comment_count );
	}

	/**
	 * @testdox Moving a review without changing its rating still updates the aggregates of both products.
	 */
	public function test_update_item_recalculates_the_aggregates_when_the_review_moves_without_a_rating_change() {
		wp_set_current_user( $this->shop_manager_id );
		$source_id      = ProductHelper::create_simple_product()->get_id();
		$destination_id = ProductHelper::create_simple_product()->get_id();

		$review_id = $this->create_review( $source_id, 'Holds up to daily use.', 4 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params( array( 'product_id' => $destination_id ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status(), 'The review is moved successfully.' );

		$source = wc_get_product( $source_id );
		$this->assertEquals( 0, $source->get_average_rating(), 'The product the review left drops it even though the rating did not change.' );
		$this->assertEquals( 0, $source->get_review_count(), 'The product the review left no longer counts it as a review.' );

		$destination = wc_get_product( $destination_id );
		$this->assertEquals( 4, $destination->get_average_rating(), 'The product the review moved to keeps the existing rating.' );
		$this->assertEquals( 1, $destination->get_review_count(), 'The product the review moved to counts the review.' );

		clean_post_cache( $source_id );
		clean_post_cache( $destination_id );
		$this->assertSame( 0, (int) get_post( $source_id )->comment_count );
		$this->assertSame( 1, (int) get_post( $destination_id )->comment_count );
	}

	/**
	 * @testdox Moving a review honours deferred comment counting for both products.
	 */
	public function test_update_item_defers_both_products_when_a_review_moves() {
		wp_set_current_user( $this->shop_manager_id );
		$source_id      = ProductHelper::create_simple_product()->get_id();
		$destination_id = ProductHelper::create_simple_product()->get_id();
		$review_id      = $this->create_review( $source_id, 'Moving later.', 4 )->get_data()['id'];

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_body_params( array( 'product_id' => $destination_id ) );

		wp_defer_comment_counting( true );
		try {
			$response = $this->server->dispatch( $request );
			$this->assertSame( 200, $response->get_status() );

			clean_post_cache( $source_id );
			clean_post_cache( $destination_id );
			$this->assertSame( 1, (int) get_post( $source_id )->comment_count );
			$this->assertSame( 0, (int) get_post( $destination_id )->comment_count );
		} finally {
			wp_defer_comment_counting( false );
		}

		clean_post_cache( $source_id );
		clean_post_cache( $destination_id );
		$this->assertSame( 0, (int) get_post( $source_id )->comment_count );
		$this->assertSame( 1, (int) get_post( $destination_id )->comment_count );

		$source      = wc_get_product( $source_id );
		$destination = wc_get_product( $destination_id );
		$this->assertEquals( 0, $source->get_average_rating() );
		$this->assertEquals( 4, $destination->get_average_rating() );
	}

	/**
	 * Creates a product review through the REST API.
	 *
	 * @param int      $product_id ID of the product being reviewed.
	 * @param string   $content    Review content. Must differ between reviews, as WordPress rejects duplicates.
	 * @param int|null $rating     Rating to submit, or null to omit the rating field.
	 * @return WP_REST_Response
	 */
	private function create_review( int $product_id, string $content, ?int $rating ) {
		$body = array(
			'product_id'     => $product_id,
			'review'         => $content,
			'reviewer'       => 'Jane Smith',
			'reviewer_email' => 'jane.smith@example.org',
		);

		if ( null !== $rating ) {
			$body['rating'] = $rating;
		}

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_body_params( $body );

		return $this->server->dispatch( $request );
	}
}
