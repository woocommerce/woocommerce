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
	 * @testdox Creating a product review through the REST API refreshes the product's aggregate rating data so structured data on the storefront stays in sync.
	 */
	public function test_create_item_refreshes_product_rating_meta() {
		wp_set_current_user( $this->shop_manager_id );
		$product = ProductHelper::create_simple_product();

		// Sanity-check that the freshly created product has no rating data yet.
		$this->assertEquals( 0, (float) $product->get_average_rating(), 'Fresh products should start with no average rating.' );
		$this->assertEquals( 0, (int) $product->get_review_count(), 'Fresh products should start with no reviews counted.' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/products/reviews' );
		$request->set_param( 'product_id', $product->get_id() );
		$request->set_param( 'reviewer', 'CLI Reviewer' );
		$request->set_param( 'reviewer_email', 'cli-reviewer@example.com' );
		$request->set_param( 'review', 'Pretty good overall.' );
		$request->set_param( 'rating', 5 );
		$request->set_param( 'status', 'approved' );

		$response = $this->sut->create_item( $request );

		$this->assertEquals( 201, $response->get_status(), 'A valid create request should return a 201 status.' );

		$refreshed = wc_get_product( $product->get_id() );
		$this->assertEquals( 5.0, (float) $refreshed->get_average_rating(), 'Creating an approved review via REST should update the product average rating.' );
		$this->assertEquals( 1, (int) $refreshed->get_review_count(), 'Creating an approved review via REST should update the product review count.' );
		$this->assertEquals( array( 5 => 1 ), $refreshed->get_rating_counts(), 'Creating an approved review via REST should update the product rating count distribution.' );
	}

	/**
	 * @testdox Updating a product review's rating through the REST API refreshes the product's aggregate rating data.
	 */
	public function test_update_item_refreshes_product_rating_meta() {
		wp_set_current_user( $this->shop_manager_id );
		$product   = ProductHelper::create_simple_product();
		$review_id = ProductHelper::create_product_review( $product->get_id(), 'Initial review content.' );

		// Pre-warm the aggregate fields the way the GUI path would.
		WC_Comments::clear_transients( $product->get_id() );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/products/reviews/' . $review_id );
		$request->set_param( 'id', $review_id );
		$request->set_param( 'rating', 1 );

		$response = $this->sut->update_item( $request );

		$this->assertNotInstanceOf( WP_Error::class, $response, 'Updating a review with a new rating should not produce a WP_Error.' );

		$refreshed = wc_get_product( $product->get_id() );
		$this->assertEquals( 1.0, (float) $refreshed->get_average_rating(), 'Updating a review rating via REST should refresh the product average rating.' );
		$this->assertEquals( array( 1 => 1 ), $refreshed->get_rating_counts(), 'Updating a review rating via REST should refresh the product rating distribution.' );
	}
}
