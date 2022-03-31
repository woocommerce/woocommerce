<?php
/**
 * Product Attributes REST API Test
 *
 * @package WooCommerce\Admin\Tests\API
 */

/**
 * Class WC_Admin_Tests_API_Product_Attributes
 */
class WC_Admin_Tests_API_Product_Attributes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-analytics/products/attributes';

	/**
	 * Setup test user.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies" );
		$wpdb->query('commit');
	}

	/**
	 * Setup test product attributes data.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Use the test helper to populate some global attributes.
		$product    = \WC_Helper_Product::create_variation_product();
		$attributes = $product->get_attributes();

		// Add a custom attribute.
		$custom_attr = new WC_Product_Attribute();
		$custom_attr->set_name( 'Numeric Size' );
		$custom_attr->set_options( array( '1', '2', '3', '4', '5' ) );
		$custom_attr->set_visible( true );
		$custom_attr->set_variation( true );
		$attributes[] = $custom_attr;

		$product->set_attributes( $attributes );
		$product->save();

		// Assign one variation to the '1' size.
		$variation  = $product->get_available_variations( 'objects' )[0];
		$attributes = $variation->get_attributes();
		$attributes[ sanitize_title( $custom_attr->get_name() ) ] = '1';
		$variation->set_attributes( $attributes );
		$variation->save();

		// Add more custom Numeric Size values to another product.
		$product_2 = new WC_Product_Variable();
		$product_2->set_props(
			array(
				'name' => 'Dummy Variable Product 2',
				'sku'  => 'DUMMY VARIABLE SKU 2',
			)
		);

		$custom_attr_2 = new WC_Product_Attribute();
		$custom_attr_2->set_name( 'Numeric Size' );
		$custom_attr_2->set_options( array( '6', '7', '8', '9', '10' ) );
		$custom_attr_2->set_visible( true );
		$custom_attr_2->set_variation( true );

		$product_2->set_attributes(
			array(
				$custom_attr_2,
			)
		);
		$product_2->save();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test request without valid permissions.
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test schema.
	 */
	public function test_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 6, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertEquals( array( 'integer', 'string' ), $properties['id']['type'] );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'order_by', $properties );
		$this->assertArrayHasKey( 'has_archives', $properties );
	}

	/**
	 * Test our passthrough case to the wc/v3 endpoint.
	 */
	public function test_without_search() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'GET', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$attributes = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $attributes ) );

		// Ensure our custom attribute is not in the results (proof of core endpoint).
		$names = wp_list_pluck( $attributes, 'name' );
		$this->assertNotContains( 'Numeric Size', $names );
	}

	/**
	 * Test our search functionality.
	 */
	public function test_with_search() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params(
			array(
				'search' => 'num',
			)
		);
		$response   = $this->server->dispatch( $request );
		$attributes = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $attributes ) );

		// Results should include "number" and "Numeric Size".
		$names = wp_list_pluck( $attributes, 'name' );
		$this->assertContains( 'number', $names );
		$this->assertContains( 'Numeric Size', $names );
	}

	/**
	 * Test getting a single attribute by slug.
	 */
	public function test_by_slug() {
		wp_set_current_user( $this->user );

		$request   = new WP_REST_Request( 'GET', $this->endpoint . '/numeric-size' );
		$response  = $this->server->dispatch( $request );
		$attribute = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'numeric-size', $attribute['id'] );
		$this->assertEquals( 'numeric-size', $attribute['slug'] );
		$this->assertEquals( 'Numeric Size', $attribute['name'] );
	}

	/**
	 * Test not finding a single attribute by slug.
	 */
	public function test_by_slug_404() {
		wp_set_current_user( $this->user );

		$request   = new WP_REST_Request( 'GET', $this->endpoint . '/not-a-real-slug' );
		$response  = $this->server->dispatch( $request );
		$attribute = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test terms schema.
	 */
	public function test_terms_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint . '/numeric-size/terms' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 6, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
		$this->assertEquals( array( 'integer', 'string' ), $properties['id']['type'] );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'slug', $properties );
		$this->assertArrayHasKey( 'description', $properties );
		$this->assertArrayHasKey( 'menu_order', $properties );
		$this->assertArrayHasKey( 'count', $properties );
	}

	/**
	 * Test our passthrough case to the wc/v3 terms endpoint.
	 */
	public function test_taxonomy_backed_terms() {
		wp_set_current_user( $this->user );

		$global_attrs = wc_get_attribute_taxonomies();

		// Grab the size attribute.
		$size_attr_id = false;
		foreach ( $global_attrs as $global_attr ) {
			if ( 'size' === $global_attr->attribute_name ) {
				$size_attr_id = $global_attr->attribute_id;
				break;
			}
		}

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/' . $size_attr_id . '/terms' );
		$response = $this->server->dispatch( $request );
		$terms    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 3, count( $terms ) );

		$slugs = wp_list_pluck( $terms, 'slug' );
		$this->assertContains( 'small', $slugs );
		$this->assertContains( 'large', $slugs );
		$this->assertContains( 'huge', $slugs );
	}

	/**
	 * Test that our endpoint supports custom attributes.
	 */
	public function test_custom_attribute_terms() {
		wp_set_current_user( $this->user );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/numeric-size/terms' );
		$response = $this->server->dispatch( $request );
		$terms    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, count( $terms ) );
		$this->assertEquals( '1', $terms[0]['slug'] );
		$this->assertEquals( 1, $terms[0]['count'] );
		$this->assertEquals( 0, $terms[1]['count'] );
	}
}
