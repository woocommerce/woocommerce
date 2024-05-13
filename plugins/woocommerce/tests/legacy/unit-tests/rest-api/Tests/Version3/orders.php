<?php
/**
 * Tests for the orders REST API.
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

// phpcs:ignore Squiz.Commenting.FileComment.Missing
require_once __DIR__ . '/date-filtering.php';

use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Class WC_Tests_API_Orders
 */
class WC_Tests_API_Orders extends WC_REST_Unit_Test_Case {
	use WC_REST_API_Complex_Meta;
	use DateFilteringForCrudControllers;

	/**
	 * Array of order to track
	 * @var array
	 */
	protected $orders = array();

	/**
	 * Setup our test server.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->endpoint = new WC_REST_Orders_Controller();
		$this->user     = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test route registration.
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();
		$this->assertArrayHasKey( '/wc/v3/orders', $routes );
		$this->assertArrayHasKey( '/wc/v3/orders/batch', $routes );
		$this->assertArrayHasKey( '/wc/v3/orders/(?P<id>[\d]+)', $routes );
	}

	/**
	 * Test getting all orders.
	 * @since 3.5.0
	 */
	public function test_get_items() {
		wp_set_current_user( $this->user );

		// Create 10 orders.
		for ( $i = 0; $i < 10; $i++ ) {
			$this->orders[] = OrderHelper::create_order( $this->user );
		}

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders' ) );
		$orders   = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 10, count( $orders ) );
	}

	/**
	 * Test getting all orders sorted by modified date.
	 */
	public function test_get_items_ordered_by_modified() {
		wp_set_current_user( $this->user );

		$order1 = OrderHelper::create_order( $this->user );
		$order2 = OrderHelper::create_order( $this->user );

		$order1->set_status( 'completed' );
		$order1->save();
		sleep( 1 );
		$order2->set_status( 'completed' );
		$order2->save();

		$request = new WP_REST_Request( 'GET', '/wc/v3/orders' );
		$request->set_query_params(
			array(
				'orderby' => 'modified',
				'order'   => 'asc',
			)
		);
		$response = $this->server->dispatch( $request );
		$orders   = $response->get_data();
		$this->assertEquals( $order1->get_id(), $orders[0]['id'] );

		$request->set_query_params(
			array(
				'orderby' => 'modified',
				'order'   => 'desc',
			)
		);
		$response = $this->server->dispatch( $request );
		$orders   = $response->get_data();
		$this->assertEquals( $order2->get_id(), $orders[0]['id'] );
	}

	/**
	 * Tests to make sure orders cannot be viewed without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_items_without_permission() {
		wp_set_current_user( 0 );
		$this->orders[] = OrderHelper::create_order();
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests getting a single order.
	 * @since 3.5.0
	 */
	public function test_get_item() {
		wp_set_current_user( $this->user );
		$order = OrderHelper::create_order();
		$order->add_meta_data( 'key', 'value' );
		$order->add_meta_data( 'key2', 'value2' );
		$order->save();
		$this->orders[] = $order;
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );
		$data           = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $data['id'] );

		// Test meta data is set.
		$this->assertEquals( 'key', $data['meta_data'][0]->key );
		$this->assertEquals( 'value', $data['meta_data'][0]->value );
		$this->assertEquals( 'key2', $data['meta_data'][1]->key );
		$this->assertEquals( 'value2', $data['meta_data'][1]->value );
	}

	/**
	 * Tests getting a single order without the correct permissions.
	 * @since 3.5.0
	 */
	public function test_get_item_without_permission() {
		wp_set_current_user( 0 );
		$order          = OrderHelper::create_order();
		$this->orders[] = $order;
		$response       = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests line items have the expected meta_data properties when getting a single order.
	 */
	public function test_get_item_with_line_items_meta_data() {
		wp_set_current_user( $this->user );

		$attribute_name            = 'Site Level Type';
		$site_level_attribute_id   = wc_create_attribute( array( 'name' => $attribute_name ) );
		$site_level_attribute_slug = wc_attribute_taxonomy_name_by_id( $site_level_attribute_id );

		// Register the attribute so that wp_insert_term will be successful.
		register_taxonomy( $site_level_attribute_slug, array( 'product' ), array() );

		$term_name                        = 'Site Level Value - Wood';
		$site_level_term_insertion_result = wp_insert_term( $term_name, $site_level_attribute_slug );
		$site_level_term                  = get_term( $site_level_term_insertion_result['term_id'] );

		$product   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );

		$line_item = new WC_Order_Item_Product();
		$line_item->set_product( $variation );
		$line_item->set_props(
			array( 'variation' => array( "attribute_{$site_level_attribute_slug}" => $site_level_term->slug ) )
		);

		$order = OrderHelper::create_order();
		$order->add_item( $line_item );
		$order->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $data['id'] );

		$last_line_item = array_slice( $data['line_items'], -1 )[0];

		$size_meta_data = $last_line_item['meta_data'][0];
		$this->assertEquals( $line_item->get_meta_data()[0]->id, $size_meta_data['id'] );
		$this->assertEquals( 'pa_size', $size_meta_data['key'] );
		$this->assertEquals( 'size', $size_meta_data['display_key'] );
		$this->assertEquals( 'small', $size_meta_data['value'] );
		$this->assertEquals( 'small', $size_meta_data['display_value'] );

		$color_meta_data = $last_line_item['meta_data'][1];
		$this->assertEquals( $line_item->get_meta_data()[1]->id, $color_meta_data['id'] );
		$this->assertEquals( $site_level_attribute_slug, $color_meta_data['key'] );
		$this->assertEquals( $attribute_name, $color_meta_data['display_key'] );
		$this->assertEquals( $site_level_term->slug, $color_meta_data['value'] );
		$this->assertEquals( $term_name, $color_meta_data['display_value'] );
	}

	/**
	 * Tests that line items for variations includes the parent product name.
	 */
	public function test_get_item_with_variation_parent_name() {
		wp_set_current_user( $this->user );

		$product   = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_variation_product();
		$variation = wc_get_product( $product->get_children()[0] );

		$line_item = new WC_Order_Item_Product();
		$line_item->set_product( $variation );

		$order = OrderHelper::create_order();
		$order->add_item( $line_item );
		$order->save();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $order->get_id() ) );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_id(), $data['id'] );

		$last_line_item = array_slice( $data['line_items'], -1 )[0];

		// The name property contains the parent product name and the attribute value (ex. "Dummy Variable Product - small").
		$this->assertEquals( $variation->get_name(), $last_line_item['name'] );
		$this->assertTrue( false !== strpos( $last_line_item['name'], $variation->get_attribute( 'pa_size' ) ) );
		// The parent_name property only contains the parent product name (ex. "Dummy Variable Product").
		$this->assertEquals( $product->get_name(), $last_line_item['parent_name'] );
		$this->assertNotEquals( $variation->get_name(), $last_line_item['parent_name'] );
		$this->assertTrue( false === strpos( $last_line_item['parent_name'], $variation->get_attribute( 'pa_size' ) ) );
	}

	/**
	 * Tests getting an order with an invalid ID.
	 * @since 3.5.0
	 */
	public function test_get_item_invalid_id() {
		wp_set_current_user( $this->user );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/99999999' ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests getting an order with an invalid ID.
	 * @since 3.5.0
	 */
	public function test_get_item_refund_id() {
		wp_set_current_user( $this->user );
		$order    = OrderHelper::create_order();
		$refund   = wc_create_refund(
			array(
				'order_id' => $order->get_id(),
			)
		);
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v3/orders/' . $refund->get_id() ) );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Tests creating an order.
	 * @since 3.5.0
	 */
	public function test_create_order() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'payment_method'       => 'bacs',
				'payment_method_title' => 'Direct Bank Transfer',
				'set_paid'             => true,
				'billing'              => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
					'email'      => 'john.doe@example.com',
					'phone'      => '(555) 555-5555',
				),
				'shipping'             => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
					'phone'      => '(555) 555-5555',
				),
				'line_items'           => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
				'shipping_lines'       => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => '10.00',
						'instance_id'  => '1',
						'meta_data'    => array(
							array(
								'key'   => 'string',
								'value' => 'string_val',
							),
							array(
								'key'   => 'integer',
								'value' => 1,
							),
							array(
								'key'   => 'array',
								'value' => array( 1, 2 ),
							),
						),
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$order    = wc_get_order( $data['id'] );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $order->get_payment_method(), $data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), $data['payment_method_title'] );
		$this->assertEquals( $order->get_billing_first_name(), $data['billing']['first_name'] );
		$this->assertEquals( $order->get_billing_last_name(), $data['billing']['last_name'] );
		$this->assertEquals( '', $data['billing']['company'] );
		$this->assertEquals( $order->get_billing_address_1(), $data['billing']['address_1'] );
		$this->assertEquals( $order->get_billing_address_2(), $data['billing']['address_2'] );
		$this->assertEquals( $order->get_billing_city(), $data['billing']['city'] );
		$this->assertEquals( $order->get_billing_state(), $data['billing']['state'] );
		$this->assertEquals( $order->get_billing_postcode(), $data['billing']['postcode'] );
		$this->assertEquals( $order->get_billing_country(), $data['billing']['country'] );
		$this->assertEquals( $order->get_billing_email(), $data['billing']['email'] );
		$this->assertEquals( $order->get_billing_phone(), $data['billing']['phone'] );
		$this->assertEquals( $order->get_shipping_first_name(), $data['shipping']['first_name'] );
		$this->assertEquals( $order->get_shipping_last_name(), $data['shipping']['last_name'] );
		$this->assertEquals( '', $data['shipping']['company'] );
		$this->assertEquals( $order->get_shipping_address_1(), $data['shipping']['address_1'] );
		$this->assertEquals( $order->get_shipping_address_2(), $data['shipping']['address_2'] );
		$this->assertEquals( $order->get_shipping_city(), $data['shipping']['city'] );
		$this->assertEquals( $order->get_shipping_state(), $data['shipping']['state'] );
		$this->assertEquals( $order->get_shipping_postcode(), $data['shipping']['postcode'] );
		$this->assertEquals( $order->get_shipping_country(), $data['shipping']['country'] );
		$this->assertEquals( $order->get_shipping_phone(), $data['shipping']['phone'] );
		$this->assertEquals( 1, count( $data['line_items'] ) );
		$this->assertEquals( 1, count( $data['shipping_lines'] ) );

		$shipping               = current( $order->get_items( 'shipping' ) );
		$expected_shipping_line = array(
			'id'           => $shipping->get_id(),
			'method_title' => $shipping->get_method_title(),
			'method_id'    => $shipping->get_method_id(),
			'instance_id'  => $shipping->get_instance_id(),
			'total'        => wc_format_decimal( $shipping->get_total(), '' ),
			'total_tax'    => wc_format_decimal( $shipping->get_total_tax(), '' ),
			'taxes'        => array(),
		);
		foreach ( $expected_shipping_line as $key => $value ) {
			$this->assertEquals( $value, $data['shipping_lines'][0][ $key ] );
		}

		$actual_shipping_line_meta_data = $data['shipping_lines'][0]['meta_data'];
		$this->assertCount( 3, $actual_shipping_line_meta_data );
		foreach ( $shipping->get_meta_data() as $index => $expected_meta_item ) {
			$this->assertEquals( $expected_meta_item->id, $actual_shipping_line_meta_data[ $index ]['id'] );
			$this->assertEquals( $expected_meta_item->key, $actual_shipping_line_meta_data[ $index ]['key'] );
			$this->assertEquals( $expected_meta_item->value, $actual_shipping_line_meta_data[ $index ]['value'] );
			$this->assertEquals( $expected_meta_item->key, $actual_shipping_line_meta_data[ $index ]['display_key'] );
			$this->assertEquals( $expected_meta_item->value, $actual_shipping_line_meta_data[ $index ]['display_value'] );
		}
	}

	/**
	 * Test the sanitization of the payment_method_title field through the API.
	 *
	 * @since 3.5.2
	 */
	public function test_create_update_order_payment_method_title_sanitize() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		// Test when creating order.
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'payment_method'       => 'bacs',
				'payment_method_title' => '<h1>Sanitize this <script>alert(1);</script></h1>',
				'set_paid'             => true,
				'billing'              => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
					'email'      => 'john.doe@example.com',
					'phone'      => '(555) 555-5555',
				),
				'shipping'             => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
					'phone'      => '(555) 555-5555',
				),
				'line_items'           => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
				'shipping_lines'       => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => '10',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$order    = wc_get_order( $data['id'] );
		$this->assertEquals( 201, $response->get_status() );
		$this->assertEquals( $order->get_payment_method(), $data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), 'Sanitize this' );

		// Test when updating order.
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $data['id'] );
		$request->set_body_params(
			array(
				'payment_method'       => 'bacs',
				'payment_method_title' => '<h1>Sanitize this too <script>alert(1);</script></h1>',
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$order    = wc_get_order( $data['id'] );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $order->get_payment_method(), $data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), 'Sanitize this too' );
	}

	/**
	 * Tests creating an order without required fields.
	 * @since 3.5.0
	 */
	public function test_create_order_invalid_fields() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();

		// Non-existent customer.
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'payment_method'       => 'bacs',
				'payment_method_title' => 'Direct Bank Transfer',
				'set_paid'             => true,
				'customer_id'          => 99999,
				'billing'              => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
					'email'      => 'john.doe@example.com',
					'phone'      => '(555) 555-5555',
				),
				'shipping'             => array(
					'first_name' => 'John',
					'last_name'  => 'Doe',
					'address_1'  => '969 Market',
					'address_2'  => '',
					'city'       => 'San Francisco',
					'state'      => 'CA',
					'postcode'   => '94103',
					'country'    => 'US',
					'phone'      => '(555) 555-5555',
				),
				'line_items'           => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 2,
					),
				),
				'shipping_lines'       => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => 10,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Tests create an order with an invalid product.
	 *
	 * @since 3.9.0
	 */
	public function test_create_order_with_invalid_product() {
		wp_set_current_user( $this->user );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_body_params(
			array(
				'line_items' => array(
					array(
						'quantity' => 2,
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 'woocommerce_rest_required_product_reference', $data['code'] );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Tests updating an order.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order() {
		wp_set_current_user( $this->user );
		$order   = OrderHelper::create_order();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'payment_method' => 'test-update',
				'billing'        => array(
					'first_name' => 'Fish',
					'last_name'  => 'Face',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'test-update', $data['payment_method'] );
		$this->assertEquals( 'Fish', $data['billing']['first_name'] );
		$this->assertEquals( 'Face', $data['billing']['last_name'] );
	}

	/**
	 * Tests updating an order and removing items.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_remove_items() {
		wp_set_current_user( $this->user );
		$order = OrderHelper::create_order();
		$fee   = new WC_Order_Item_Fee();
		$fee->set_props(
			array(
				'name'       => 'Some Fee',
				'tax_status' => 'taxable',
				'total'      => '100',
				'tax_class'  => '',
			)
		);
		$order->add_item( $fee );
		$order->save();

		$request  = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$fee_data = current( $order->get_items( 'fee' ) );

		$request->set_body_params(
			array(
				'fee_lines' => array(
					array(
						'id'   => $fee_data->get_id(),
						'name' => null,
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( empty( $data['fee_lines'] ) );
	}

	/**
	 * Tests updating an order after deleting a product.
	 *
	 * @since 3.9.0
	 */
	public function test_update_order_after_delete_product() {
		wp_set_current_user( $this->user );
		$product = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper::create_simple_product();
		$order   = OrderHelper::create_order( 1, $product );
		$product->delete( true );

		$request    = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$line_items = $order->get_items( 'line_item' );
		$item       = current( $line_items );

		$request->set_body_params(
			array(
				'line_items' => array(
					array(
						'id'       => $item->get_id(),
						'quantity' => 10,
					),
				),
			)
		);

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$expected = array(
			'id'           => $item->get_id(),
			'name'         => 'Dummy Product',
			'product_id'   => 0,
			'variation_id' => 0,
			'quantity'     => 10,
			'tax_class'    => '',
			'subtotal'     => '40.00',
			'subtotal_tax' => '0.00',
			'total'        => '40.00',
			'total_tax'    => '0.00',
			'taxes'        => array(),
			'meta_data'    => array(),
			'sku'          => null,
			'price'        => 4,
			'parent_name'  => null,
			'image'        => array(
				'id'  => 0,
				'src' => '',
			),
		);

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( $expected, $data['line_items'][0] );
	}

	/**
	 * Data provider for test_update_order_add_coupons.
	 *
	 * @return array Data for test_update_order_add_coupons.
	 */
	public function data_provider_for_test_update_order_add_coupons() {
		return array(

			// Successful case, no previous coupon, it gets created.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(
							'code' => 'fake-coupon-2',
						),
					),
				),
				'order_has_coupon_before_request'          => false,
				'expected_request_result'                  => array(
					'code' => 200,
				),
				'expected_order_coupon_code_after_request' => 'fake-coupon-2',
			),

			// Successful case with previous coupon, it gets replaced.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(
							'code' => 'fake-coupon-2',
						),
					),
				),
				'order_has_coupon_before_request'          => true,
				'expected_request_result'                  => array(
					'code' => 200,
				),
				'expected_order_coupon_code_after_request' => 'fake-coupon-2',
			),

			// Bad request: invalid coupon name, no previous coupon, it doesn't get added.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(
							'code' => 'not-existing-coupon',
						),
					),
				),
				'order_has_coupon_before_request'          => false,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Coupon "not-existing-coupon" does not exist!',
				),
				'expected_order_coupon_code_after_request' => null,
			),

			// Bad request: invalid coupon name, coupon existed, it's kept.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(
							'code' => 'not-existing-coupon',
						),
					),
				),
				'order_has_coupon_before_request'          => true,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Coupon "not-existing-coupon" does not exist!',
				),
				'expected_order_coupon_code_after_request' => 'fake-coupon',
			),

			// Bad request: has coupon id, no previous coupon, it doesn't get added.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(
							'id'   => '1234',
							'code' => 'fake-coupon-2',
						),
					),
				),
				'order_has_coupon_before_request'          => false,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Coupon item ID is readonly.',
				),
				'expected_order_coupon_code_after_request' => null,
			),

			// Bad request: has coupon id, previous coupon existed, it's kept.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(
							'id'   => '1234',
							'code' => 'fake-coupon-2',
						),
					),
				),
				'order_has_coupon_before_request'          => true,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Coupon item ID is readonly.',
				),
				'expected_order_coupon_code_after_request' => 'fake-coupon',
			),

			// Bad request: no coupon code, no previous coupon, it doesn't get added.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(),
					),
				),
				'order_has_coupon_before_request'          => false,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Coupon code is required.',
				),
				'expected_order_coupon_code_after_request' => null,
			),

			// Bad request: no coupon code, previous coupon existed, it's kept.
			array(
				'request_body'                             => array(
					'coupon_lines' => array(
						array(),
					),
				),
				'order_has_coupon_before_request'          => true,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Coupon code is required.',
				),
				'expected_order_coupon_code_after_request' => 'fake-coupon',
			),

			// Bad request: invalid input ('coupon_lines' is not an array), no previous coupon, it doesn't get added.
			array(
				'request_body'                             => array(
					'coupon_lines' => 1234,
				),
				'order_has_coupon_before_request'          => false,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Invalid parameter(s): coupon_lines',
				),
				'expected_order_coupon_code_after_request' => null,
			),

			// Bad request: invalid input ('coupon_lines' is not an array), previous coupon existed, it's kept.
			array(
				'request_body'                             => array(
					'coupon_lines' => 1234,
				),
				'order_has_coupon_before_request'          => true,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Invalid parameter(s): coupon_lines',
				),
				'expected_order_coupon_code_after_request' => 'fake-coupon',
			),

			// Bad request: invalid input ('coupon_lines' has non-array elements), no previous coupon, it doesn't get added.
			array(
				'request_body'                             => array(
					'coupon_lines' => array( 1234 ),
				),
				'order_has_coupon_before_request'          => false,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Invalid parameter(s): coupon_lines',
				),
				'expected_order_coupon_code_after_request' => null,
			),

			// Bad request: invalid input ('coupon_lines' has non-array elements), previous coupon existed, it's kept.
			array(
				'request_body'                             => array(
					'coupon_lines' => array( 1234 ),
				),
				'order_has_coupon_before_request'          => true,
				'expected_request_result'                  => array(
					'code'    => 400,
					'message' => 'Invalid parameter(s): coupon_lines',
				),
				'expected_order_coupon_code_after_request' => 'fake-coupon',
			),
		);
	}


	/**
	 * Tests updating an order and adding a coupon.
	 *
	 * @dataProvider data_provider_for_test_update_order_add_coupons
	 *
	 * @param array  $request_body The body for the API request.
	 * @param bool   $order_has_coupon_before_request If true, the order will have 'fake-coupon' applied before the API request.
	 * @param array  $expected_request_result Expected result from the API request, with 'code' and optionally 'message'.
	 * @param string $expected_order_coupon_code_after_request Code of the expected applied coupon after the API request, null if it shouldn't have a coupon applied.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_add_coupons( $request_body, $order_has_coupon_before_request, $expected_request_result, $expected_order_coupon_code_after_request ) {
		wp_set_current_user( $this->user );

		// Create order and coupons.

		$order                 = OrderHelper::create_order();
		$original_order_amount = $order->get_total();

		$coupons = array();

		$coupon = CouponHelper::create_coupon( 'fake-coupon' );
		$coupon->set_amount( 5 );
		$coupon->save();
		$coupons['fake-coupon'] = $coupon;

		$coupon = CouponHelper::create_coupon( 'fake-coupon-2' );
		$coupon->set_amount( 10 );
		$coupon->save();
		$coupons['fake-coupon-2'] = $coupon;

		if ( $order_has_coupon_before_request ) {
			$order->apply_coupon( $coupons['fake-coupon'] );
		}

		// Perform the request.

		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params( $request_body );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		// Check the response and the actual order data after the operation.

		$this->assertEquals( $expected_request_result['code'], $response->get_status() );

		$order         = wc_get_order( $order->get_id() );
		$order_coupons = array_values( $order->get_coupons() );
		if ( is_null( $expected_order_coupon_code_after_request ) ) {
			$expected_coupon       = null;
			$expected_order_amount = $original_order_amount;
		} else {
			$expected_coupon       = $coupons[ $expected_order_coupon_code_after_request ];
			$expected_order_amount = number_format( 50 - $expected_coupon->get_amount(), 2 );
		}

		$is_ok_status = $response->get_status() < 300;
		if ( $is_ok_status ) {
			$this->assertEquals( $expected_order_amount, $data['total'] );
			$this->assertCount( 1, $data['coupon_lines'] );
		} else {
			$this->assertEquals( $expected_request_result['message'], $data['message'] );
		}

		if ( is_null( $expected_order_coupon_code_after_request ) ) {
			$this->assertEquals( '50.00', $order->get_total() );
			$this->assertCount( 0, $order_coupons );
		} else {
			$this->assertEquals( number_format( $expected_order_amount, 2 ), $order->get_total() );
			$this->assertCount( 1, $order_coupons );
			$this->assertEquals( $expected_coupon->get_code(), $order_coupons[0]->get_code() );
		}
	}

	/**
	 * Tests updating an order and removing a coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_remove_coupons() {
		wp_set_current_user( $this->user );
		$order      = OrderHelper::create_order();
		$order_item = current( $order->get_items() );
		$coupon     = CouponHelper::create_coupon( 'fake-coupon' );
		$coupon->set_amount( 5 );
		$coupon->save();

		$order->apply_coupon( $coupon );
		$order->save();

		// Check that the coupon is applied.
		$this->assertEquals( '45.00', $order->get_total() );

		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );

		$request->set_body_params(
			array(
				'coupon_lines' => array(),
				'line_items'   => array(
					array(
						'id'         => $order_item->get_id(),
						'product_id' => $order_item->get_product_id(),
						'total'      => '40.00',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( empty( $data['coupon_lines'] ) );
		$this->assertEquals( '50.00', $data['total'] );
	}

	/**
	 * Tests updating an order with an invalid coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_invalid_coupon() {
		wp_set_current_user( $this->user );
		$order   = OrderHelper::create_order();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );

		$request->set_body_params(
			array(
				'coupon_lines' => array(
					array(
						'code' => 'NON_EXISTING_COUPON',
					),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'woocommerce_rest_invalid_coupon', $data['code'] );
		$this->assertEquals( 'Coupon "non_existing_coupon" does not exist!', $data['message'] );
	}

	/**
	 * Tests updating an order without the correct permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_without_permission() {
		wp_set_current_user( 0 );
		$order   = OrderHelper::create_order();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_body_params(
			array(
				'payment_method' => 'test-update',
				'billing'        => array(
					'first_name' => 'Fish',
					'last_name'  => 'Face',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Tests that updating an order with an invalid id fails.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/999999' );
		$request->set_body_params(
			array(
				'payment_method' => 'test-update',
				'billing'        => array(
					'first_name' => 'Fish',
					'last_name'  => 'Face',
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test deleting an order.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_order() {
		wp_set_current_user( $this->user );
		$order   = OrderHelper::create_order();
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( null, get_post( $order->get_id() ) );
	}

	/**
	 * Test deleting an order without permission/creds.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_order_without_permission() {
		wp_set_current_user( 0 );
		$order   = OrderHelper::create_order();
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/' . $order->get_id() );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test deleting an order with an invalid id.
	 *
	 * @since 3.5.0
	 */
	public function test_delete_order_invalid_id() {
		wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'DELETE', '/wc/v3/orders/9999999' );
		$request->set_param( 'force', true );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
	}

	/**
	 * Test batch managing product reviews.
	 *
	 * @since 3.5.0
	 */
	public function test_orders_batch() {
		wp_set_current_user( $this->user );

		$order1 = OrderHelper::create_order();
		$order2 = OrderHelper::create_order();
		$order3 = OrderHelper::create_order();

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders/batch' );
		$request->set_body_params(
			array(
				'update' => array(
					array(
						'id'             => $order1->get_id(),
						'payment_method' => 'updated',
					),
				),
				'delete' => array(
					$order2->get_id(),
					$order3->get_id(),
				),
			)
		);
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 'updated', $data['update'][0]['payment_method'] );
		$this->assertEquals( $order2->get_id(), $data['delete'][0]['id'] );
		$this->assertEquals( $order3->get_id(), $data['delete'][1]['id'] );

		$request  = new WP_REST_Request( 'GET', '/wc/v3/orders' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();
		$this->assertEquals( 1, count( $data ) );
	}

	/**
	 * Test the order schema.
	 *
	 * @since 3.5.0
	 */
	public function test_order_schema() {
		wp_set_current_user( $this->user );
		$order      = OrderHelper::create_order();
		$request    = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders/' . $order->get_id() );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 47, count( $properties ) );
		$this->assertArrayHasKey( 'id', $properties );
	}

	/**
	 * Test the order line items schema.
	 */
	public function test_order_line_items_schema() {
		wp_set_current_user( $this->user );
		$order    = OrderHelper::create_order();
		$request  = new WP_REST_Request( 'OPTIONS', '/wc/v3/orders/' . $order->get_id() );
		$response = $this->server->dispatch( $request );

		$data = $response->get_data();

		$line_item_properties = $data['schema']['properties']['line_items']['items']['properties'];
		$this->assertEquals( 16, count( $line_item_properties ) );
		$this->assertArrayHasKey( 'id', $line_item_properties );
		$this->assertArrayHasKey( 'meta_data', $line_item_properties );
		$this->assertArrayHasKey( 'parent_name', $line_item_properties );

		$meta_data_item_properties = $line_item_properties['meta_data']['items']['properties'];
		$this->assertEquals( 5, count( $meta_data_item_properties ) );
		$this->assertEquals( array( 'id', 'key', 'value', 'display_key', 'display_value' ), array_keys( $meta_data_item_properties ) );
	}

	/**
	 * Create an object for the tests in DateFilteringForCrudControllers.
	 *
	 * @return object The created object.
	 */
	private function get_item_for_date_filtering_tests() {
		return OrderHelper::create_order();
	}

	/**
	 * Get the REST API endpoint for the tests in DateFilteringForCrudControllers.
	 *
	 * @return string REST API endpoint for querying items.
	 */
	private function get_endpoint_for_date_filtering_tests() {
		return '/wc/v3/orders';
	}
}
