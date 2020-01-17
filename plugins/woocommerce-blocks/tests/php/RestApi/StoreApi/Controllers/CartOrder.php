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
use \WC_Helper_Order as OrderHelper;
use \WC_Helper_Coupon as CouponHelper;
use Automattic\WooCommerce\Blocks\Tests\Helpers\ValidateSchema;

/**
 * Cart Order Controller Tests.
 */
class CartOrder extends TestCase {
	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		wp_set_current_user( 0 );

		$this->products = [];

		// Create some test products.
		$this->products[0] = ProductHelper::create_simple_product( false );
		$this->products[0]->set_weight( 10 );
		$this->products[0]->set_regular_price( 10 );
		$this->products[0]->save();

		$this->products[1] = ProductHelper::create_simple_product( false );
		$this->products[1]->set_weight( 10 );
		$this->products[1]->set_regular_price( 10 );
		$this->products[1]->save();

		wc_empty_cart();

		$this->keys   = [];
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id(), 1 );
	}

	/**
	 * Test route registration.
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/store/cart/order', $routes );

		$request  = new WP_REST_Request( 'GET', '/wc/store/cart/order' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test order creation from cart data.
	 */
	public function test_create_item() {
		$request = new WP_REST_Request( 'POST', '/wc/store/cart/order' );
		$request->set_param(
			'billing_address',
			[
				'first_name' => 'Margaret',
				'last_name'  => 'Thatchcroft',
				'address_1'  => '123 South Street',
				'address_2'  => 'Apt 1',
				'city'       => 'Philadelphia',
				'state'      => 'PA',
				'postcode'   => '19123',
				'country'    => 'US',
				'email'      => 'test@test.com',
				'phone'      => '',
			]
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 201, $response->get_status() );

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'number', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'order_key', $data );
		$this->assertArrayHasKey( 'created_via', $data );
		$this->assertArrayHasKey( 'prices_include_tax', $data );
		$this->assertArrayHasKey( 'events', $data );
		$this->assertArrayHasKey( 'customer', $data );
		$this->assertArrayHasKey( 'billing_address', $data );
		$this->assertArrayHasKey( 'shipping_address', $data );
		$this->assertArrayHasKey( 'customer_note', $data );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'totals', $data );

		$this->assertEquals( 'Margaret', $data['billing_address']->first_name );
		$this->assertEquals( 'Thatchcroft', $data['billing_address']->last_name );
		$this->assertEquals( '123 South Street', $data['billing_address']->address_1 );
		$this->assertEquals( 'Apt 1', $data['billing_address']->address_2 );
		$this->assertEquals( 'Philadelphia', $data['billing_address']->city );
		$this->assertEquals( 'PA', $data['billing_address']->state );
		$this->assertEquals( '19123', $data['billing_address']->postcode );
		$this->assertEquals( 'US', $data['billing_address']->country );
		$this->assertEquals( 'test@test.com', $data['billing_address']->email );
		$this->assertEquals( '', $data['billing_address']->phone );

		$this->assertEquals( 'checkout-draft', $data['status'] );
		$this->assertEquals( 2, count( $data['items'] ) );
	}

	/**
	 * Test schema retrieval.
	 */
	public function test_get_item_schema() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartOrder();
		$schema     = $controller->get_item_schema();

		$this->assertArrayHasKey( 'id', $schema['properties'] );
		$this->assertArrayHasKey( 'number', $schema['properties'] );
		$this->assertArrayHasKey( 'status', $schema['properties'] );
		$this->assertArrayHasKey( 'order_key', $schema['properties'] );
		$this->assertArrayHasKey( 'created_via', $schema['properties'] );
		$this->assertArrayHasKey( 'prices_include_tax', $schema['properties'] );
		$this->assertArrayHasKey( 'events', $schema['properties'] );
		$this->assertArrayHasKey( 'customer', $schema['properties'] );
		$this->assertArrayHasKey( 'billing_address', $schema['properties'] );
		$this->assertArrayHasKey( 'shipping_address', $schema['properties'] );
		$this->assertArrayHasKey( 'customer_note', $schema['properties'] );
		$this->assertArrayHasKey( 'items', $schema['properties'] );
		$this->assertArrayHasKey( 'totals', $schema['properties'] );
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item_for_response() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartOrder();
		$order      = OrderHelper::create_order();
		$response   = $controller->prepare_item_for_response( $order, [] );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'number', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'order_key', $data );
		$this->assertArrayHasKey( 'created_via', $data );
		$this->assertArrayHasKey( 'prices_include_tax', $data );
		$this->assertArrayHasKey( 'events', $data );
		$this->assertArrayHasKey( 'customer', $data );
		$this->assertArrayHasKey( 'billing_address', $data );
		$this->assertArrayHasKey( 'shipping_address', $data );
		$this->assertArrayHasKey( 'customer_note', $data );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'totals', $data );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_schema_matches_response() {
		$controller = new \Automattic\WooCommerce\Blocks\RestApi\StoreApi\Controllers\CartOrder();

		$order  = OrderHelper::create_order();
		$coupon = CouponHelper::create_coupon();
		$order->apply_coupon( $coupon );

		$response = $controller->prepare_item_for_response( $order, [] );
		$schema   = $controller->get_item_schema();
		$validate = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff, print_r( $diff, true ) );
	}
}
