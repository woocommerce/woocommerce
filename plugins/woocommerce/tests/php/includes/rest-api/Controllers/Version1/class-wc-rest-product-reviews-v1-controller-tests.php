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
}
