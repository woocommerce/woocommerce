<?php
declare(strict_types=1);


namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Products;

use Automattic\WooCommerce\Internal\RestApi\Routes\V4\Products\Controller as ProductsController;
use WC_Helper_Product;
use WC_REST_Unit_Test_Case;
use WP_REST_Request;

/**
 * ProductsAddToCartTest.
 * This class is responsible for testing the add to cart field functionality of the V4 REST API Products endpoint.
 */
class ProductsAddToCartTest extends WC_REST_Unit_Test_Case {

	/**
	 * The shared admin user used for REST authentication across all tests in this class.
	 *
	 * @var int
	 */
	protected static $user;

	/**
	 * Create the shared admin user once for the whole class.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp(): void {
		// Enable the REST API v4 feature.
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features[] = 'rest-api-v4';
				return $features;
			},
		);
		parent::setUp();
		$this->endpoint = new ProductsController();
		wp_set_current_user( self::$user );
	}

	/**
	 * Runs after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Disable the REST API v4 feature.
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				$features = array_diff( $features, array( 'rest-api-v4' ) );
				return $features;
			}
		);
	}

	/**
	 * Test that add_to_cart data is correctly populated in product responses.
	 *
	 * @param string $product_type The type of product to test.
	 * @testWith ["simple"]
	 *           ["grouped"]
	 *           ["external"]
	 *           ["variable"]
	 */
	public function test_add_to_cart_response_data( string $product_type ) {
		switch ( $product_type ) {
			case 'simple':
				$product                   = WC_Helper_Product::create_simple_product();
				$expected_add_to_cart_text = __( 'Add to cart', 'woocommerce' );
				break;
			case 'grouped':
				$product                   = new \WC_Product_Grouped();
				$expected_add_to_cart_text = __( 'View products', 'woocommerce' );
				$product->set_name( 'Dummy Grouped Product' );
				$product->save();
				break;
			case 'external':
				$product                   = WC_Helper_Product::create_external_product();
				$expected_add_to_cart_text = 'Buy external product';
				break;
			case 'variable':
				$product   = new \WC_Product_Variable();
				$attribute = new \WC_Product_Attribute();
				$attribute->set_name( 'size' );
				$attribute->set_options( array( 'small' ) );
				$attribute->set_visible( true );
				$attribute->set_variation( true );
				$product->set_name( 'Dummy Variable Product' );
				$product->set_attributes( array( $attribute ) );
				$product->save();

				$variation = new \WC_Product_Variation();
				$variation->set_parent_id( $product->get_id() );
				$variation->set_regular_price( 10 );
				$variation->set_status( 'publish' );
				$variation->set_attributes( array( 'size' => 'small' ) );
				$variation->save();

				\WC_Product_Variable::sync( $product->get_id() );
				$product = wc_get_product( $product->get_id() );
				$this->assertNotEmpty( $product->get_children(), 'Variable fixture should expose its child.' );
				$this->assertNotSame( '', $product->get_price(), 'Variable fixture should have a synced price.' );
				$this->assertTrue( $product->is_purchasable(), 'Variable fixture should be purchasable.' );
				$expected_add_to_cart_text = __( 'Select options', 'woocommerce' );
				break;
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v4/products/' . $product->get_id() ) );
		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();

		$this->assertSame( $product_type, $data['type'] );
		$this->assertArrayHasKey( 'add_to_cart', $data );
		$add_to_cart = $data['add_to_cart'];

		$required_properties = array( 'url', 'description', 'text', 'single_text' );
		foreach ( $required_properties as $property ) {
			$this->assertArrayHasKey( $property, $add_to_cart, "Property '{$property}' should exist in add_to_cart response" );
			$this->assertIsString( $add_to_cart[ $property ], "Property '{$property}' should be a string" );
		}

		$this->assertEquals( $product->add_to_cart_url(), $add_to_cart['url'] );
		$this->assertEquals( $product->add_to_cart_description(), $add_to_cart['description'] );
		$this->assertSame( $expected_add_to_cart_text, $add_to_cart['text'] );
		$this->assertEquals( $product->single_add_to_cart_text(), $add_to_cart['single_text'] );

		switch ( $product_type ) {
			case 'external':
				$this->assertNotEmpty( $add_to_cart['url'], 'External products should have a non-empty add_to_cart URL' );
				break;
			case 'variable':
				$this->assertNotEmpty( $add_to_cart['text'], 'Variable products should have add_to_cart text' );
				break;
		}
	}

	/**
	 * Test that add_to_cart field is included when using _fields parameter.
	 */
	public function test_add_to_cart_with_fields_parameter() {
		$product = WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'GET', '/wc/v4/products/' . $product->get_id() );
		$request->set_param( '_fields', 'id,name,add_to_cart' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'add_to_cart', $data );

		$add_to_cart         = $data['add_to_cart'];
		$expected_properties = array( 'url', 'description', 'text', 'single_text' );
		foreach ( $expected_properties as $property ) {
			$this->assertArrayHasKey( $property, $add_to_cart );
		}
	}

	/**
	 * Test that add_to_cart field is present in product collection responses.
	 */
	public function test_add_to_cart_in_collection_response() {
		$product = WC_Helper_Product::create_simple_product();

		$request = new WP_REST_Request( 'GET', '/wc/v4/products' );
		$request->set_param( 'include', array( $product->get_id() ) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertCount( 1, $data );
		$product_data = $data[0];

		$this->assertArrayHasKey( 'add_to_cart', $product_data );

		$add_to_cart         = $product_data['add_to_cart'];
		$expected_properties = array( 'url', 'description', 'text', 'single_text' );
		foreach ( $expected_properties as $property ) {
			$this->assertArrayHasKey( $property, $add_to_cart );
			$this->assertIsString( $add_to_cart[ $property ] );
		}
	}
}
