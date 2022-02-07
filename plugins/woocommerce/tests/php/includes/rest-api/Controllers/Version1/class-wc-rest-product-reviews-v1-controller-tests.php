<?php

/**
 * Tests relating to WC_REST_Product_Reviews_V1_Controller.
 */
class WC_REST_Product_Attributes_V1_Controller_Tests extends WC_Unit_Test_Case {
	/**
	 * @var int
	 */
	private static $editor_id;

	/**
	 * @var int
	 */
	private static $author_id;

	/**
	 * @var WC_REST_Product_Reviews_V1_Controller
	 */
	private static $sut;

	/**
	 * Creates test users with varying permissions.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$password        = wp_generate_password();
		self::$editor_id = wp_insert_user(
			array(
				'user_login' => "test_editor_$password",
				'user_pass'  => $password,
				'user_email' => "editor_$password@example.com",
				'role'       => 'editor',
			)
		);
		self::$author_id = wp_insert_user(
			array(
				'user_login' => "test_author_$password",
				'user_pass'  => $password,
				'user_email' => "author_$password@example.com",
				'role'       => 'author',
			)
		);

		self::$sut = new WC_REST_Product_Reviews_V1_Controller();
	}

	/**
	 * @testdox Ensure attempts to create product reviews are checked for user permissions.
	 */
	public function test_permissions_for_updating_product_reviews() {
		$product   = WC_Helper_Product::create_simple_product();
		$review_id = WC_Helper_Product::create_product_review(
			$product->get_id(),
			'Arrived on schedule but I had to turn it on manually.'
		);

		$request = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product->get_id() . '/reviews/' . $review_id );
		$request->set_param( 'id', $review_id );
		$request->set_body( '{ "review": "Modified automatically." }' );

		wp_set_current_user( self::$author_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_edit',
			self::$sut->update_item_permissions_check( $request )->get_error_code(),
			'An author cannot update a product review.'
		);

		wp_set_current_user( self::$editor_id );
		$this->assertTrue(
			self::$sut->update_item_permissions_check( $request ),
			'An editor (or any user with the moderate_comments capability) can update a product review.'
		);

		$request->set_route( '/wc/v2/products/' . ( $product->get_id() * 10 ) . '/reviews/' . $review_id );
		$this->assertEquals(
			'woocommerce_rest_product_invalid_id',
			self::$sut->update_item( $request )->get_error_code(),
			'Attempts to edit reviews for non-existent products are rejected.'
		);
	}

	/**
	 * @testdox Ensure attempts to delete product reviews are checked for user permissions.
	 */
	public function test_permissions_for_deleting_product_reviews() {
		$product   = WC_Helper_Product::create_simple_product();
		$review_id = WC_Helper_Product::create_product_review(
			$product->get_id(),
			'Supposed to be made from real unicorn horn but was actually cheap cardboard. OK for the price.'
		);

		$request = new WP_REST_Request( 'DELETE', '/wc/v2/products/123456789/reviews/' . $review_id );
		$request->set_param( 'id', $review_id );

		wp_set_current_user( self::$author_id );
		$this->assertEquals(
			'woocommerce_rest_cannot_delete',
			self::$sut->delete_item_permissions_check( $request )->get_error_code(),
			'An author (or other user lacking the moderate_comments capability) cannot delete a product review.'
		);

		wp_set_current_user( self::$editor_id );
		$this->assertTrue(
			self::$sut->delete_item_permissions_check( $request ),
			'An editor (or any user with the moderate_comments capability) can delete a product review.'
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

