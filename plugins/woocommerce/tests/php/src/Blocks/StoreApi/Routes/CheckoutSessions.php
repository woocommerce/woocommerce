<?php
/**
 * Agentic Checkout Sessions Tests.
 *
 * @package Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes
 */

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;
use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Internal\Features\FeaturesController;

/**
 * CheckoutSessions Controller Tests.
 */
class CheckoutSessions extends ControllerTestCase {

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Enable the agentic_checkout feature.
		$features_controller = wc_get_container()->get( FeaturesController::class );
		update_option( 'woocommerce_feature_agentic_checkout_enabled', 'yes' );

		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$this->products = array(
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 20,
					'weight'        => 5,
				)
			),
			$fixtures->get_simple_product(
				array(
					'name'          => 'Virtual Product',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 15,
					'virtual'       => true,
				)
			),
		);

		wc_empty_cart();
		$this->reset_customer_state();
	}

	/**
	 * Tear down test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		delete_option( 'woocommerce_feature_agentic_checkout_enabled' );
	}

	/**
	 * Resets customer state and remove any existing data from previous tests.
	 */
	private function reset_customer_state() {
		wc()->customer->set_billing_country( 'US' );
		wc()->customer->set_shipping_country( 'US' );
		wc()->customer->set_billing_state( 'CA' );
		wc()->customer->set_shipping_state( 'CA' );
		wc()->customer->set_billing_postcode( '94102' );
		wc()->customer->set_shipping_postcode( '94102' );
		wc()->customer->set_billing_city( 'San Francisco' );
		wc()->customer->set_shipping_city( 'San Francisco' );
	}

	/**
	 * Test creating a checkout session with items only.
	 */
	public function test_create_checkout_session_with_items() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 2,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'id', $data );
		$this->assertArrayHasKey( 'status', $data );
		$this->assertArrayHasKey( 'line_items', $data );
		$this->assertArrayHasKey( 'currency', $data );
		$this->assertArrayHasKey( 'totals', $data );
		$this->assertArrayHasKey( 'fulfillment_options', $data );
		$this->assertArrayHasKey( 'messages', $data );
		$this->assertArrayHasKey( 'links', $data );

		// Verify line items.
		$this->assertCount( 1, $data['line_items'] );
		$this->assertEquals( (string) $this->products[0]->get_id(), $data['line_items'][0]['item']['id'] );
		$this->assertEquals( 2, $data['line_items'][0]['item']['quantity'] );

		// Verify status (should be not_ready_for_payment without address).
		$this->assertEquals( 'not_ready_for_payment', $data['status'] );

		// Verify amounts are in cents (integers).
		$this->assertIsInt( $data['line_items'][0]['base_amount'] );
		$this->assertIsInt( $data['line_items'][0]['total'] );
		$this->assertEquals( 2000, $data['line_items'][0]['base_amount'] ); // $10 * 2 = $20 = 2000 cents
	}

	/**
	 * Test creating a checkout session with address.
	 */
	public function test_create_checkout_session_with_address() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items'               => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
					'fulfillment_address' => array(
						'name'        => 'John Doe',
						'line_one'    => '555 Golden Gate Avenue',
						'line_two'    => 'Apt 401',
						'city'        => 'San Francisco',
						'state'       => 'CA',
						'country'     => 'US',
						'postal_code' => '94102',
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// Verify address is set.
		$this->assertArrayHasKey( 'fulfillment_address', $data );
		$this->assertNotNull( $data['fulfillment_address'] );
		$this->assertEquals( 'John Doe', $data['fulfillment_address']['name'] );
		$this->assertEquals( '555 Golden Gate Avenue', $data['fulfillment_address']['line_one'] );
		$this->assertEquals( 'Apt 401', $data['fulfillment_address']['line_two'] );
		$this->assertEquals( 'San Francisco', $data['fulfillment_address']['city'] );
		$this->assertEquals( 'CA', $data['fulfillment_address']['state'] );
		$this->assertEquals( 'US', $data['fulfillment_address']['country'] );
		$this->assertEquals( '94102', $data['fulfillment_address']['postal_code'] );

		// Verify fulfillment options are available.
		$this->assertNotEmpty( $data['fulfillment_options'] );
		$this->assertIsArray( $data['fulfillment_options'] );
	}

	/**
	 * Test creating a checkout session with buyer info.
	 */
	public function test_create_checkout_session_with_buyer() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
					'buyer' => array(
						'first_name'   => 'Jane',
						'last_name'    => 'Smith',
						'email'        => 'jane@example.com',
						'phone_number' => '+1234567890',
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		// Verify buyer info is set.
		$this->assertArrayHasKey( 'buyer', $data );
		$this->assertNotNull( $data['buyer'] );
		$this->assertEquals( 'Jane', $data['buyer']['first_name'] );
		$this->assertEquals( 'Smith', $data['buyer']['last_name'] );
		$this->assertEquals( 'jane@example.com', $data['buyer']['email'] );
		$this->assertEquals( '+1234567890', $data['buyer']['phone_number'] );
	}

	/**
	 * Test status calculation for not_ready_for_payment.
	 */
	public function test_status_not_ready_for_payment() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// Without address and shipping method, should be not_ready_for_payment.
		$this->assertEquals( 'not_ready_for_payment', $data['status'] );
	}

	/**
	 * Test status calculation for ready_for_payment.
	 */
	public function test_status_ready_for_payment() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );

		// Get shipping methods first.
		wc()->customer->set_shipping_address_1( '555 Golden Gate Avenue' );
		wc()->customer->set_shipping_city( 'San Francisco' );
		wc()->customer->set_shipping_state( 'CA' );
		wc()->customer->set_shipping_postcode( '94102' );
		wc()->customer->set_shipping_country( 'US' );
		wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );
		wc()->cart->calculate_shipping();
		$packages           = wc()->shipping()->get_packages();
		$shipping_method_id = ! empty( $packages[0]['rates'] ) ? array_key_first( $packages[0]['rates'] ) : null;
		wc_empty_cart();

		$request->set_body(
			wp_json_encode(
				array(
					'items'                 => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
					'fulfillment_address'   => array(
						'name'        => 'John Doe',
						'line_one'    => '555 Golden Gate Avenue',
						'city'        => 'San Francisco',
						'state'       => 'CA',
						'country'     => 'US',
						'postal_code' => '94102',
					),
					'fulfillment_option_id' => $shipping_method_id,
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// With address and shipping method, should be ready_for_payment.
		$this->assertEquals( 'ready_for_payment', $data['status'] );
	}

	/**
	 * Test invalid product ID returns error.
	 */
	public function test_invalid_product_returns_error() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => '999999',
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'invalid_product', $data['code'] );
	}

	/**
	 * Test out of stock product returns error.
	 */
	public function test_out_of_stock_product_returns_error() {
		// Set product out of stock.
		$this->products[0]->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$this->products[0]->save();

		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'out_of_stock', $data['code'] );
	}

	/**
	 * Test virtual product doesn't require shipping address.
	 */
	public function test_virtual_product_ready_for_payment_without_address() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[2]->get_id(), // Virtual product.
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		// Virtual product should be ready_for_payment without address.
		$this->assertEquals( 'ready_for_payment', $data['status'] );
	}

	/**
	 * Test totals array format.
	 */
	public function test_totals_array_format() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data['totals'] );
		$this->assertNotEmpty( $data['totals'] );

		// Verify required total types exist.
		$total_types = array_column( $data['totals'], 'type' );
		$this->assertContains( 'items_base_amount', $total_types );
		$this->assertContains( 'subtotal', $total_types );
		$this->assertContains( 'total', $total_types );

		// Verify each total has required fields.
		foreach ( $data['totals'] as $total ) {
			$this->assertArrayHasKey( 'type', $total );
			$this->assertArrayHasKey( 'display_text', $total );
			$this->assertArrayHasKey( 'amount', $total );
			$this->assertIsInt( $total['amount'] );
		}
	}

	/**
	 * Test payment provider is included.
	 */
	public function test_payment_provider_included() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// Should have payment_provider even if null.
		$this->assertArrayHasKey( 'payment_provider', $data );
	}

	/**
	 * Test links array includes terms and privacy.
	 */
	public function test_links_array() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertIsArray( $data['links'] );

		// Verify each link has required fields.
		foreach ( $data['links'] as $link ) {
			$this->assertArrayHasKey( 'type', $link );
			$this->assertArrayHasKey( 'url', $link );
			$this->assertIsString( $link['type'] );
			$this->assertIsString( $link['url'] );
		}
	}

	/**
	 * Test feature flag disabled returns 403.
	 */
	public function test_feature_flag_disabled_returns_403() {
		// Disable feature.
		delete_option( 'woocommerce_feature_agentic_checkout_enabled' );

		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test currency format is lowercase.
	 */
	public function test_currency_format_is_lowercase() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items' => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// Currency should be lowercase (e.g., "usd" not "USD").
		$this->assertArrayHasKey( 'currency', $data );
		$this->assertSame( strtolower( $data['currency'] ), $data['currency'] );
	}

	/**
	 * Test address line_two returns empty string when not set.
	 */
	public function test_address_line_two_empty_string() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items'               => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
					'fulfillment_address' => array(
						'name'        => 'John Doe',
						'line_one'    => '555 Golden Gate Avenue',
						// line_two not provided.
						'city'        => 'San Francisco',
						'state'       => 'CA',
						'country'     => 'US',
						'postal_code' => '94102',
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// line_two should be empty string, not null.
		$this->assertArrayHasKey( 'fulfillment_address', $data );
		$this->assertNotNull( $data['fulfillment_address'] );
		$this->assertArrayHasKey( 'line_two', $data['fulfillment_address'] );
		$this->assertSame( '', $data['fulfillment_address']['line_two'] );
		$this->assertNotNull( $data['fulfillment_address']['line_two'] ); // Explicitly not null.
	}

	/**
	 * Test address line_two preserves value when provided.
	 */
	public function test_address_line_two_with_value() {
		$request = new \WP_REST_Request( 'POST', '/wc/agentic/v1/checkout_sessions' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'items'               => array(
						array(
							'id'       => (string) $this->products[0]->get_id(),
							'quantity' => 1,
						),
					),
					'fulfillment_address' => array(
						'name'        => 'John Doe',
						'line_one'    => '555 Golden Gate Avenue',
						'line_two'    => 'Apt 401',
						'city'        => 'San Francisco',
						'state'       => 'CA',
						'country'     => 'US',
						'postal_code' => '94102',
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		// line_two should preserve the provided value.
		$this->assertEquals( 'Apt 401', $data['fulfillment_address']['line_two'] );
	}
}
