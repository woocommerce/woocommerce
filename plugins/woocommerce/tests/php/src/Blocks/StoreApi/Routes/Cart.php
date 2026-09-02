<?php
/**
 * Controller Tests.
 */

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;
use Automattic\WooCommerce\Tests\Blocks\Helpers\ValidateSchema;
use Automattic\WooCommerce\StoreApi\Authentication;
use Automattic\WooCommerce\StoreApi\SessionHandler;
use Automattic\WooCommerce\StoreApi\Utilities\CartTokenUtils;
use Automattic\WooCommerce\StoreApi\Utilities\JsonWebToken;
use Spy_REST_Server;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * Cart Controller Tests.
 */
class Cart extends ControllerTestCase {

	/**
	 * Product IDs shared by the class.
	 *
	 * @var int[]
	 */
	private static $product_ids = array();

	/**
	 * Coupon ID shared by the class.
	 *
	 * @var int
	 */
	private static $coupon_id;

	/**
	 * Cart instance removed to mimic a REST request, restored on teardown.
	 *
	 * @var \WC_Cart|null
	 */
	private $cart_backup = null;

	/**
	 * Create immutable catalog rows shared by all test methods.
	 */
	public static function wpSetUpBeforeClass(): void {
		$fixtures = new FixtureData();
		$products = self::create_class_fixture_products(
			array(
				array(
					'name'          => 'Test Product 1',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				),
				array(
					'name'          => 'Test Product 2',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				),
				array(
					'name'          => 'Test Product 3',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
				),
				array(
					'name'          => 'Test Product 4',
					'stock_status'  => ProductStockStatus::IN_STOCK,
					'regular_price' => 10,
					'weight'        => 10,
					'virtual'       => true,
				),
			),
		);

		$products[0]->set_cross_sell_ids( array( $products[2]->get_id() ) );
		$products[0]->save();

		self::$product_ids = array_map( fn( $product ) => $product->get_id(), $products );
		self::$coupon_id   = $fixtures->get_coupon(
			array(
				'code'          => 'test_coupon',
				'discount_type' => 'fixed_cart',
				'amount'        => 1,
			)
		)->get_id();
	}

	/**
	 * Delete class products through WooCommerce data stores.
	 */
	public static function wpTearDownAfterClass(): void {
		try {
			self::delete_class_fixture_products( self::$product_ids );
		} finally {
			$coupon = new \WC_Coupon( self::$coupon_id );
			$coupon->delete( true );
		}
	}

	/**
	 * Setup test product data. Called before every test.
	 */
	protected function setUp(): void {
		parent::setUp();

		$fixtures = new FixtureData();
		$fixtures->shipping_add_flat_rate();

		$this->products = array_map( 'wc_get_product', self::$product_ids );
		$this->coupon   = new \WC_Coupon( self::$coupon_id );

		wc_empty_cart();
		$this->reset_customer_state();
		wc()->session->set( 'wc_notices', null );
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id() );
		wc()->cart->apply_coupon( $this->coupon->get_code() );

		// Draft order.
		$order = new \WC_Order();
		$order->set_status( 'checkout-draft' );
		$order->save();
		wc()->session->set( 'store_api_draft_order', $order->get_id() );
	}

	/**
	 * Resets customer state and remove any existing data from previous tests.
	 */
	private function reset_customer_state() {
		wc()->customer->set_billing_first_name( '' );
		wc()->customer->set_billing_last_name( '' );
		wc()->customer->set_billing_company( '' );
		wc()->customer->set_billing_address_1( '' );
		wc()->customer->set_billing_address_2( '' );
		wc()->customer->set_billing_city( '' );
		wc()->customer->set_billing_state( '' );
		wc()->customer->set_billing_postcode( '' );
		wc()->customer->set_billing_country( 'US' );
		wc()->customer->set_billing_email( '' );
		wc()->customer->set_billing_phone( '' );
		wc()->customer->set_shipping_first_name( '' );
		wc()->customer->set_shipping_last_name( '' );
		wc()->customer->set_shipping_company( '' );
		wc()->customer->set_shipping_address_1( '' );
		wc()->customer->set_shipping_address_2( '' );
		wc()->customer->set_shipping_city( '' );
		wc()->customer->set_shipping_state( '' );
		wc()->customer->set_shipping_postcode( '' );
		wc()->customer->set_shipping_country( 'US' );
	}

	/**
	 * Dispatches a Store API add-item request.
	 *
	 * @param array $body Request body.
	 * @return \WP_REST_Response
	 */
	private function dispatch_add_item_request( array $body ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( $body );

		return rest_get_server()->dispatch( $request );
	}

	/**
	 * Gets a canonical map of the cart lines that participate in identity.
	 *
	 * @return array<string, array{key: string, product_id: int, variation_id: int, variation: array, quantity: int|float}>
	 */
	private function get_cart_line_identity_map(): array {
		$cart_line_map = array();

		foreach ( WC()->cart->get_cart() as $key => $cart_item ) {
			$variation = $cart_item['variation'] ?? array();
			ksort( $variation );

			$cart_line_map[ $key ] = array(
				'key'          => $key,
				'product_id'   => (int) $cart_item['product_id'],
				'variation_id' => (int) $cart_item['variation_id'],
				'variation'    => $variation,
				'quantity'     => wc_stock_amount( $cart_item['quantity'] ),
			);
		}

		ksort( $cart_line_map );
		return $cart_line_map;
	}

	/**
	 * Test getting cart.
	 */
	public function test_get_item() {
		$this->assertAPIResponse(
			'/wc/store/v1/cart',
			200,
			array(
				'items_count'    => 3,
				'needs_payment'  => true,
				'needs_shipping' => true,
				'items_weight'   => '30',
				'items'          => function ( $value ) {
					return count( $value ) === 2;
				},
				'cross_sells'    => array(
					array(
						'id' => $this->products[2]->get_id(),
					),
				),
				'totals'         => array(
					'currency_code'               => 'USD',
					'currency_minor_unit'         => 2,
					'currency_symbol'             => '$',
					'currency_decimal_separator'  => '.',
					'currency_thousand_separator' => ',',
					'currency_prefix'             => '$',
					'currency_suffix'             => '',
					'total_items'                 => '3000',
					'total_items_tax'             => '0',
					'total_fees'                  => '0',
					'total_fees_tax'              => '0',
					'total_discount'              => '100',
					'total_discount_tax'          => '0',
					'total_shipping'              => '1000',
					'total_shipping_tax'          => '0',
					'total_tax'                   => '0',
					'total_price'                 => '3900',
					'tax_lines'                   => array(),
				),
			)
		);
	}

	/**
	 * Test removing a nonexistent cart item. This should return 409 conflict with updated cart data.
	 */
	public function test_remove_bad_cart_item() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => 'bad_item_key_123',
			)
		);
		$this->assertAPIResponse(
			$request,
			409,
			array(
				'code' => 'woocommerce_rest_cart_invalid_key',
			)
		);
	}

	/**
	 * Test remove cart item.
	 */
	public function test_remove_cart_item() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => $this->keys[0],
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'items_count'  => 1,
				'items'        => function ( $value ) {
					return count( $value ) === 1;
				},
				'items_weight' => '10',
				'totals'       => array(
					'total_items' => '1000',
				),
			)
		);

		// Test removing same item again - should return 404 (item is already removed).
		$this->assertAPIResponse(
			$request,
			409,
			array(
				'code' => 'woocommerce_rest_cart_invalid_key',
			)
		);
	}

	/**
	 * Test changing the quantity of a cart item.
	 */
	public function test_update_item() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 10,
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'items_count' => 11,
				'totals'      => array(
					'total_items' => '11000',
				),
				'items'       => array(
					0 => array(
						'quantity' => 10,
					),
				),
			)
		);
	}

	/**
	 * Test getting updated shipping.
	 */
	public function test_update_customer() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'country' => 'US',
				),
			)
		);

		$action_callback = \Mockery::mock( 'ActionCallback' );
		$action_callback->shouldReceive( 'do_customer_callback' )->once();

		add_action(
			'woocommerce_store_api_cart_update_customer_from_request',
			array(
				$action_callback,
				'do_customer_callback',
			)
		);

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(
					0 => array(
						'destination' => array(
							'address_1' => '',
							'address_2' => '',
							'city'      => '',
							'state'     => '',
							'postcode'  => '',
							'country'   => 'US',
						),
					),
				),
			)
		);

		remove_action(
			'woocommerce_store_api_cart_update_customer_from_request',
			array(
				$action_callback,
				'do_customer_callback',
			)
		);
	}

	/**
	 * Test the shipping topology returned by the Store API.
	 *
	 * @testdox Store API serializes the expected rates and selected method for $scenario.
	 * @dataProvider shipping_topology_provider
	 *
	 * @param string $scenario                 Scenario name.
	 * @param bool   $has_default_rate         Whether the Rest of the World zone has a rate.
	 * @param bool   $has_gb_rate              Whether the GB zone has a rate.
	 * @param bool   $has_pickup               Whether Local Pickup is enabled.
	 * @param bool   $requires_address          Whether rates require a complete address.
	 * @param string $address_key               Address fixture key.
	 * @param array  $expected_rate_keys        Expected rate fixture keys.
	 * @param string $expected_selected_rate_key Expected selected rate fixture key.
	 */
	public function test_shipping_topology(
		string $scenario,
		bool $has_default_rate,
		bool $has_gb_rate,
		bool $has_pickup,
		bool $requires_address,
		string $address_key,
		array $expected_rate_keys,
		string $expected_selected_rate_key
	): void {
		unset( $scenario );

		$option_names = array(
			'woocommerce_default_customer_address',
			'woocommerce_shipping_cost_requires_address',
			'woocommerce_flat_rate_settings',
			'woocommerce_pickup_location_settings',
			'pickup_location_pickup_locations',
		);
		$options      = $this->capture_options( $option_names );
		$customer     = $this->capture_shipping_address();
		$session      = $this->capture_shipping_session();
		$cart_context = WC()->cart->cart_context;
		$country_data = get_object_vars( WC()->countries );
		$has_locale   = array_key_exists( 'locale', $country_data );
		$locale       = $has_locale ? $country_data['locale'] : array();
		$fixtures     = new FixtureData();
		$topology     = array();

		try {
			unset( WC()->countries->locale );
			$this->configure_shipping_topology( $fixtures, $has_default_rate, $has_gb_rate, $has_pickup, $topology );

			update_option( 'woocommerce_default_customer_address', 'blank' === $address_key ? '' : 'base' );
			update_option( 'woocommerce_shipping_cost_requires_address', $requires_address ? 'yes' : 'no' );
			WC()->cart->cart_context = 'store-api';
			$this->clear_shipping_session();

			$address = $this->get_shipping_address_fixture( $address_key );
			$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
			$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
			$request->set_body_params( array( 'shipping_address' => (object) $address ) );
			$response = rest_get_server()->dispatch( $request );
			$data     = $response->get_data();

			$this->assertSame( 200, $response->get_status(), 'The customer update should succeed.' );
			$this->assertTrue( $data['needs_shipping'], 'The cart should continue to require shipping.' );

			$expected_package_count = $requires_address && 'blank' === $address_key && ! $has_pickup ? 0 : 1;
			$this->assertCount( $expected_package_count, $data['shipping_rates'], 'The response should serialize the exact expected package count.' );

			if ( 0 === $expected_package_count ) {
				$this->assertSame( array(), WC()->session->get( 'chosen_shipping_methods', array() ), 'No hidden package should choose a shipping method.' );
				return;
			}

			$package              = (array) $data['shipping_rates'][0];
			$destination          = (array) $package['destination'];
			$rates                = array_map(
				function ( $rate ): array {
					return (array) $rate;
				},
				$package['shipping_rates']
			);
			$expected_destination = array_intersect_key(
				$address,
				array_flip( array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) )
			);

			$this->assertSame( $expected_destination, $destination, 'The serialized package destination should match the submitted address exactly.' );

			$expected_rate_ids = array_map(
				function ( string $key ) use ( $topology ): string {
					return $topology['rate_ids'][ $key ];
				},
				$expected_rate_keys
			);
			$actual_rate_ids   = array_column( $rates, 'rate_id' );
			sort( $expected_rate_ids );
			sort( $actual_rate_ids );

			$this->assertSame( $expected_rate_ids, $actual_rate_ids, 'The response should contain only the expected concrete rates.' );

			$expected_method_ids = array_map(
				function ( string $key ) use ( $topology ): string {
					return $topology['method_ids'][ $key ];
				},
				$expected_rate_keys
			);
			$actual_method_ids   = array_column( $rates, 'method_id' );
			sort( $expected_method_ids );
			sort( $actual_method_ids );

			$this->assertSame( $expected_method_ids, $actual_method_ids, 'The response should contain only the expected shipping method types.' );

			$selected_by_rate = array_column( $rates, 'selected', 'rate_id' );
			foreach ( $expected_rate_ids as $rate_id ) {
				$this->assertSame(
					$expected_selected_rate_key && $topology['rate_ids'][ $expected_selected_rate_key ] === $rate_id,
					$selected_by_rate[ $rate_id ],
					"The selected flag should be exact for {$rate_id}."
				);
			}

			$chosen_methods  = WC()->session->get( 'chosen_shipping_methods', array() );
			$expected_chosen = $expected_selected_rate_key ? $topology['rate_ids'][ $expected_selected_rate_key ] : '';
			$this->assertIsArray( $chosen_methods, 'A serialized package should persist indexed shipping choices.' );
			$this->assertArrayHasKey( 0, $chosen_methods, 'A serialized package should persist a choice for package zero.' );
			$this->assertSame( $expected_chosen, $chosen_methods[0], 'The session should persist the same method selected in the response.' );
		} finally {
			$this->remove_shipping_topology( $fixtures, $topology );
			$this->restore_options( $options );
			$this->restore_shipping_address( $customer );
			WC()->shipping()->reset_shipping();
			$this->restore_shipping_session( $session );
			WC()->cart->cart_context = $cart_context;
			if ( $has_locale ) {
				WC()->countries->locale = $locale;
			} else {
				unset( WC()->countries->locale );
			}
			\WC_Cache_Helper::get_transient_version( 'shipping', true );
			delete_transient( 'wc_shipping_method_count' );
		}
	}

	/**
	 * The topology owner restores both present and missing incoming session values.
	 *
	 * @testdox Shipping topology restores exact incoming shipping and customer session values.
	 */
	public function test_shipping_topology_restores_existing_session_state(): void {
		$keys     = array( 'chosen_shipping_methods', 'customer', 'shipping_method_counts' );
		$original = array();

		foreach ( $keys as $key ) {
			$original[ $key ] = array(
				'exists' => isset( WC()->session->{$key} ),
				'value'  => WC()->session->get( $key ),
			);
		}

		try {
			$chosen_methods = array( 'external_shipping:7' );
			$customer       = array( 'shipping_country' => 'SE' );
			WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
			WC()->session->set( 'customer', $customer );
			WC()->session->set( 'shipping_method_counts', null );

			$this->test_shipping_topology(
				'session restoration',
				false,
				true,
				true,
				true,
				'blank',
				array( 'pickup' ),
				''
			);

			$this->assertTrue( isset( WC()->session->chosen_shipping_methods ), 'The incoming chosen-method key should remain present.' );
			$this->assertSame( $chosen_methods, WC()->session->get( 'chosen_shipping_methods' ), 'The incoming chosen methods should be restored exactly.' );
			$this->assertTrue( isset( WC()->session->customer ), 'The incoming customer key should remain present.' );
			$this->assertSame( $customer, WC()->session->get( 'customer' ), 'The incoming customer session value should be restored exactly.' );
			$this->assertFalse( isset( WC()->session->shipping_method_counts ), 'An incoming missing session key should remain missing.' );
		} finally {
			foreach ( $original as $key => $session_value ) {
				WC()->session->set( $key, $session_value['exists'] ? $session_value['value'] : null );
			}
		}
	}

	/**
	 * Shipping topology scenarios.
	 *
	 * @return array<string, array{string, bool, bool, bool, bool, string, string[], string}>
	 */
	public function shipping_topology_provider(): array {
		return array(
			'default and any rates with pickup'       => array( 'default and any rates with pickup', true, true, true, false, 'us', array( 'default', 'pickup' ), 'default' ),
			'default and any rates without pickup'    => array( 'default and any rates without pickup', true, true, false, false, 'us', array( 'default' ), 'default' ),
			'default rate only'                       => array( 'default rate only', true, false, false, false, 'us', array( 'default' ), 'default' ),
			'any rate only before address'            => array( 'any rate only before address', false, true, false, false, 'blank', array(), '' ),
			'any rate only for a nonmatching address' => array( 'any rate only for a nonmatching address', false, true, false, false, 'us', array(), '' ),
			'any rate only for a matching address'    => array( 'any rate only for a matching address', false, true, false, false, 'gb', array( 'gb' ), 'gb' ),
			'pickup only without required address'    => array( 'pickup only without required address', false, false, true, false, 'blank', array( 'pickup' ), 'pickup' ),
			'pickup only with required address'       => array( 'pickup only with required address', false, true, true, true, 'blank', array( 'pickup' ), '' ),
			'no rates without required address'       => array( 'no rates without required address', false, false, false, false, 'blank', array(), '' ),
			'no rates with required address'          => array( 'no rates with required address', false, false, false, true, 'blank', array(), '' ),
			'required address before entry'           => array( 'required address before entry', true, true, false, true, 'blank', array(), '' ),
			'required address with default rate'      => array( 'required address with default rate', true, true, false, true, 'us', array( 'default' ), 'default' ),
			'required address with any rate'          => array( 'required address with any rate', true, true, false, true, 'gb', array( 'gb' ), 'gb' ),
		);
	}

	/**
	 * Configure real zones and shipping methods for a topology scenario.
	 *
	 * @param FixtureData $fixtures         Fixture helper.
	 * @param bool        $has_default_rate Whether the default zone gets a rate.
	 * @param bool        $has_gb_rate      Whether the GB zone gets a rate.
	 * @param bool        $has_pickup       Whether pickup is enabled.
	 * @param array       $topology         Created topology data, updated as records are added.
	 */
	private function configure_shipping_topology( FixtureData $fixtures, bool $has_default_rate, bool $has_gb_rate, bool $has_pickup, array &$topology ): void {
		update_option( 'woocommerce_flat_rate_settings', array( 'enabled' => 'no' ) );

		$topology = array(
			'rate_ids'   => array( 'pickup' => 'pickup_location:0' ),
			'method_ids' => array(
				'default' => 'flat_rate',
				'gb'      => 'flat_rate',
				'pickup'  => 'pickup_location',
			),
		);

		if ( $has_default_rate ) {
			$default_zone = \WC_Shipping_Zones::get_zone( 0 );
			if ( ! $default_zone instanceof \WC_Shipping_Zone ) {
				throw new \RuntimeException( 'The default shipping zone should be available.' );
			}

			$topology['default_instance_id'] = $default_zone->add_shipping_method( 'flat_rate' );
			$topology['rate_ids']['default'] = 'flat_rate:' . $topology['default_instance_id'];
			update_option(
				'woocommerce_flat_rate_' . $topology['default_instance_id'] . '_settings',
				array(
					'enabled'    => 'yes',
					'title'      => 'Default delivery',
					'tax_status' => 'none',
					'cost'       => '10',
				)
			);
		}

		if ( $has_gb_rate ) {
			$gb_zone = new \WC_Shipping_Zone();
			$gb_zone->set_zone_name( 'United Kingdom' );
			$gb_zone->set_zone_locations(
				array(
					(object) array(
						'code' => 'GB',
						'type' => 'country',
					),
				)
			);
			$gb_zone->save();
			$topology['gb_zone']        = $gb_zone;
			$topology['gb_instance_id'] = $gb_zone->add_shipping_method( 'flat_rate' );
			$topology['rate_ids']['gb'] = 'flat_rate:' . $topology['gb_instance_id'];
			update_option(
				'woocommerce_flat_rate_' . $topology['gb_instance_id'] . '_settings',
				array(
					'enabled'    => 'yes',
					'title'      => 'UK delivery',
					'tax_status' => 'none',
					'cost'       => '15',
				)
			);
		}

		if ( $has_pickup ) {
			$fixtures->shipping_add_pickup_location();
			$topology['pickup_registered'] = true;
		}

		WC()->shipping()->reset_shipping();
		\WC_Cache_Helper::get_transient_version( 'shipping', true );
		delete_transient( 'wc_shipping_method_count' );
	}

	/**
	 * Remove topology records created by a scenario.
	 *
	 * @param FixtureData $fixtures Fixture helper.
	 * @param array       $topology Created topology data.
	 */
	private function remove_shipping_topology( FixtureData $fixtures, array $topology ): void {
		if ( isset( $topology['default_instance_id'] ) ) {
			$default_zone = \WC_Shipping_Zones::get_zone( 0 );
			if ( $default_zone instanceof \WC_Shipping_Zone ) {
				$default_zone->delete_shipping_method( $topology['default_instance_id'] );
			}
		}

		if ( isset( $topology['gb_zone'] ) && $topology['gb_zone'] instanceof \WC_Shipping_Zone ) {
			$topology['gb_zone']->delete( true );
		}

		if ( ! empty( $topology['pickup_registered'] ) ) {
			$fixtures->shipping_remove_pickup_location();
		}
	}

	/**
	 * Get an exact address fixture for a topology scenario.
	 *
	 * @param string $key Fixture key.
	 * @return array<string, string>
	 */
	private function get_shipping_address_fixture( string $key ): array {
		$addresses = array(
			'blank' => array(
				'first_name' => '',
				'last_name'  => '',
				'company'    => '',
				'address_1'  => '',
				'address_2'  => '',
				'city'       => '',
				'state'      => '',
				'postcode'   => '',
				'country'    => '',
			),
			'us'    => array(
				'first_name' => 'Pat',
				'last_name'  => 'Shopper',
				'company'    => '',
				'address_1'  => '60 29th Street',
				'address_2'  => '',
				'city'       => 'San Francisco',
				'state'      => 'CA',
				'postcode'   => '94110',
				'country'    => 'US',
			),
			'gb'    => array(
				'first_name' => 'Pat',
				'last_name'  => 'Shopper',
				'company'    => '',
				'address_1'  => '1 New Change',
				'address_2'  => '',
				'city'       => 'London',
				'state'      => '',
				'postcode'   => 'EC4M 9AF',
				'country'    => 'GB',
			),
		);

		return $addresses[ $key ];
	}

	/**
	 * Capture option values without turning missing options into stored values.
	 *
	 * @param string[] $option_names Option names.
	 * @return array<string, array{exists: bool, value: mixed}>
	 */
	private function capture_options( array $option_names ): array {
		$options = array();
		$missing = new \stdClass();

		foreach ( $option_names as $option_name ) {
			$value                   = get_option( $option_name, $missing );
			$options[ $option_name ] = array(
				'exists' => $missing !== $value,
				'value'  => $value,
			);
		}

		return $options;
	}

	/**
	 * Restore captured options.
	 *
	 * @param array<string, array{exists: bool, value: mixed}> $options Captured options.
	 */
	private function restore_options( array $options ): void {
		foreach ( $options as $option_name => $option ) {
			if ( $option['exists'] ) {
				update_option( $option_name, $option['value'] );
			} else {
				delete_option( $option_name );
			}
		}
	}

	/**
	 * Capture the current shipping address.
	 *
	 * @return array<string, string>
	 */
	private function capture_shipping_address(): array {
		$address = array();
		foreach ( array( 'first_name', 'last_name', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ) as $field ) {
			$getter            = 'get_shipping_' . $field;
			$address[ $field ] = WC()->customer->$getter();
		}

		return $address;
	}

	/**
	 * Restore a shipping address.
	 *
	 * @param array<string, string> $address Shipping address.
	 */
	private function restore_shipping_address( array $address ): void {
		foreach ( $address as $field => $value ) {
			$setter = 'set_shipping_' . $field;
			WC()->customer->$setter( $value );
		}
	}

	/**
	 * Capture session values involved in shipping selection and caching.
	 *
	 * @return array<string, array{exists: bool, value: mixed}>
	 */
	private function capture_shipping_session(): array {
		$session = array();
		foreach ( array( 'chosen_shipping_methods', 'shipping_method_counts', 'previous_shipping_methods', 'shipping_for_package_0', 'customer' ) as $key ) {
			$session[ $key ] = array(
				'exists' => isset( WC()->session->{$key} ),
				'value'  => WC()->session->get( $key ),
			);
		}

		return $session;
	}

	/**
	 * Clear session values involved in shipping selection and caching.
	 */
	private function clear_shipping_session(): void {
		foreach ( array( 'chosen_shipping_methods', 'shipping_method_counts', 'previous_shipping_methods', 'shipping_for_package_0' ) as $key ) {
			WC()->session->set( $key, null );
		}
		WC()->shipping()->reset_shipping();
	}

	/**
	 * Restore shipping session values.
	 *
	 * @param array<string, array{exists: bool, value: mixed}> $session Session values.
	 */
	private function restore_shipping_session( array $session ): void {
		foreach ( $session as $key => $session_value ) {
			WC()->session->set( $key, $session_value['exists'] ? $session_value['value'] : null );
		}
	}

	/**
	 * Test shipping address validation.
	 */
	public function test_update_customer_address() {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => '90210',
					'country'    => 'US',
				),
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(
					0 => array(
						'destination' => array(
							'address_1' => 'Test address 1',
							'address_2' => 'Test address 2',
							'city'      => 'Test City',
							'state'     => 'AL',
							'postcode'  => '90210',
							'country'   => 'US',
						),
					),
				),
			)
		);

		// Address with invalid country.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => '90210',
					'country'    => 'ZZZZZZZZ',
				),
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);

		// US address with named state.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'Alabama',
					'postcode'   => '90210',
					'country'    => 'US',
				),
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(
					0 => array(
						'destination' => array(
							'state'   => 'AL',
							'country' => 'US',
						),
					),
				),
			)
		);

		// US address with invalid state.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'ZZZZZZZZ',
					'postcode'   => '90210',
					'country'    => 'US',
				),
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);

		// US address with invalid postcode.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'shipping_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => 'ABCDE',
					'country'    => 'US',
				),
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);
	}

	/**
	 * Test updating customer with a virtual cart only, this should test the address copying functionality.
	 */
	public function test_update_customer_virtual_cart() {
		// Add a virtual item to cart.
		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[3]->get_id() );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-customer' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'billing_address' => (object) array(
					'first_name' => 'Han',
					'last_name'  => 'Solo',
					'address_1'  => 'Test address 1',
					'address_2'  => 'Test address 2',
					'city'       => 'Test City',
					'state'      => 'AL',
					'postcode'   => '90210',
					'country'    => 'US',
					'email'      => 'testaccount@test.com',
				),
			)
		);

		$this->assertAPIResponse(
			$request,
			200,
			array(
				'shipping_rates' => array(),
			)
		);
		// Restore cart for other tests.
		wc_empty_cart();
		$this->keys   = array();
		$this->keys[] = wc()->cart->add_to_cart( $this->products[0]->get_id(), 2 );
		$this->keys[] = wc()->cart->add_to_cart( $this->products[1]->get_id() );
		wc()->cart->apply_coupon( $this->coupon->get_code() );
	}

	/**
	 * Test applying coupon to cart.
	 */
	public function test_apply_coupon() {
		wc()->cart->remove_coupon( $this->coupon->get_code() );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'totals' => array(
					'total_discount' => '100',
				),
			)
		);

		$fixtures = new FixtureData();

		// Test coupons with different case.
		$newcoupon = $fixtures->get_coupon( array( 'code' => 'testCoupon' ) );
		$request   = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'testCoupon',
			)
		);
		$this->assertAPIResponse(
			$request,
			200
		);

		// Test coupons with special chars in the code.
		$newcoupon = $fixtures->get_coupon( array( 'code' => '$5 off' ) );
		$request   = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/apply-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => '$5 off',
			)
		);
		$this->assertAPIResponse(
			$request,
			200
		);
	}

	/**
	 * Test removing coupon from cart.
	 */
	public function test_remove_coupon() {
		// Invalid coupon.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => 'doesnotexist',
			)
		);
		$this->assertAPIResponse(
			$request,
			400
		);

		// Applied coupon.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-coupon' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'code' => $this->coupon->get_code(),
			)
		);
		$this->assertAPIResponse(
			$request,
			200,
			array(
				'totals' => array(
					'total_discount' => '0',
				),
			)
		);
	}

	/**
	 * Test conversion of cart item to rest response.
	 */
	public function test_prepare_item() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart', 'v1' );
		$cart       = wc()->cart;
		$response   = $controller->prepare_item_for_response( $cart, new \WP_REST_Request() );
		$data       = $response->get_data();

		$this->assertArrayHasKey( 'items_count', $data );
		$this->assertArrayHasKey( 'items', $data );
		$this->assertArrayHasKey( 'shipping_rates', $data );
		$this->assertArrayHasKey( 'coupons', $data );
		$this->assertArrayHasKey( 'needs_payment', $data );
		$this->assertArrayHasKey( 'needs_shipping', $data );
		$this->assertArrayHasKey( 'items_weight', $data );
		$this->assertArrayHasKey( 'totals', $data );
		$this->assertArrayHasKey( 'cross_sells', $data );
	}

	/**
	 * Test schema matches responses.
	 */
	public function test_get_item_schema() {
		$routes     = new \Automattic\WooCommerce\StoreApi\RoutesController( new \Automattic\WooCommerce\StoreApi\SchemaController( $this->mock_extend ) );
		$controller = $routes->get( 'cart', 'v1' );
		$cart       = wc()->cart;
		$response   = $controller->prepare_item_for_response( $cart, new \WP_REST_Request() );
		$schema     = $controller->get_item_schema();
		$validate   = new ValidateSchema( $schema );

		$diff = $validate->get_diff_from_object( $response->get_data() );
		$this->assertEmpty( $diff );
	}

	/**
	 * Tests for Cart-Token header presence and validity.
	 */
	public function test_cart_token_header() {

		/** @var Spy_REST_Server $server */
		$server = rest_get_server();

		$server->serve_request( '/wc/store/cart' );

		$this->assertArrayHasKey( 'Cart-Token', $server->sent_headers );

		$this->assertTrue(
			JsonWebToken::validate(
				$server->sent_headers['Cart-Token'],
				'@' . wp_salt()
			)
		);
	}

	/**
	 * Test Store API uses SessionHandler when Cart-Token is present via REQUEST_URI.
	 */
	public function test_store_api_uses_session_handler_for_cart_token() {
		$customer_id = (string) wc()->session->get_customer_id();
		$token       = CartTokenUtils::get_cart_token( $customer_id );

		// Preserve globals.
		$old_server = $_SERVER;

		try {
			// Simulate a Store API request with valid Cart-Token.
			$_SERVER['REQUEST_URI']     = '/' . rest_get_url_prefix() . '/wc/store/v1/cart';
			$_SERVER['HTTP_CART_TOKEN'] = $token;

			$authentication = new Authentication();
			$result         = $authentication->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

			$this->assertSame( SessionHandler::class, $result );
		} finally {
			// Restore globals.
			$_SERVER = $old_server;
		}
	}

	/**
	 * Test Store API uses SessionHandler when rest_route GET parameter is present.
	 */
	public function test_rest_route_get_parameter_uses_store_api_session_handler() {
		$customer_id = (string) wc()->session->get_customer_id();
		$token       = CartTokenUtils::get_cart_token( $customer_id );

		// Preserve globals.
		$old_get    = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test context.
		$old_server = $_SERVER;

		try {
			// Simulate a Store API request via GET parameter with valid Cart-Token.
			$_GET['rest_route']         = '/wc/store/v1/cart';
			$_SERVER['HTTP_CART_TOKEN'] = $token;

			$authentication = new Authentication();
			$result         = $authentication->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

			$this->assertSame( SessionHandler::class, $result );
		} finally {
			// Restore globals.
			$_GET    = $old_get;
			$_SERVER = $old_server;
		}
	}

	/**
	 * Test non-Store API routes do not switch to Store API session handler.
	 */
	public function test_non_store_api_route_does_not_use_store_api_session_handler() {
		$customer_id = (string) wc()->session->get_customer_id();
		$token       = CartTokenUtils::get_cart_token( $customer_id );

		// Preserve globals.
		$old_server = $_SERVER;

		try {
			// Simulate a non-Store API request (even with valid Cart-Token).
			$_SERVER['REQUEST_URI']     = '/' . rest_get_url_prefix() . '/wp/v2/posts';
			$_SERVER['HTTP_CART_TOKEN'] = $token;

			$authentication = new Authentication();
			$result         = $authentication->maybe_use_store_api_session_handler( 'WC_Session_Handler' );

			// Should return the default handler for non-Store API routes.
			$this->assertSame( 'WC_Session_Handler', $result );
		} finally {
			// Restore globals.
			$_SERVER = $old_server;
		}
	}

	/**
	 * Nested Cart-Token should be ignored in a batch request.
	 */
	public function test_batch_sub_request_cart_token_does_not_waive_nonce() {
		$token = CartTokenUtils::get_cart_token( (string) wc()->session->get_customer_id() );

		// Preserve globals.
		$old_server = $_SERVER;

		try {
			$_SERVER['REQUEST_URI'] = '/' . rest_get_url_prefix() . '/wc/store/v1/batch';
			unset( $_SERVER['HTTP_CART_TOKEN'] );

			$request = new \WP_REST_Request( 'POST', '/wc/store/v1/batch' );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body(
				wp_json_encode(
					array(
						'requests' => array(
							array(
								'method'  => 'POST',
								'path'    => '/wc/store/v1/cart/update-customer',
								'headers' => array( 'Cart-Token' => $token ),
								'body'    => array( 'billing_address' => array( 'first_name' => 'Nonce-free' ) ),
							),
						),
					)
				)
			);

			$response = rest_get_server()->dispatch( $request );
			$data     = $response->get_data();

			$this->assertSame( 401, $data['responses'][0]['status'] ?? null, 'A sub-request token must not waive the nonce check.' );
		} finally {
			// Restore globals.
			$_SERVER = $old_server;
		}
	}

	/**
	 * Test that cart GET endpoint sends Cache-Control headers.
	 */
	public function test_cart_get_endpoint_cache_control_headers() {
		/** @var Spy_REST_Server $server */
		$server = rest_get_server();

		$server->serve_request( '/wc/store/cart' );

		$this->assertArrayHasKey( 'Cache-Control', $server->sent_headers );
		$this->assertStringContainsString( 'no-store', $server->sent_headers['Cache-Control'] );
	}

	/**
	 * Test that cart endpoint returns fresh data.
	 */
	public function test_cart_get_endpoint_returns_fresh_data() {
		wc_empty_cart();

		/** @var Spy_REST_Server $server */
		$server = rest_get_server();

		$server->serve_request( '/wc/store/cart' );
		$first_response = json_decode( $server->sent_body, true );
		$this->assertEquals( 0, $first_response['items_count'] );
		$this->assertEmpty( $first_response['items'] );

		wc()->cart->add_to_cart( $this->products[0]->get_id(), 1 );

		$server->serve_request( '/wc/store/cart' );
		$second_response = json_decode( $server->sent_body, true );
		$this->assertEquals( 1, $second_response['items_count'] );
		$this->assertCount( 1, $second_response['items'] );
		$this->assertEquals( $this->products[0]->get_id(), $second_response['items'][0]['id'] );

		$this->assertArrayHasKey( 'Cache-Control', $server->sent_headers );
		$this->assertStringContainsString( 'no-store', $server->sent_headers['Cache-Control'] );
	}

	/**
	 * Test adding a variable product to cart returns proper variation data.
	 */
	public function test_add_variable_product_to_cart_returns_variation_data() {
		wc_empty_cart();

		$fixtures = new FixtureData();

		$variable_product = $fixtures->get_variable_product(
			array(
				'name'          => 'Test Variable Product 4',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
				'weight'        => 10,
			),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'green', 'blue' ) ),
				$fixtures->get_product_attribute( 'size', array( 'small', 'medium', 'large' ) ),
			)
		);

		$variable_product->save();

		$variation = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array(
				'pa_color' => 'red',
				'pa_size'  => 'small',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		$request->set_body_params(
			array(
				'id'        => $variation->get_id(),
				'quantity'  => 1,
				'variation' => array( // intentionally alphabetically disordered.
					array(
						'attribute' => 'pa_color',
						'value'     => 'red',
					),
					array(
						'attribute' => 'pa_size',
						'value'     => 'small',
					),
				),
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'items' => array(
					array(
						'variation' => array( // order matters, alphabetical attribute order.
							array(
								'attribute' => 'color',
								'value'     => 'red',
							),
							array(
								'attribute' => 'size',
								'value'     => 'small',
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Test adding a variable product to cart with attribute_* attributes.
	 */
	public function test_add_variable_product_to_cart_with_attribute_data() {
		wc_empty_cart();

		$fixtures = new FixtureData();

		$variable_product = $fixtures->get_variable_product(
			array(
				'name'          => 'Test Variable Product with special characters',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
				'weight'        => 10,
			),
			array(
				// this will create a "taxonomy"/"global" attribute.
				$fixtures->get_product_attribute( 'Size', array( 'Small 🤏', 'Medium' ) ),
				// this will create a "local" attribute.
				[
					'attribute_id'       => 0,
					'attribute_taxonomy' => 'Autograph choice ✏️',
					'term_ids'           => [ 'Yes 👍', 'No 👎' ],
				],
			)
		);

		$variation = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array(
				// if we need to create a variation product with a taxonomy attribute using special chars,
				// we need to use their encoded values.
				'pa_size'                             => 'small-%f0%9f%a4%8f',
				'autograph-choice-%e2%9c%8f%ef%b8%8f' => 'Yes 👍',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		$request->set_body_params(
			array(
				'id'        => $variation->get_id(),
				'quantity'  => 1,
				'variation' => array(
					array(
						'attribute' => 'pa_size',
						'value'     => 'Small 🤏',
					),
					array(
						'attribute' => 'attribute_autograph-choice-%e2%9c%8f%ef%b8%8f',
						'value'     => 'Yes 👍',
					),
				),
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'items' => array(
					array(
						'variation' => array(
							array(
								'attribute' => 'Autograph choice ✏️',
								'value'     => 'Yes 👍',
							),
							array(
								'attribute' => 'Size',
								'value'     => 'small-%f0%9f%a4%8f',
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Test adding a variable product that doesn't exist to cart with attribute_* attributes.
	 */
	public function test_fails_add_variable_product_to_cart_with_wrong_attribute_data() {
		wc_empty_cart();

		$fixtures = new FixtureData();

		$variable_product = $fixtures->get_variable_product(
			array(
				'name'          => 'Test Variable Product with special characters',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
				'weight'        => 10,
			),
			array(
				[
					'attribute_id'       => 0,
					'attribute_taxonomy' => 'Autograph choice ✏️',
					'term_ids'           => [ 'Yes 👍', 'No 👎' ],
				],
			)
		);

		$variation = $fixtures->get_variation_product(
			$variable_product->get_id(),
			array(
				'autograph-choice-%e2%9c%8f%ef%b8%8f' => 'No 👎',
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );

		$request->set_body_params(
			array(
				'id'        => $variation->get_id(),
				'quantity'  => 1,
				'variation' => array(
					array(
						// purposefully using the wrong attribute value, here.
						'attribute' => 'attribute_autograph-choice-%e2%9c%8f%ef%b8%8f',
						'value'     => 'Yes 👍',
					),
				),
			)
		);

		$this->assertAPIResponse(
			$request,
			400
		);
	}

	/**
	 * @testdox Re-adding a standalone product preserves its cart-line key and increments its quantity.
	 */
	public function test_add_item_preserves_standalone_cart_line_identity(): void {
		wc_empty_cart();

		$first_response = $this->dispatch_add_item_request(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 1,
			)
		);
		$first_data     = $first_response->get_data();

		$this->assertSame( 201, $first_response->get_status(), 'The first standalone add should succeed.' );
		$this->assertCount( 1, $first_data['items'], 'The first add should create exactly one cart line.' );
		$cart_item_key = $first_data['items'][0]['key'];
		$this->assertSame(
			array(
				$cart_item_key => array(
					'key'          => $cart_item_key,
					'product_id'   => $this->products[0]->get_id(),
					'variation_id' => 0,
					'variation'    => array(),
					'quantity'     => 1,
				),
			),
			$this->get_cart_line_identity_map(),
			'The first add should create the expected standalone cart line.'
		);

		$second_response = $this->dispatch_add_item_request(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 1,
			)
		);
		$second_data     = $second_response->get_data();

		$this->assertSame( 201, $second_response->get_status(), 'The standalone re-add should succeed.' );
		$this->assertCount( 1, $second_data['items'], 'The re-add should not create another cart line.' );
		$this->assertSame( $cart_item_key, $second_data['items'][0]['key'], 'The re-add should preserve the server cart-line key.' );
		$this->assertSame(
			array(
				$cart_item_key => array(
					'key'          => $cart_item_key,
					'product_id'   => $this->products[0]->get_id(),
					'variation_id' => 0,
					'variation'    => array(),
					'quantity'     => 2,
				),
			),
			$this->get_cart_line_identity_map(),
			'The re-add should increment only the existing standalone line.'
		);
	}

	/**
	 * @testdox Cart item data keeps a same-product meta line distinct from the standalone line.
	 */
	public function test_add_item_preserves_cart_item_data_identity(): void {
		wc_empty_cart();

		$add_cart_item_data = function ( $add_to_cart_data, $request ) {
			if ( 'meta-line' === $request->get_param( 'cart_line_identity_marker' ) ) {
				$add_to_cart_data['cart_item_data']['_cart_line_identity'] = 'meta-line';
			}
			return $add_to_cart_data;
		};
		add_filter( 'woocommerce_store_api_add_to_cart_data', $add_cart_item_data, 10, 2 );

		try {
			$meta_response = $this->dispatch_add_item_request(
				array(
					'id'                        => $this->products[0]->get_id(),
					'quantity'                  => 1,
					'cart_line_identity_marker' => 'meta-line',
				)
			);
			$meta_map      = $this->get_cart_line_identity_map();
			$meta_key      = array_key_first( $meta_map );

			$this->assertSame( 201, $meta_response->get_status(), 'The marker-scoped meta-line add should succeed.' );
			$this->assertCount( 1, $meta_map, 'The first request should create exactly the meta line.' );

			$plain_response = $this->dispatch_add_item_request(
				array(
					'id'       => $this->products[0]->get_id(),
					'quantity' => 1,
				)
			);
			$mixed_map      = $this->get_cart_line_identity_map();
			$plain_keys     = array_values( array_diff( array_keys( $mixed_map ), array( $meta_key ) ) );

			$this->assertSame( 201, $plain_response->get_status(), 'The first plain add should succeed.' );
			$this->assertCount( 1, $plain_keys, 'The plain add should create one distinct standalone key.' );
			$plain_key = $plain_keys[0];
			$expected  = array(
				$meta_key  => array(
					'key'          => $meta_key,
					'product_id'   => $this->products[0]->get_id(),
					'variation_id' => 0,
					'variation'    => array(),
					'quantity'     => 1,
				),
				$plain_key => array(
					'key'          => $plain_key,
					'product_id'   => $this->products[0]->get_id(),
					'variation_id' => 0,
					'variation'    => array(),
					'quantity'     => 1,
				),
			);
			ksort( $expected );
			$this->assertSame( $expected, $mixed_map, 'The meta and standalone lines should remain distinct at quantity one.' );

			$second_plain_response              = $this->dispatch_add_item_request(
				array(
					'id'       => $this->products[0]->get_id(),
					'quantity' => 1,
				)
			);
			$expected[ $plain_key ]['quantity'] = 2;

			$this->assertSame( 201, $second_plain_response->get_status(), 'The standalone re-add should succeed.' );
			$this->assertSame( $expected, $this->get_cart_line_identity_map(), 'Only the standalone line should increment.' );
		} finally {
			remove_filter( 'woocommerce_store_api_add_to_cart_data', $add_cart_item_data, 10 );
		}
	}

	/**
	 * @testdox Re-adding one variation preserves its key while another variation creates a distinct line.
	 */
	public function test_add_item_preserves_variation_identity(): void {
		wc_empty_cart();

		$fixtures         = new FixtureData();
		$variable_product = $fixtures->get_variable_product(
			array(
				'name'          => 'Cart line identity variable product',
				'stock_status'  => ProductStockStatus::IN_STOCK,
				'regular_price' => 10,
			),
			array(
				$fixtures->get_product_attribute( 'color', array( 'red', 'blue' ) ),
			)
		);
		$red_variation    = $fixtures->get_variation_product( $variable_product->get_id(), array( 'pa_color' => 'red' ) );
		$blue_variation   = $fixtures->get_variation_product( $variable_product->get_id(), array( 'pa_color' => 'blue' ) );

		$red_body       = array(
			'id'        => $red_variation->get_id(),
			'quantity'  => 1,
			'variation' => array(
				array(
					'attribute' => 'pa_color',
					'value'     => 'red',
				),
			),
		);
		$first_response = $this->dispatch_add_item_request( $red_body );
		$first_map      = $this->get_cart_line_identity_map();
		$red_key        = array_key_first( $first_map );

		$this->assertSame( 201, $first_response->get_status(), 'The first variation add should succeed.' );
		$this->assertCount( 1, $first_map, 'The first variation add should create exactly one line.' );

		$second_response = $this->dispatch_add_item_request( $red_body );
		$second_map      = $this->get_cart_line_identity_map();

		$this->assertSame( 201, $second_response->get_status(), 'Re-adding the same variation should succeed.' );
		$this->assertSame( array( $red_key ), array_keys( $second_map ), 'The same variation should preserve its cart-line key.' );
		$this->assertSame( 2, $second_map[ $red_key ]['quantity'], 'The same variation should increment its line.' );

		$blue_response = $this->dispatch_add_item_request(
			array(
				'id'        => $blue_variation->get_id(),
				'quantity'  => 1,
				'variation' => array(
					array(
						'attribute' => 'pa_color',
						'value'     => 'blue',
					),
				),
			)
		);
		$final_map     = $this->get_cart_line_identity_map();
		$blue_keys     = array_values( array_diff( array_keys( $final_map ), array( $red_key ) ) );

		$this->assertSame( 201, $blue_response->get_status(), 'Adding a different variation should succeed.' );
		$this->assertCount( 1, $blue_keys, 'The different variation should create one distinct key.' );
		$blue_key = $blue_keys[0];
		$this->assertSame( $red_variation->get_id(), $final_map[ $red_key ]['variation_id'], 'The original key should still identify the red variation.' );
		$this->assertSame( 2, $final_map[ $red_key ]['quantity'], 'The red variation quantity should remain two.' );
		$this->assertSame( $blue_variation->get_id(), $final_map[ $blue_key ]['variation_id'], 'The new key should identify the blue variation.' );
		$this->assertSame( 1, $final_map[ $blue_key ]['quantity'], 'The blue variation should start at quantity one.' );
	}

	/**
	 * @testdox A rejected sold-individually re-add leaves the exact cart-line map unchanged.
	 */
	public function test_rejected_add_item_leaves_sold_individually_cart_unchanged(): void {
		wc_empty_cart();

		$product                    = $this->products[0];
		$original_sold_individually = $product->get_sold_individually();
		$product->set_sold_individually( true );
		$product->save();

		try {
			$first_response  = $this->dispatch_add_item_request(
				array(
					'id'       => $product->get_id(),
					'quantity' => 1,
				)
			);
			$before          = $this->get_cart_line_identity_map();
			$second_response = $this->dispatch_add_item_request(
				array(
					'id'       => $product->get_id(),
					'quantity' => 1,
				)
			);
			$second_data     = $second_response->get_data();

			$this->assertSame( 201, $first_response->get_status(), 'The first sold-individually add should succeed.' );
			$this->assertSame( 400, $second_response->get_status(), 'The sold-individually re-add should be rejected.' );
			$this->assertSame( 'readonly_quantity', $second_data['code'], 'The rejection should report the Store API quantity-limit code.' );
			$this->assertSame( $before, $this->get_cart_line_identity_map(), 'The rejected re-add should leave every cart line unchanged.' );
		} finally {
			$product->set_sold_individually( $original_sold_individually );
			$product->save();
		}
	}

	/**
	 * @testdox A rejected managed-stock re-add leaves the exact cart-line map unchanged.
	 */
	public function test_rejected_add_item_leaves_managed_stock_cart_unchanged(): void {
		wc_empty_cart();

		$product                 = $this->products[1];
		$original_manage_stock   = $product->get_manage_stock();
		$original_stock_quantity = $product->get_stock_quantity();
		$original_backorders     = $product->get_backorders();
		$original_stock_status   = $product->get_stock_status();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1 );
		$product->set_backorders( 'no' );
		$product->set_stock_status( ProductStockStatus::IN_STOCK );
		$product->save();

		try {
			$first_response  = $this->dispatch_add_item_request(
				array(
					'id'       => $product->get_id(),
					'quantity' => 1,
				)
			);
			$before          = $this->get_cart_line_identity_map();
			$second_response = $this->dispatch_add_item_request(
				array(
					'id'       => $product->get_id(),
					'quantity' => 1,
				)
			);
			$second_data     = $second_response->get_data();

			$this->assertSame( 201, $first_response->get_status(), 'The first managed-stock add should succeed.' );
			$this->assertSame( 400, $second_response->get_status(), 'The managed-stock re-add should be rejected.' );
			$this->assertSame( 'woocommerce_rest_product_partially_out_of_stock', $second_data['code'], 'The rejection should report the Store API partial-stock code.' );
			$this->assertSame( $before, $this->get_cart_line_identity_map(), 'The rejected re-add should leave every cart line unchanged.' );
		} finally {
			$product->set_manage_stock( $original_manage_stock );
			$product->set_stock_quantity( $original_stock_quantity );
			$product->set_backorders( $original_backorders );
			$product->set_stock_status( $original_stock_status );
			$product->save();
		}
	}

	/**
	 * Test adding item to cart with quantity 0 shows an error.
	 */
	public function test_add_item_with_zero_quantity_shows_error() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 0,
			)
		);

		$this->assertAPIResponse(
			$request,
			400,
			array(
				'code' => 'woocommerce_rest_product_invalid_quantity',
			)
		);
	}

	/**
	 * Test adding item to cart with quantity "0" as string shows an error.
	 */
	public function test_add_item_with_string_zero_quantity_shows_error() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => '0',
			)
		);

		$this->assertAPIResponse(
			$request,
			400,
			array(
				'code' => 'woocommerce_rest_product_invalid_quantity',
			)
		);
	}

	/**
	 * Test adding item to cart without quantity adds 1 item.
	 */
	public function test_add_item_without_quantity_defaults_to_one() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id' => $this->products[0]->get_id(),
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'items_count' => 1, // Total number of items in cart (quantity sum).
				'items'       => function ( $value ) {
					// The callback function checks that:
					// 1. There is exactly 1 unique product in the cart
					// 2. The first (and only) product has a quantity of 1.
					return 1 === count( $value ) && 1 === $value[0]['quantity'];
				},
			)
		);

		// Test adding the same item again without quantity to verify it adds another 1.
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id' => $this->products[0]->get_id(),
			)
		);

		$this->assertAPIResponse(
			$request,
			201,
			array(
				'items_count' => 2, // Total quantity of all items (same product added twice).
				'items'       => function ( $value ) {
					// The callback function checks that:
					// 1. There is still only 1 unique product in the cart
					// 2. That product now has a quantity of 2 (1 + 1 from second add).
					return 1 === count( $value ) && 2 === $value[0]['quantity'];
				},
			)
		);
	}

	/**
	 * Test adding item to cart with negative quantity shows an error.
	 */
	public function test_add_item_with_negative_quantity_shows_error() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => -1,
			)
		);

		$this->assertAPIResponse(
			$request,
			400,
			array(
				'code' => 'woocommerce_rest_product_invalid_quantity',
			)
		);
	}

	/**
	 * Test adding item to cart with negative quantity as string shows an error.
	 */
	public function test_add_item_with_string_negative_quantity_shows_error() {
		wc_empty_cart();

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => '-5',
			)
		);

		$this->assertAPIResponse(
			$request,
			400,
			array(
				'code' => 'woocommerce_rest_product_invalid_quantity',
			)
		);
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_added_from_user_request when adding an item.
	 */
	public function test_add_item_fires_add_action(): void {
		wc_empty_cart();

		$captured_args = array();
		$callback      = function ( $product_id, $quantity ) use ( &$captured_args ) {
			$captured_args = array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
			);
		};

		add_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback, 10, 2 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 2,
			)
		);

		$this->assertAPIResponse( $request, 201 );

		$this->assertNotEmpty( $captured_args, 'The add action should have been fired' );
		$this->assertSame( $this->products[0]->get_id(), $captured_args['product_id'] );
		$this->assertEquals( 2, $captured_args['quantity'] );

		remove_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_added_from_user_request with default quantity of 1 when quantity is omitted.
	 */
	public function test_add_item_fires_add_action_when_quantity_omitted(): void {
		wc_empty_cart();

		$captured_args = array();
		$callback      = function ( $product_id, $quantity ) use ( &$captured_args ) {
			$captured_args = array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
			);
		};

		add_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback, 10, 2 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id' => $this->products[0]->get_id(),
			)
		);

		$this->assertAPIResponse( $request, 201 );

		$this->assertNotEmpty( $captured_args, 'The add action should have been fired' );
		$this->assertSame( $this->products[0]->get_id(), $captured_args['product_id'] );
		$this->assertEquals( 1, $captured_args['quantity'] );

		remove_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_updated_from_user_request when updating item quantity.
	 */
	public function test_update_item_fires_update_action(): void {
		$captured_args = array();
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$callback = function ( $cart_item_key, $quantity, $old_quantity, $cart ) use ( &$captured_args ) {
			$captured_args = compact( 'cart_item_key', 'quantity', 'old_quantity', 'cart' );
		};

		add_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback, 10, 4 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 5,
			)
		);

		$this->assertAPIResponse( $request, 200 );

		$this->assertNotEmpty( $captured_args, 'The update action should have been fired' );
		$this->assertSame( $this->keys[0], $captured_args['cart_item_key'] );
		$this->assertEquals( 5, $captured_args['quantity'] );
		$this->assertEquals( 2, $captured_args['old_quantity'] );
		$this->assertInstanceOf( \WC_Cart::class, $captured_args['cart'] );

		remove_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_removed_from_user_request when removing a cart item.
	 */
	public function test_remove_item_fires_remove_action(): void {
		$captured_args = array();
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$callback = function ( $cart_item_key, $cart ) use ( &$captured_args ) {
			$captured_args = compact( 'cart_item_key', 'cart' );
		};

		add_action( 'internal_woocommerce_cart_item_removed_from_user_request', $callback, 10, 2 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/remove-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => $this->keys[0],
			)
		);

		$this->assertAPIResponse( $request, 200 );

		$this->assertNotEmpty( $captured_args, 'The remove action should have been fired' );
		$this->assertSame( $this->keys[0], $captured_args['cart_item_key'] );
		$this->assertInstanceOf( \WC_Cart::class, $captured_args['cart'] );

		remove_action( 'internal_woocommerce_cart_item_removed_from_user_request', $callback );
	}

	/**
	 * @testdox Should not fire internal_woocommerce_cart_item_updated_from_user_request when quantity is unchanged.
	 */
	public function test_update_item_with_same_quantity_does_not_fire_update_action(): void {
		$action_fired = false;
		$callback     = function () use ( &$action_fired ) {
			$action_fired = true;
		};

		add_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback, 10, 4 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 2,
			)
		);

		$this->assertAPIResponse( $request, 200 );

		$this->assertFalse( $action_fired, 'The update action should not fire when quantity is unchanged' );

		remove_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_added_from_user_request with the variation ID when adding a variable product.
	 */
	public function test_add_item_fires_add_action_with_variation_id(): void {
		wc_empty_cart();

		$fixtures  = new FixtureData();
		$attribute = $fixtures->get_product_attribute( 'color', array( 'blue' ) );
		$product   = $fixtures->get_variable_product(
			array(
				'name' => 'Test Variable Product',
			),
			array( $attribute )
		);

		$variation = new \WC_Product_Variation();
		$variation->set_parent_id( $product->get_id() );
		$variation->set_attributes( array( 'pa_color' => 'blue' ) );
		$variation->set_regular_price( 10 );
		$variation->save();

		$captured_args = array();
		$callback      = function ( $product_id, $quantity ) use ( &$captured_args ) {
			$captured_args = array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
			);
		};

		add_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback, 10, 2 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $variation->get_id(),
				'quantity' => 1,
			)
		);

		$this->assertAPIResponse( $request, 201 );

		$this->assertNotEmpty( $captured_args, 'The add action should have been fired' );
		$this->assertSame( $variation->get_id(), $captured_args['product_id'], 'The product_id should be the variation ID, not the parent product ID' );
		$this->assertEquals( 1, $captured_args['quantity'] );

		remove_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback );
	}

	/**
	 * @testdox Should not fire internal_woocommerce_cart_item_updated_from_user_request when quantity is not set.
	 */
	public function test_update_item_without_quantity_does_not_fire_update_action(): void {
		$action_fired = false;
		$callback     = function () use ( &$action_fired ) {
			$action_fired = true;
		};

		add_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback, 10, 4 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key' => $this->keys[0],
			)
		);

		$this->assertAPIResponse( $request, 200 );

		$this->assertFalse( $action_fired, 'The update action should not fire when quantity is not set' );

		remove_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_updated_from_user_request with untruncated quantities on stores with decimal quantities.
	 */
	public function test_update_item_fires_update_action_with_float_quantity(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		$multiple_of_callback = function () {
			return 0.5;
		};
		add_filter( 'woocommerce_store_api_product_quantity_multiple_of', $multiple_of_callback );

		wc()->cart->set_quantity( $this->keys[0], 1.5 );

		$captured_args = array();
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$callback = function ( $cart_item_key, $quantity, $old_quantity, $cart ) use ( &$captured_args ) {
			$captured_args = compact( 'cart_item_key', 'quantity', 'old_quantity', 'cart' );
		};

		add_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback, 10, 4 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 2.5,
			)
		);

		$this->assertAPIResponse( $request, 200 );

		$this->assertNotEmpty( $captured_args, 'The update action should have been fired' );
		$this->assertSame( 2.5, $captured_args['quantity'], 'The new quantity should not be truncated' );
		$this->assertSame( 1.5, $captured_args['old_quantity'], 'The old quantity should not be truncated' );
		$this->assertSame( 2.5, wc()->cart->get_cart_item( $this->keys[0] )['quantity'], 'The cart item quantity should be the untruncated value' );

		remove_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback );
		remove_filter( 'woocommerce_store_api_product_quantity_multiple_of', $multiple_of_callback );
		remove_filter( 'woocommerce_stock_amount', 'floatval' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
	}

	/**
	 * @testdox Should not fire internal_woocommerce_cart_item_updated_from_user_request when a decimal quantity is unchanged.
	 */
	public function test_update_item_with_same_float_quantity_does_not_fire_update_action(): void {
		remove_filter( 'woocommerce_stock_amount', 'intval' );
		add_filter( 'woocommerce_stock_amount', 'floatval' );
		$multiple_of_callback = function () {
			return 0.5;
		};
		add_filter( 'woocommerce_store_api_product_quantity_multiple_of', $multiple_of_callback );

		wc()->cart->set_quantity( $this->keys[0], 1.5 );

		$action_fired = false;
		$callback     = function () use ( &$action_fired ) {
			$action_fired = true;
		};

		add_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 1.5,
			)
		);

		$this->assertAPIResponse( $request, 200 );

		$this->assertFalse( $action_fired, 'The update action should not fire when a decimal quantity is unchanged' );

		remove_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback );
		remove_filter( 'woocommerce_store_api_product_quantity_multiple_of', $multiple_of_callback );
		remove_filter( 'woocommerce_stock_amount', 'floatval' );
		add_filter( 'woocommerce_stock_amount', 'intval' );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_updated_from_user_request with numeric quantities when the cart contains a string quantity.
	 */
	public function test_update_item_fires_update_action_with_numeric_quantities_for_string_cart_quantity(): void {
		$cart_contents                               = wc()->cart->get_cart_contents();
		$cart_contents[ $this->keys[0] ]['quantity'] = '2';
		wc()->cart->set_cart_contents( $cart_contents );

		$captured_args = array();
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$callback = function ( $cart_item_key, $quantity, $old_quantity, $cart ) use ( &$captured_args ) {
			$captured_args = compact( 'cart_item_key', 'quantity', 'old_quantity', 'cart' );
		};

		add_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback, 10, 4 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/update-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'key'      => $this->keys[0],
				'quantity' => 3,
			)
		);

		$this->assertAPIResponse( $request, 200 );

		$this->assertNotEmpty( $captured_args, 'The update action should have been fired' );
		$this->assertSame( 3, $captured_args['quantity'], 'The new quantity should be a numeric value' );
		$this->assertSame( 2, $captured_args['old_quantity'], 'The old quantity should be normalized to a numeric value' );

		remove_action( 'internal_woocommerce_cart_item_updated_from_user_request', $callback );
	}

	/**
	 * @testdox Should fire internal_woocommerce_cart_item_added_from_user_request with a numeric quantity when a filter sets a string quantity.
	 */
	public function test_add_item_fires_add_action_with_numeric_quantity_for_string_quantity(): void {
		wc_empty_cart();

		$add_to_cart_data_callback = function ( $add_to_cart_data ) {
			$add_to_cart_data['quantity'] = '2';
			return $add_to_cart_data;
		};
		add_filter( 'woocommerce_store_api_add_to_cart_data', $add_to_cart_data_callback );

		$captured_args = array();
		$callback      = function ( $product_id, $quantity ) use ( &$captured_args ) {
			$captured_args = array(
				'product_id' => $product_id,
				'quantity'   => $quantity,
			);
		};

		add_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback, 10, 2 );

		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/add-item' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params(
			array(
				'id'       => $this->products[0]->get_id(),
				'quantity' => 1,
			)
		);

		$this->assertAPIResponse( $request, 201 );

		$this->assertNotEmpty( $captured_args, 'The add action should have been fired' );
		$this->assertSame( 2, $captured_args['quantity'], 'The quantity should be normalized to a numeric value' );

		remove_action( 'internal_woocommerce_cart_item_added_from_user_request', $callback );
		remove_filter( 'woocommerce_store_api_add_to_cart_data', $add_to_cart_data_callback );
	}

	/**
	 * @testdox Should return an error response when restoring the cart session throws.
	 */
	public function test_cart_session_failure_returns_error_response() {
		wc()->session->set( 'cart', wc()->cart->get_cart_for_session() );

		// The route restores the cart only when this action has not run yet, so reset
		// the counter to put the process back into the state a REST request starts in.
		unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );

		add_filter(
			'woocommerce_get_cart_item_from_session',
			static function () {
				throw new \RuntimeException( 'Synthetic Store API cart-session failure.' );
			}
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertSame( 500, $response->get_status(), 'A cart session failure should return a Store API error response.' );
		$this->assertSame( 'woocommerce_rest_unknown_server_error', $response->get_data()['code'] );
	}

	/**
	 * @testdox Should return an error response when the cart session fails before it is restored.
	 */
	public function test_cart_session_failure_before_restore_returns_error_response() {
		// This filter runs before `get_cart_from_session()` fires its action, so nothing
		// stops the response headers attempting a second load of the failed cart.
		unset( $GLOBALS['wp_actions']['woocommerce_load_cart_from_session'] );

		// A REST request never runs `initialize_cart()`, so the route starts with no
		// cart at all. The test bootstrap leaves one behind.
		$this->cart_backup = WC()->cart;
		WC()->cart         = null;

		add_filter(
			'woocommerce_session_handler',
			static function () {
				throw new \RuntimeException( 'Synthetic session handler failure.' );
			}
		);

		$response = rest_get_server()->dispatch( new \WP_REST_Request( 'GET', '/wc/store/v1/cart' ) );

		$this->assertSame( 500, $response->get_status(), 'A cart session failure should return a Store API error response.' );
	}

	/**
	 * Restore the cart instance removed by the cart session failure tests.
	 */
	public function tearDown(): void {
		if ( $this->cart_backup instanceof \WC_Cart ) {
			WC()->cart         = $this->cart_backup;
			$this->cart_backup = null;
		}

		parent::tearDown();
	}
}
