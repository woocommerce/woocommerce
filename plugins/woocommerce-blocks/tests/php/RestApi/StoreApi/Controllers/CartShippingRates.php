<?php
/**
 * Controller Tests.
 *
 * @package WooCommerce\Blocks\Tests
 */

namespace Automattic\WooCommerce\Blocks\Tests\RestApi\StoreApi\Controllers;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case as TestCase;
use \WC_Helper_Product as ProductHelper;
use \WC_Helper_Shipping;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Cart Shipping Rates Controller Tests.
 */
class CartShippingRates extends TestCase {
	/**
	 * Setup test products data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		$this->products = [];

		// Create some test products.
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->save();

		$this->products[1] = ProductHelper::create_simple_product( false );
		$this->products[1]->save();

		wc_empty_cart();
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );

		WC_Helper_Shipping::create_simple_flat_rate();
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart/shipping-rates', $routes );
	}

	/**
	 * Test getting shipping.
	 */
	public function test_get_items() {
		$request = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'country', 'US' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $data ) );

		$this->assertArrayHasKey( 'destination', $data[0] );
		$this->assertArrayHasKey( 'items', $data[0] );
		$this->assertArrayHasKey( 'shipping_rates', $data[0] );

		$this->assertEquals( null, $data[0]['destination']->address_1 );
		$this->assertEquals( null, $data[0]['destination']->address_2 );
		$this->assertEquals( null, $data[0]['destination']->city );
		$this->assertEquals( null, $data[0]['destination']->state );
		$this->assertEquals( null, $data[0]['destination']->postcode );
		$this->assertEquals( 'US', $data[0]['destination']->country );
	}

	/**
	 * Test getting shipping.
	 */
	public function test_get_items_address_validation() {
		// US address.
		$request = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'address_1', 'Test address 1' );
		$request->set_param( 'address_2', 'Test address 2' );
		$request->set_param( 'city', 'Test City' );
		$request->set_param( 'state', 'AL' );
		$request->set_param( 'postcode', '90210' );
		$request->set_param( 'country', 'US' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'Test address 1', $data[0]['destination']->address_1 );
		$this->assertEquals( 'Test address 2', $data[0]['destination']->address_2 );
		$this->assertEquals( 'Test City', $data[0]['destination']->city );
		$this->assertEquals( 'AL', $data[0]['destination']->state );
		$this->assertEquals( '90210', $data[0]['destination']->postcode );
		$this->assertEquals( 'US', $data[0]['destination']->country );

		// Address with empty country.
		$request = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'country', '' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// Address with invalid country.
		$request = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'country', 'ZZZZZZZZ' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// US address with named state.
		$request = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'state', 'Alabama' );
		$request->set_param( 'country', 'US' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'AL', $data[0]['destination']->state );
		$this->assertEquals( 'US', $data[0]['destination']->country );

		// US address with invalid state.
		$request = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'state', 'ZZZZZZZZ' );
		$request->set_param( 'country', 'US' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );

		// US address with named state.
		$request = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'state', 'Alabama' );
		$request->set_param( 'country', 'US' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'AL', $data[0]['destination']->state );
		$this->assertEquals( 'US', $data[0]['destination']->country );
	}

	/**
	 * Test schema retrieval.
	 */
	public function test_get_item_schema() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartShippingRates();
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'destination', $schema['properties'] );
		$this->assertArrayHasKey( 'items', $schema['properties'] );
		$this->assertArrayHasKey( 'shipping_rates', $schema['properties'] );
		$this->assertArrayHasKey( 'name', $schema['properties']['shipping_rates']['items']['properties'] );
		$this->assertArrayHasKey( 'description', $schema['properties']['shipping_rates']['items']['properties'] );
		$this->assertArrayHasKey( 'delivery_time', $schema['properties']['shipping_rates']['items']['properties'] );
		$this->assertArrayHasKey( 'price', $schema['properties']['shipping_rates']['items']['properties'] );
		$this->assertArrayHasKey( 'rate_id', $schema['properties']['shipping_rates']['items']['properties'] );
		$this->assertArrayHasKey( 'instance_id', $schema['properties']['shipping_rates']['items']['properties'] );
		$this->assertArrayHasKey( 'method_id', $schema['properties']['shipping_rates']['items']['properties'] );
		$this->assertArrayHasKey( 'meta_data', $schema['properties']['shipping_rates']['items']['properties'] );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item_for_response() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartShippingRates();
		$packages   = wc()->shipping->calculate_shipping( wc()->cart->get_shipping_packages() );
		$response   = $controller->prepare_item_for_response( current( $packages ), [] );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'destination', $data );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'shipping_rates', $data );
	}

	/**
	 * Test collection params getter.
	 */
	public function test_get_collection_params() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartShippingRates();
		$params     = $controller->get_collection_params();

		$this->assertArrayHasKey( 'address_1', $params );
		$this->assertArrayHasKey( 'address_2', $params );
		$this->assertArrayHasKey( 'city', $params );
		$this->assertArrayHasKey( 'state', $params );
		$this->assertArrayHasKey( 'country', $params );
		$this->assertArrayHasKey( 'postcode', $params );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_schema_matches_response() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartShippingRates();
		$request    = new WP_REST_Request( 'GET', '/wc/store/cart/shipping-rates' );
		$request->set_param( 'address_1', 'Test address 1' );
		$request->set_param( 'address_2', 'Test address 2' );
		$request->set_param( 'city', 'Test City' );
		$request->set_param( 'state', 'AL' );
		$request->set_param( 'postcode', '90210' );
		$request->set_param( 'country', 'US' );
		$response = $this->server->dispatch( $request );
		$schema   = $controller->get_item_schema();
		$validate = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( current( $response->get_data() ) );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
