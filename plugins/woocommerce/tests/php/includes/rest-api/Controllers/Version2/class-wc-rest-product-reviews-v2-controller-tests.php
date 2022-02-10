<?php

/**
 * Tests relating to the Product Reviews controller in APIv2.
 */
class WC_REST_Product_Reviews_V2_Controller_Test extends WC_REST_Unit_Test_case {
	/**
	 * @var WC_REST_Product_Reviews_V2_Controller
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

	public function setUp() {
		parent::setUp();
		$password = wp_generate_password();

		$this->shop_manager_id = wp_insert_user(
			array(
				'user_login' => "test_shopman_$password",
				'user_pass'  => $password,
				'user_email' => "shopman_$password@example.com",
				'role'       => 'shop_manager',
			)
		);

		$this->editor_id = wp_insert_user(
			array(
				'user_login' => "test_editor_$password",
				'user_pass'  => $password,
				'user_email' => "editor_$password@example.com",
				'role'       => 'editor',
			)
		);

		$this->sut = new WC_REST_Product_Reviews_V2_Controller();
	}

	/**
	 * @testdox Ensure attempts to modify product reviews (via batches) are subject to appropriate permission checks.
	 */
	public function test_permissions_for_deleting_product_reviews() {
		$request = new WP_REST_Request( 'POST', '/wc/v2/products/123/reviews/batch' );
		$request->set_param( 'id', $this->review_id );

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
}
