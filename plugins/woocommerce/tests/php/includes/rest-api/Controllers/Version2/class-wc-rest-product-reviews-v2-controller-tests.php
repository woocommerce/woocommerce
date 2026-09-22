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

	public function setUp(): void {
		parent::setUp();

		$this->sut             = new WC_REST_Product_Reviews_V2_Controller();
		$this->shop_manager_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->editor_id       = self::factory()->user->create( array( 'role' => 'editor' ) );
	}

	/**
	 * @testdox Ensure attempts to modify product reviews (via batches) are subject to appropriate permission checks.
	 */
	public function test_permissions_for_batch_product_reviews() {
		$request = new WP_REST_Request( 'POST', '/wc/v2/products/123/reviews/batch' );

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
<<<<<<< HEAD
=======

	/**
	 * @testdox The wc/v2 route accepts rating-only updates and keeps zero as a no-op.
	 */
	public function test_wc_v2_route_handles_rating_only_updates() {
		wp_set_current_user( $this->shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$create = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product_id . '/reviews' );
		$create->set_body_params(
			array(
				'review' => 'A v2 review.',
				'name'   => 'Jane Smith',
				'email'  => 'jane.smith@example.org',
				'rating' => 5,
			)
		);
		$created = $this->server->dispatch( $create );

		$this->assertSame( 201, $created->get_status() );
		$review_id = $created->get_data()['id'];

		$update = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product_id . '/reviews/' . $review_id );
		$update->set_body_params( array( 'rating' => 3 ) );
		$updated = $this->server->dispatch( $update );

		$this->assertSame( 200, $updated->get_status() );
		$this->assertSame( 3, (int) get_comment_meta( $review_id, 'rating', true ) );
		$this->assertEquals( 3, wc_get_product( $product_id )->get_average_rating() );

		$zero = new WP_REST_Request( 'PUT', '/wc/v2/products/' . $product_id . '/reviews/' . $review_id );
		$zero->set_body_params( array( 'rating' => 0 ) );
		$zero_response = $this->server->dispatch( $zero );

		$this->assertSame( 200, $zero_response->get_status() );
		$this->assertSame( 3, (int) get_comment_meta( $review_id, 'rating', true ) );
	}

	/**
	 * @testdox Creating a review as a shop manager strips markup that user role cannot post.
	 */
	public function test_create_item_strips_disallowed_markup_for_shop_manager() {
		$shop_manager_id = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $shop_manager_id );
		$product_id = ProductHelper::create_simple_product()->get_id();

		$create = new WP_REST_Request( 'POST', '/wc/v2/products/' . $product_id . '/reviews' );
		$create->set_body_params(
			array(
				'review' => '<a href="https://example.test" data-wp-bind--href="state.url" style="position:fixed;inset:0">Nice</a><strong>Good</strong><script>alert(1)</script>',
				'name'   => 'Jane Smith',
				'email'  => 'jane.smith@example.org',
				'rating' => 5,
			)
		);
		$created = $this->server->dispatch( $create );

		$this->assertSame( 201, $created->get_status() );

		$comment = get_comment( $created->get_data()['id'] );

		$this->assertStringContainsString( '<strong>Good</strong>', $comment->comment_content );
		$this->assertStringContainsString( 'href="https://example.test"', $comment->comment_content );
		$this->assertStringNotContainsString( 'data-wp-bind', $comment->comment_content );
		$this->assertStringNotContainsString( 'style=', $comment->comment_content );
		$this->assertStringNotContainsString( '<script', $comment->comment_content );
	}
>>>>>>> 70e85454c4 (Cherry pick 496 (#68954))
}
