<?php
/**
 * Orders REST API tests.
 *
 * @package Automattic/WooCommerce/RestApi/Tests
 */

namespace Automattic\WooCommerce\RestApi\UnitTests\Tests\Version4;

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\RestApi\UnitTests\AbstractRestApiTest;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\ProductHelper;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;

/**
 * Abstract Rest API Test Class
 *
 * @extends AbstractRestApiTest
 */
class Orders extends AbstractRestApiTest {
	/**
	 * Routes that this endpoint creates.
	 *
	 * @var array
	 */
	protected $routes = [
		'/wc/v4/orders',
		'/wc/v4/orders/(?P<id>[\d]+)',
		'/wc/v4/orders/batch',
	];

	/**
	 * The endpoint schema.
	 *
	 * @var array Keys are property names, values are supported context.
	 */
	protected $properties = [
		'id'                   => array( 'view', 'edit' ),
		'parent_id'            => array( 'view', 'edit' ),
		'number'               => array( 'view', 'edit' ),
		'order_key'            => array( 'view', 'edit' ),
		'created_via'          => array( 'view', 'edit' ),
		'version'              => array( 'view', 'edit' ),
		'status'               => array( 'view', 'edit' ),
		'currency'             => array( 'view', 'edit' ),
		'currency_symbol'      => array( 'view', 'edit' ),
		'date_created'         => array( 'view', 'edit' ),
		'date_created_gmt'     => array( 'view', 'edit' ),
		'date_modified'        => array( 'view', 'edit' ),
		'date_modified_gmt'    => array( 'view', 'edit' ),
		'discount_total'       => array( 'view', 'edit' ),
		'discount_tax'         => array( 'view', 'edit' ),
		'shipping_total'       => array( 'view', 'edit' ),
		'shipping_tax'         => array( 'view', 'edit' ),
		'cart_tax'             => array( 'view', 'edit' ),
		'total'                => array( 'view', 'edit' ),
		'total_tax'            => array( 'view', 'edit' ),
		'prices_include_tax'   => array( 'view', 'edit' ),
		'customer_id'          => array( 'view', 'edit' ),
		'customer_ip_address'  => array( 'view', 'edit' ),
		'customer_user_agent'  => array( 'view', 'edit' ),
		'customer_note'        => array( 'view', 'edit' ),
		'billing'              => array( 'view', 'edit' ),
		'shipping'             => array( 'view', 'edit' ),
		'payment_method'       => array( 'view', 'edit' ),
		'payment_method_title' => array( 'view', 'edit' ),
		'transaction_id'       => array( 'view', 'edit' ),
		'date_paid'            => array( 'view', 'edit' ),
		'date_paid_gmt'        => array( 'view', 'edit' ),
		'date_completed'       => array( 'view', 'edit' ),
		'date_completed_gmt'   => array( 'view', 'edit' ),
		'cart_hash'            => array( 'view', 'edit' ),
		'meta_data'            => array( 'view', 'edit' ),
		'line_items'           => array( 'view', 'edit' ),
		'tax_lines'            => array( 'view', 'edit' ),
		'shipping_lines'       => array( 'view', 'edit' ),
		'fee_lines'            => array( 'view', 'edit' ),
		'coupon_lines'         => array( 'view', 'edit' ),
		'refunds'              => array( 'view', 'edit' ),
		'set_paid'             => array( 'edit' ),
	];

	/**
	 * Test create.
	 */
	public function test_create() {
		$product = ProductHelper::create_simple_product();
		$data    = [
			'currency'             => 'ZAR',
			'customer_id'          => 1,
			'customer_note'        => 'I am a note',
			'transaction_id'       => 'test',
			'payment_method'       => 'bacs',
			'payment_method_title' => 'Direct Bank Transfer',
			'set_paid'             => true,
			'billing'              => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'company'    => '',
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
				'company'    => '',
				'address_1'  => '969 Market',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94103',
				'country'    => 'US',
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
		];
		$response = $this->do_request( '/wc/v4/orders', 'POST', $data );
		$this->assertExpectedResponse( $response, 201, $data );
	}

		/**
	 * Test the sanitization of the payment_method_title field through the API.
	 *
	 * @since 3.5.2
	 */
	public function test_create_update_order_payment_method_title_sanitize() {
		$product = ProductHelper::create_simple_product();
		$data    = [
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
		];
		$response = $this->do_request( '/wc/v4/orders', 'POST', $data );
		$order    = wc_get_order( $response->data['id'] );
		$this->assertExpectedResponse( $response, 201 );
		$this->assertEquals( $order->get_payment_method(), $response->data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), 'Sanitize this' );

		// Test when updating order.
		$response = $this->do_request(
			'/wc/v4/orders/' . $response->data['id'],
			'POST', [
				'payment_method'       => 'bacs',
				'payment_method_title' => '<h1>Sanitize this too <script>alert(1);</script></h1>',
			]
		);
		$order    = wc_get_order( $response->data['id'] );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( $order->get_payment_method(), $response->data['payment_method'] );
		$this->assertEquals( $order->get_payment_method_title(), 'Sanitize this too' );
	}

	/**
	 * Tests creating an order without required fields.
	 * @since 3.5.0
	 */
	public function test_create_order_invalid_fields() {
		$product = ProductHelper::create_simple_product();
		$data    = [
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
		];
		$response = $this->do_request( '/wc/v4/orders', 'POST', $data );
		$this->assertExpectedResponse( $response, 400 );
	}

	/**
	 * Test read.
	 */
	public function test_read() {
		$product  = ProductHelper::create_simple_product();
		$product->set_regular_price( 10.95 );
		$product->set_sale_price( false );
		$product->save();
		$customer = CustomerHelper::create_customer();
		$orders   = [];
		$orders[] = OrderHelper::create_order( $customer->get_id(), $product );

		// Create orders.
		for ( $i = 0; $i < 9; $i++ ) {
			$orders[] = OrderHelper::create_order( $this->user );
		}

		$orders[5]->set_status( 'on-hold' );
		$orders[5]->save();
		$orders[6]->set_status( 'on-hold' );
		$orders[6]->save();
		$orders[0]->calculate_totals();
		$orders[0]->save();

		// Collection.
		$response = $this->do_request( '/wc/v4/orders', 'GET' );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 10, count( $response->data ) );

		// Collection args.
		$response = $this->do_request( '/wc/v4/orders', 'GET', [ 'status' => 'on-hold' ] );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 2, count( $response->data ) );

		$response = $this->do_request( '/wc/v4/orders', 'GET', [ 'number' => (string) $orders[0]->get_id() ] );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 1, count( $response->data ) );

		$response = $this->do_request( '/wc/v4/orders', 'GET', [ 'customer' => $customer->get_id() ] );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 1, count( $response->data ) );

		$response = $this->do_request( '/wc/v4/orders', 'GET', [ 'product' => $product->get_id() ] );
		$this->assertExpectedResponse( $response, 200 );
		$this->assertEquals( 1, count( $response->data ) );

		// Single collection args.
		$response = $this->do_request( '/wc/v4/orders/' . $orders[0]->get_id(), 'GET', [ 'dp' => 0 ] );
		$this->assertEquals( '54', $response->data['total'] );
		$response = $this->do_request( '/wc/v4/orders/' . $orders[0]->get_id(), 'GET', [ 'dp' => 2 ] );
		$this->assertEquals( '53.80', $response->data['total'] );

		// Single.
		$response = $this->do_request( '/wc/v4/orders/' . $orders[0]->get_id(), 'GET' );
		$this->assertExpectedResponse( $response, 200 );

		foreach ( $this->get_properties( 'view' ) as $property ) {
			$this->assertArrayHasKey( $property, $response->data );
		}

		// Invalid.
		$response = $this->do_request( '/wc/v4/orders/0', 'GET' );
		$this->assertExpectedResponse( $response, 404 );
	}

	/**
	 * Test update.
	 */
	public function test_update() {
		// Invalid.
		$response = $this->do_request( '/wc/v4/orders/0', 'POST', [ 'payment_method' => 'test' ] );
		$this->assertExpectedResponse( $response, 404 );

		// Update existing.
		$order    = OrderHelper::create_order();
		$data     = [
			'payment_method' => 'test-update',
			'billing'        => array(
				'first_name' => 'Fish',
				'last_name'  => 'Face',
			),
		];
		$response = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'POST',
			$data
		);
		$this->assertExpectedResponse( $response, 200, $data );

		foreach ( $this->get_properties( 'view' ) as $property ) {
			$this->assertArrayHasKey( $property, $response->data );
		}
	}

	/**
	 * Test delete.
	 */
	public function test_delete() {
		// Invalid.
		$result = $this->do_request( '/wc/v4/orders/0', 'DELETE', [ 'force' => false ] );
		$this->assertEquals( 404, $result->status );

		// Trash.
		$order  = OrderHelper::create_order();
		$result = $this->do_request( '/wc/v4/orders/' . $order->get_id(), 'DELETE', [ 'force' => false ] );
		$this->assertEquals( 200, $result->status );
		$this->assertEquals( 'trash', get_post_status( $order->get_id() ) );

		// Force.
		$order  = OrderHelper::create_order();
		$result = $this->do_request( '/wc/v4/orders/' . $order->get_id(), 'DELETE', [ 'force' => true ] );
		$this->assertEquals( 200, $result->status );
		$this->assertEquals( false, get_post( $order->get_id() ) );
	}

	/**
	 * Test read.
	 */
	public function test_guest_create() {
		wp_set_current_user( 0 );
		$product = ProductHelper::create_simple_product();
		$data    = [
			'currency'             => 'ZAR',
			'customer_id'          => 1,
			'customer_note'        => 'I am a note',
			'transaction_id'       => 'test',
			'payment_method'       => 'bacs',
			'payment_method_title' => 'Direct Bank Transfer',
			'set_paid'             => true,
			'billing'              => array(
				'first_name' => 'John',
				'last_name'  => 'Doe',
				'company'    => '',
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
				'company'    => '',
				'address_1'  => '969 Market',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94103',
				'country'    => 'US',
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
		];
		$response = $this->do_request( '/wc/v4/orders', 'POST', $data );
		$this->assertExpectedResponse( $response, 401, $data );
	}

	/**
	 * Test read.
	 */
	public function test_guest_read() {
		wp_set_current_user( 0 );
		$response = $this->do_request( '/wc/v4/orders', 'GET' );
		$this->assertExpectedResponse( $response, 401 );
	}

	/**
	 * Test update.
	 */
	public function test_guest_update() {
		wp_set_current_user( 0 );
		$order    = OrderHelper::create_order();
		$data     = [
			'payment_method' => 'test-update',
			'billing'        => array(
				'first_name' => 'Fish',
				'last_name'  => 'Face',
			),
		];
		$response = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'POST',
			$data
		);
		$this->assertExpectedResponse( $response, 401 );
	}

	/**
	 * Test delete.
	 */
	public function test_guest_delete() {
		wp_set_current_user( 0 );
		$order    = OrderHelper::create_order();
		$response = $this->do_request( '/wc/v4/orders/' . $order->get_id(), 'DELETE', [ 'force' => true ] );
		$this->assertEquals( 401, $response->status );
	}

	/**
	 * Test validation.
	 */
	public function test_enums() {
		$order    = OrderHelper::create_order();

		$response = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'POST',
			[
				'status' => 'invalid',
			]
		);
		$this->assertEquals( 400, $response->status );
		$this->assertEquals( 'Invalid parameter(s): status', $response->data['message'] );

		$response = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'POST',
			[
				'currency' => 'invalid',
			]
		);
		$this->assertEquals( 400, $response->status );
		$this->assertEquals( 'Invalid parameter(s): currency', $response->data['message'] );
	}

	/**
	 * Test a batch update.
	 */
	public function test_batch() {
		$order1 = OrderHelper::create_order();
		$order2 = OrderHelper::create_order();
		$order3 = OrderHelper::create_order();

		$result = $this->do_request(
			'/wc/v4/orders/batch',
			'POST',
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
		$this->assertEquals( 'updated', $result->data['update'][0]['payment_method'] );
		$this->assertEquals( $order2->get_id(), $result->data['delete'][0]['previous']['id'] );
		$this->assertEquals( $order3->get_id(), $result->data['delete'][1]['previous']['id'] );

		$result = $this->do_request( '/wc/v4/orders' );
		$this->assertEquals( 1, count( $result->data ) );
	}

	/**
	 * Tests updating an order and removing items.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_remove_items() {
		$order = OrderHelper::create_order();
		$fee   = new \WC_Order_Item_Fee();
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

		$fee_data = current( $order->get_items( 'fee' ) );
		$response = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'PUT',
			[
				'fee_lines' => array(
					array(
						'id'   => $fee_data->get_id(),
						'name' => null,
					),
				),
			]
		);
		$this->assertEquals( 200, $response->status );
		$this->assertTrue( empty( $response->data['fee_lines'] ) );
	}

	/**
	 * Tests updating an order and adding a coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_add_coupons() {
		$order      = OrderHelper::create_order();
		$order_item = current( $order->get_items() );
		$coupon     = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'fake-coupon' );
		$coupon->set_amount( 5 );
		$coupon->save();

		$response = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'PUT',
			[
				'coupon_lines' => array(
					array(
						'code' => 'fake-coupon',
					),
				),
			]
		);
		$this->assertEquals( 200, $response->status );
		$this->assertCount( 1, $response->data['coupon_lines'] );
		$this->assertEquals( '45.00', $response->data['total'] );
	}

	/**
	 * Tests updating an order and removing a coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_update_order_remove_coupons() {
		$order      = OrderHelper::create_order();
		$order_item = current( $order->get_items() );
		$coupon     = \Automattic\WooCommerce\RestApi\UnitTests\Helpers\CouponHelper::create_coupon( 'fake-coupon' );
		$coupon->set_amount( 5 );
		$coupon->save();
		$order->apply_coupon( $coupon );
		$order->save();

		// Check that the coupon is applied.
		$this->assertEquals( '45.00', $order->get_total() );

		$coupon_data = current( $order->get_items( 'coupon' ) );
		$response    = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'PUT',
			[
				'coupon_lines' => array(
					array(
						'id'   => $coupon_data->get_id(),
						'code' => null,
					),
				),
				'line_items'   => array(
					array(
						'id'         => $order_item->get_id(),
						'product_id' => $order_item->get_product_id(),
						'total'      => '40.00',
					),
				),
			]
		);
		$this->assertEquals( 200, $response->status );
		$this->assertTrue( empty( $response->data['coupon_lines'] ) );
		$this->assertEquals( '50.00', $response->data['total'] );
	}

	/**
	 * Tests updating an order with an invalid coupon.
	 *
	 * @since 3.5.0
	 */
	public function test_invalid_coupon() {
		$order    = OrderHelper::create_order();
		$response = $this->do_request(
			'/wc/v4/orders/' . $order->get_id(),
			'PUT',
			[
				'coupon_lines' => array(
					array(
						'code' => 'NON_EXISTING_COUPON',
					),
				),
			]
		);
		$this->assertEquals( 400, $response->status );
		$this->assertEquals( 'woocommerce_rest_invalid_coupon', $response->data['code'] );
		$this->assertEquals( 'Coupon "non_existing_coupon" does not exist!', $response->data['message'] );
	}
}
