<?php
/**
 * Products REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * WC Tests API Products
 */
class WC_Admin_Tests_API_Products extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/products';

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
	 * Test product schema contains embed fields.
	 */
	public function test_product_schema() {
		wp_set_current_user( $this->user );
		$product    = WC_Helper_Product::create_simple_product();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc-analytics/products/' . $product->get_id() );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$properties_to_embed = array(
			'id',
			'name',
			'slug',
			'permalink',
			'images',
			'description',
			'short_description',
		);

		foreach ( $properties as $property_key => $property ) {
			if ( in_array( $property_key, $properties_to_embed, true ) ) {
				$this->assertEquals( array( 'view', 'edit', 'embed' ), $property['context'] );
			}
		}

		$this->assertArrayHasKey( 'last_order_date', $properties );

		$product->delete( true );
	}
}
