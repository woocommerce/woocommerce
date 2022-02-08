<?php

/**
 * Tests relating to WC_REST_Product_Reviews_V1_Controller.
 */
class WC_REST_Product_Attributes_V1_Controller_Tests extends WC_Unit_Test_Case {
	/**
	 * @var int
	 */
	private static $customer_id;

	/**
	 * @var int
	 */
	private static $editor_id;

	/**
	 * @var int
	 */
	private static $shop_manager_id;

	/**
	 * @var int
	 */
	private static $product_id;

	/**
	 * @var int
	 */
	private static $review_id;

	/**
	 * @var WC_REST_Product_Reviews_V1_Controller
	 */
	private static $sut;

	/**
	 * Creates test users with varying permissions.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		$password = wp_generate_password();

		self::$shop_manager_id = wp_insert_user(
			array(
				'user_login' => "test_shopmanr_$password",
				'user_pass'  => $password,
				'user_email' => "shopman_$password@example.com",
				'role'       => 'shop_manager',
			)
		);

		self::$editor_id = wp_insert_user(
			array(
				'user_login' => "test_editor_$password",
				'user_pass'  => $password,
				'user_email' => "editor_$password@example.com",
				'role'       => 'editor',
			)
		);

		self::$customer_id = wp_insert_user(
			array(
				'user_login' => "test_customer_$password",
				'user_pass'  => $password,
				'user_email' => "customer_$password@example.com",
				'role'       => 'customer',
			)
		);

		self::$product_id = WC_Helper_Product::create_simple_product()->get_id();
		self::$review_id  = WC_Helper_Product::create_product_review(
			self::$product_id,
			'Supposed to be made from real unicorn horn but was actually cheap cardboard. OK for the price.'
		);

		self::$sut = new WC_REST_Product_Reviews_V1_Controller();
	}

	public function test_permissions_for_reading_product_reviews() {
		$api_request = new WP_REST_Request( 'GET', '/wc/v2/products/' . self::$product_id . '/reviews/' );

		wp_set_current_user( self::$customer_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_view',
			self::$sut->get_items_permissions_check( $api_request )->get_error_code(),
			'A user (such as a customer) lacking the moderate_comments capability cannot list reviews.'
		);

		wp_set_current_user( self::$editor_id );
		$this->assertTrue(
			self::$sut->get_items_permissions_check( $api_request ),
			'A user (such as an editor) who has the moderate_comments capability can list reviews.'
		);
	}
	/**
	 * @testdox Ensure attempts to create product reviews are checked for user permissions.
	 */
	public function test_permissions_for_updating_product_reviews() {
		$api_request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . self::$product_id . '/reviews/' . self::$review_id );
		$api_request->set_param( 'id', self::$review_id );
		$api_request->set_body( '{ "review": "Modified automatically." }' );

		wp_set_current_user( self::$editor_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_edit',
			self::$sut->update_item_permissions_check( $api_request )->get_error_code(),
			'A user (such as an editor) lacking edit_comment permissions cannot update a product review.'
		);

		wp_set_current_user( self::$shop_manager_id );
		$this->assertTrue(
			self::$sut->update_item_permissions_check( $api_request ),
			'A user (such as a shop manager) who has edit_comment permissions can update a product review.'
		);

		$nonexistent_product_id = self::$product_id * 10;
		$api_request->set_route( "/wc/v2/products/{$nonexistent_product_id}/reviews/" . self::$review_id );
		$this->assertEquals(
			'woocommerce_rest_product_invalid_id',
			self::$sut->update_item( $api_request )->get_error_code(),
			'Attempts to edit reviews for non-existent products are rejected.'
		);
	}

	/**
	 * @testdox Ensure attempts to delete product reviews are checked for user permissions.
	 */
	public function test_permissions_for_deleting_product_reviews() {
		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/123456789/reviews/' . self::$review_id );
		$request->set_param( 'id', self::$review_id );

		wp_set_current_user( self::$editor_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_delete',
			self::$sut->delete_item_permissions_check( $request )->get_error_code(),
			'A user lacking edit_comment permissions (such as an editor) cannot delete a product review.'
		);

		wp_set_current_user( self::$shop_manager_id );
		$this->assertTrue(
			self::$sut->delete_item_permissions_check( $request ),
			'A user (such as a shop manager) who has the edit_comment permission can delete a product review.'
		);

		$order         = WC_Helper_Order::create_order();
		$order_note_id = $order->add_order_note( 'Dispatched with all due haste.' );

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/123456789/reviews/' . $order_note_id );
		$request->set_param( 'id', $order_note_id );

		$this->assertEquals(
			'woocommerce_rest_cannot_delete',
			self::$sut->delete_item_permissions_check( $request )->get_error_code(),
			'Comments that are not product reviews cannot be deleted via this endpoint.'
		);
	}
}

