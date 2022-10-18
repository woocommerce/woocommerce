<?php
/**
 * Product Reviews REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC Tests API Product Reviews
 */
class WC_Admin_Tests_API_Product_Reviews extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/products/reviews';

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test product reviews shows product field as embeddable.
	 */
	public function test_product_review_embed() {
		wp_set_current_user( $this->user );
		$product = WC_Helper_Product::create_simple_product();
		WC_Helper_Product::create_product_review( $product->get_id() );

		$request = new WP_REST_Request( 'GET', '/wc-analytics/products/reviews' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertTrue( $data[0]['_links']['up'][0]['embeddable'] );

		$product->delete( true );
	}
}
