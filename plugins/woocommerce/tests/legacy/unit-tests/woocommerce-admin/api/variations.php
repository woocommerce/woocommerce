<?php
/**
 * Variations REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC Tests API Variations
 */
class WC_Admin_Tests_API_Variations extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/variations';

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
	 * Test variation search works for global and local product attributes.
	 */
	public function test_variation_search() {
		wp_set_current_user( $this->user );

		// Create a variable product, then create a variation using "global" product attributes.
		$product    = WC_Helper_Product::create_variation_product();
		$data_store = $product->get_data_store();
		$data_store->create_all_product_variations( $product, 1 );
		$child_product_ids = $product->get_children();
		$variation_1       = wc_get_product( $child_product_ids[0] );

		// Create a variation, using "local" attribute key/value pairs.
		// NOTE: WC_Helper_Product::create_product_variation_object() is only available for WC 4.4+.
		$variation_2 = new WC_Product_Variation();
		$variation_2->set_props(
			array(
				'parent_id'     => $product->get_id(),
				'regular_price' => 23,
			)
		);
		$variation_2->set_attributes( array( 'flavor' => 'banana' ) );
		$variation_2->save();

		// Test searching for the "global" size attribute.
		$request = new WP_REST_Request( 'GET', "/wc-analytics/products/{$product->get_id()}/variations" );
		$request->set_param( 'search', 'small' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation_1->get_id(), $variations[0]['id'] );

		// Test searching for the "local" flavor attribute.
		$request = new WP_REST_Request( 'GET', "/wc-analytics/products/{$product->get_id()}/variations" );
		$request->set_param( 'search', 'banana' );
		$response   = $this->server->dispatch( $request );
		$variations = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $variations ) );
		$this->assertEquals( $variation_2->get_id(), $variations[0]['id'] );
	}
}
