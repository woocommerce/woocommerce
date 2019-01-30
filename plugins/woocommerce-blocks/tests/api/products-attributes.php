<?php
/**
 * @package WooCommerce\Tests\API
 */

/**
 * Product Controller "products by attributes" REST API Test
 *
 * @since 1.2.0
 */
class WC_Tests_API_Products_By_Attributes_Controller extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-pb/v3';

	/**
	 * Setup test products data. Called before every test.
	 *
	 * @since 1.2.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		// Create 2 product attributes with terms.
		$attr_color = WC_Helper_Product::create_attribute( 'color', array( 'red', 'yellow', 'blue' ) );
		$attr_size  = WC_Helper_Product::create_attribute( 'size', array( 'small', 'medium', 'large' ) );

		$red_term    = get_term_by( 'slug', 'red', $attr_color['attribute_taxonomy'] );
		$blue_term   = get_term_by( 'slug', 'blue', $attr_color['attribute_taxonomy'] );
		$yellow_term = get_term_by( 'slug', 'yellow', $attr_color['attribute_taxonomy'] );
		$small_term  = get_term_by( 'slug', 'small', $attr_size['attribute_taxonomy'] );
		$medium_term = get_term_by( 'slug', 'medium', $attr_size['attribute_taxonomy'] );
		$large_term  = get_term_by( 'slug', 'large', $attr_size['attribute_taxonomy'] );

		$this->attr_term_ids = array(
			'red'    => $red_term->term_id,
			'blue'   => $blue_term->term_id,
			'yellow' => $yellow_term->term_id,
			'small'  => $small_term->term_id,
			'medium' => $medium_term->term_id,
			'large'  => $large_term->term_id,
		);

		$color = new WC_Product_Attribute();
		$color->set_id( $attr_color['attribute_id'] );
		$color->set_name( $attr_color['attribute_taxonomy'] );
		$color->set_visible( true );

		$size = new WC_Product_Attribute();
		$size->set_id( $attr_size['attribute_id'] );
		$size->set_name( $attr_size['attribute_taxonomy'] );
		$size->set_visible( true );

		// Create some products with a mix of attributes:
		// - Product 1 – color: red, blue; size: medium.
		// - Product 2 – color: blue; size: large, medium.
		// - Product 3 – color: yellow.
		$this->products = array();
		$color->set_options( [ $this->attr_term_ids['red'], $this->attr_term_ids['blue'] ] );
		$size->set_options( [ $this->attr_term_ids['medium'] ] );
		$this->products[0] = WC_Helper_Product::create_simple_product( false );
		$this->products[0]->set_attributes( [ $color, $size ] );
		$this->products[0]->save();

		$color->set_options( [ $this->attr_term_ids['blue'] ] );
		$size->set_options( [ $this->attr_term_ids['medium'], $this->attr_term_ids['large'] ] );
		$this->products[1] = WC_Helper_Product::create_simple_product( false );
		$this->products[1]->set_attributes( [ $color, $size ] );
		$this->products[1]->save();

		$color->set_options( [ $this->attr_term_ids['yellow'] ] );
		$this->products[2] = WC_Helper_Product::create_simple_product( false );
		$this->products[2]->set_attributes( [ $color ] );
		$this->products[2]->save();
	}

	/**
	 * Test getting products by a single attribute term.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products' );
		$request->set_param( 'attribute', 'pa_color' );
		$request->set_param( 'attribute_term', (string) $this->attr_term_ids['red'] );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_products ) );
	}

	/**
	 * Test getting products by multiple terms in one attribute.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_by_multiple_terms() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products' );
		$request->set_param( 'attribute', 'pa_color' );
		$request->set_param(
			'attribute_term',
			// Terms list needs to be a string.
			$this->attr_term_ids['red'] . ',' . $this->attr_term_ids['yellow']
		);

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $response_products ) );
	}

	/**
	 * Test getting products by multiple terms in one attribute, matching all.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_by_multiple_terms_all() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products' );
		$request->set_param(
			'attributes',
			array(
				'pa_color' => array(
					$this->attr_term_ids['red'],
					$this->attr_term_ids['blue'],
				),
			)
		);
		$request->set_param( 'attr_operator', 'AND' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $response_products ) );
	}

	/**
	 * Test getting products by multiple terms in multiple attributes, matching any.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_by_multiple_terms_multiple_attrs_any() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products' );
		$request->set_param(
			'attributes',
			array(
				'pa_color' => array( $this->attr_term_ids['red'] ),
				'pa_size'  => array( $this->attr_term_ids['large'] ),
			)
		);
		$request->set_param( 'attr_operator', 'IN' );
		$request->set_param( 'tax_relation', 'OR' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $response_products ) );
	}

	/**
	 * Test getting products by multiple terms in multiple attributes, matching all.
	 *
	 * @since 1.2.0
	 */
	public function test_get_products_by_multiple_terms_multiple_attrs_all() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products' );
		$request->set_param(
			'attributes',
			array(
				'pa_color' => array( $this->attr_term_ids['blue'] ),
				'pa_size'  => array( $this->attr_term_ids['medium'] ),
			)
		);
		$request->set_param( 'attr_operator', 'AND' );
		$request->set_param( 'tax_relation', 'AND' );

		$response          = $this->server->dispatch( $request );
		$response_products = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $response_products ) );
	}

	/**
	 * Test getting products by attributes that don't exist.
	 *
	 * Note: This test is currently skipped because the API isn't registering the attribute
	 * properties correctly, and therefor not validating attribute names against "real" attributes.
	 *
	 * @since 1.2.0
	 */
	public function xtest_get_products_by_fake_attrs() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products' );
		$request->set_param( 'attributes', array( 'pa_fake' => array( 21 ) ) );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}
}
