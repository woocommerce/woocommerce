<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Enums\TaxBasedOn;
use Automattic\WooCommerce\Internal\Tax\NonShippingCartTaxLocation;

/**
 * Tests for the NonShippingCartTaxLocation class.
 */
class NonShippingCartTaxLocationTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var NonShippingCartTaxLocation
	 */
	private $sut;

	/**
	 * The customer used by the tests.
	 *
	 * @var \WC_Customer
	 */
	private $customer;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->sut = wc_get_container()->get( NonShippingCartTaxLocation::class );
		$this->sut->init();
		$this->customer = new \WC_Customer();
		$this->customer->set_billing_location( 'GB', 'LND', 'SW1A 1AA', 'London' );
		$this->customer->set_shipping_location( 'US', 'CA', '90210', 'Beverly Hills' );
		WC()->customer = $this->customer;
		WC()->cart     = new \WC_Cart();

		update_option( 'woocommerce_tax_based_on', TaxBasedOn::SHIPPING );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			remove_filter( 'woocommerce_is_checkout', '__return_true' );
			remove_filter( 'woocommerce_product_needs_shipping', '__return_false' );
			remove_filter( 'woocommerce_product_needs_shipping', '__return_true' );
			$this->clear_rest_server();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Leaves the taxable address unchanged when tax is not based on shipping.
	 *
	 * @dataProvider provider_tax_bases_other_than_shipping
	 *
	 * @param string $tax_based_on Tax location setting.
	 */
	public function test_leaves_address_unchanged_when_tax_is_not_based_on_shipping( string $tax_based_on ): void {
		$this->add_product_to_cart( true );
		update_option( 'woocommerce_tax_based_on', $tax_based_on );
		$taxable_address = array( 'DE', 'BE', '10115', 'Berlin' );

		$result = $this->sut->use_billing_address_for_cart_without_shipping( $taxable_address, $this->customer );

		$this->assertSame( $taxable_address, $result, 'The configured taxable address should remain unchanged.' );
	}

	/**
	 * Provides tax location settings other than shipping.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_tax_bases_other_than_shipping(): array {
		return array(
			'base address'    => array( TaxBasedOn::BASE ),
			'billing address' => array( TaxBasedOn::BILLING ),
		);
	}

	/**
	 * @testdox Leaves the taxable address unchanged when the cart is empty.
	 */
	public function test_leaves_address_unchanged_for_empty_cart(): void {
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'An empty cart should use the configured shipping address.' );
	}

	/**
	 * @testdox Uses the billing address when no cart product needs shipping.
	 */
	public function test_uses_billing_address_for_cart_without_shipping(): void {
		$this->add_product_to_cart( true );
		$this->add_product_to_cart( true );
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result, 'A cart without products requiring shipping should use the billing address.' );
	}

	/**
	 * @testdox Uses the billing address for Store API cart and checkout requests.
	 *
	 * @dataProvider provider_store_api_cart_and_checkout_routes
	 *
	 * @param string $route Store API route.
	 */
	public function test_uses_billing_address_for_store_api_cart_and_checkout_requests( string $route ): void {
		$this->add_product_to_cart( true );
		$recorded = null;

		$this->dispatch_test_route(
			$route,
			function ( $request ) use ( &$recorded ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$recorded = $this->customer->get_taxable_address();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $recorded, 'A Store API cart request should use the billing address.' );
	}

	/**
	 * Provides Store API cart and checkout routes that can calculate cart totals.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_store_api_cart_and_checkout_routes(): array {
		return array(
			'cart namespace'       => array( '/wc/store/cart' ),
			'versioned cart'       => array( '/wc/store/v1/cart' ),
			'add cart item'        => array( '/wc/store/v1/cart/add-item' ),
			'cart items'           => array( '/wc/store/v1/cart/items' ),
			'cart item by key'     => array( '/wc/store/v1/cart/items/cart-item-key' ),
			'apply coupon'         => array( '/wc/store/v1/cart/apply-coupon' ),
			'cart coupons'         => array( '/wc/store/v1/cart/coupons' ),
			'remove coupon'        => array( '/wc/store/v1/cart/coupons/coupon-code' ),
			'remove cart coupon'   => array( '/wc/store/v1/cart/remove-coupon' ),
			'remove cart item'     => array( '/wc/store/v1/cart/remove-item' ),
			'select shipping rate' => array( '/wc/store/v1/cart/select-shipping-rate' ),
			'update cart item'     => array( '/wc/store/v1/cart/update-item' ),
			'update customer'      => array( '/wc/store/v1/cart/update-customer' ),
			'cart extension'       => array( '/wc/store/v1/cart/extensions' ),
			'checkout namespace'   => array( '/wc/store/checkout' ),
			'versioned checkout'   => array( '/wc/store/v1/checkout' ),
			'checkout draft order' => array( '/wc/store/v1/checkout/123' ),
		);
	}

	/**
	 * @testdox Leaves the taxable address unchanged for other Store API requests.
	 */
	public function test_leaves_address_unchanged_for_other_store_api_requests(): void {
		$this->add_product_to_cart( true );
		$recorded = null;

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$recorded ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$recorded = $this->customer->get_taxable_address();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $recorded, 'Other Store API requests should not use the billing address.' );
	}

	/**
	 * @testdox Restores the Store API cart request context after nested requests.
	 *
	 * Registers real routes and drives them through rest_do_request() so the
	 * full dispatch lifecycle runs: the outer cart route nests an internal
	 * rest_do_request() to a non-cart route. Both dispatches push and pop
	 * their context inside respond_to_request(), so the outer cart context is
	 * restored after the nested non-cart request completes.
	 */
	public function test_restores_store_api_cart_request_context_after_nested_request(): void {
		$this->add_product_to_cart( true );
		$inner_recorded  = null;
		$outer_recorded  = null;
		$taxable_address = array( 'US', 'CA', '90210', 'Beverly Hills' );

		$this->create_rest_server_with_routes(
			array(
				function () use ( &$inner_recorded, &$outer_recorded ): void {
					$this->register_test_route(
						'/wc/v3/test-inner',
						function ( $request ) use ( &$inner_recorded ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							$inner_recorded = $this->customer->get_taxable_address();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
					$this->register_test_route(
						'/wc/store/v1/cart/test-outer',
						function ( $request ) use ( &$outer_recorded ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/test-inner' ) );
							$outer_recorded = $this->customer->get_taxable_address();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
				},
			)
		);

		rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/test-outer' ) );

		$this->assertSame( $taxable_address, $inner_recorded, 'Nested non-cart Store API requests should not use the billing address.' );
		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $outer_recorded, 'The cart request context should be restored after a nested request.' );
	}

	/**
	 * @testdox Uses the billing address for a non-virtual product that does not need shipping.
	 */
	public function test_uses_billing_address_for_non_virtual_product_without_shipping(): void {
		$this->add_product_to_cart( false );
		add_filter( 'woocommerce_product_needs_shipping', '__return_false' );
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result, 'A cart without products requiring shipping should use the billing address.' );
	}

	/**
	 * @testdox Leaves the taxable address unchanged when a cart product needs shipping.
	 */
	public function test_leaves_address_unchanged_for_mixed_cart(): void {
		$this->add_product_to_cart( true );
		$this->add_product_to_cart( false );
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'A mixed cart should use the configured shipping address.' );
	}

	/**
	 * @testdox Leaves the taxable address unchanged when shipping is required for a virtual product.
	 */
	public function test_leaves_address_unchanged_when_virtual_product_needs_shipping(): void {
		$this->add_product_to_cart( true );
		add_filter( 'woocommerce_product_needs_shipping', '__return_true' );
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'A virtual product requiring shipping should use the configured shipping address.' );
	}

	/**
	 * @testdox Leaves the taxable address unchanged when it is requested for a customer who does not own the cart.
	 */
	public function test_leaves_address_unchanged_for_customer_who_does_not_own_cart(): void {
		$this->add_product_to_cart( true );
		$this->set_checkout_context();
		$other_customer = new \WC_Customer();
		$other_customer->set_billing_location( 'DE', 'BE', '10115', 'Berlin' );
		$other_customer->set_shipping_location( 'FR', 'IDF', '75001', 'Paris' );

		$result = $other_customer->get_taxable_address();

		$this->assertSame( array( 'FR', 'IDF', '75001', 'Paris' ), $result, 'The cart should not affect another customer\'s taxable address.' );
	}

	/**
	 * The "outside cart and checkout" path is not testable in the full suite.
	 *
	 * `is_checkout()` short-circuits to true once `WOOCOMMERCE_CHECKOUT` is
	 * defined, and several legacy cart/checkout tests define it via
	 * `define()` / `wc_maybe_define_constant()`. PHP constants cannot be
	 * undefined, so the flag stays set for the rest of the process. The legacy
	 * suite runs before the main suite (`defaultTestSuite` in `phpunit.xml`),
	 * which means `is_checkout()` is already true here in CI even though it is
	 * false when the test runs in isolation locally.
	 *
	 * The same early-return branch is covered deterministically by
	 * `test_leaves_address_unchanged_for_other_store_api_requests` through the
	 * controllable Store API request-context stack.
	 *
	 * @see \Automattic\WooCommerce\Tests\Internal\LegacyAssets\LegacySelect2UsageTrackerTest::get_expected_frontend_page_type()
	 * @see \Automattic\WooCommerce\Tests\Blocks\BlockTypes\SavedForLaterTests::test_cart_page_has_saved_for_later_flag()
	 */

	/**
	 * @testdox Leaves the taxable address unchanged when the billing country is empty.
	 */
	public function test_leaves_address_unchanged_when_billing_country_is_empty(): void {
		$this->add_product_to_cart( true );
		$this->customer->set_billing_location( '', 'LND', 'SW1A 1AA', 'London' );
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'An empty billing country should not replace the shipping address.' );
	}

	/**
	 * @testdox Leaves the taxable address unchanged when local pickup uses the store address.
	 */
	public function test_leaves_address_unchanged_when_local_pickup_uses_store_address(): void {
		$this->add_product_to_cart( true );
		WC()->session->set( 'chosen_shipping_methods', array( 'local_pickup:1' ) );
		$this->set_checkout_context();
		$base_location = wc_get_base_location();

		$result = $this->customer->get_taxable_address();

		$this->assertSame(
			array(
				$base_location['country'],
				$base_location['state'],
				WC()->countries->get_base_postcode(),
				WC()->countries->get_base_city(),
			),
			$result,
			'Local pickup should use the store address for taxes.'
		);
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param bool $virtual Whether the product is virtual.
	 */
	private function add_product_to_cart( bool $virtual ): void {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_virtual( $virtual );
		$product->save();

		WC()->cart->add_to_cart( $product->get_id() );
	}

	/**
	 * Set the request context to checkout.
	 */
	private function set_checkout_context(): void {
		add_filter( 'woocommerce_is_checkout', '__return_true' );
	}

	/**
	 * Register a test REST route on the global server, splitting the path into a
	 * namespace and route so it can be dispatched by rest_do_request().
	 *
	 * Store API cart/checkout routes keep everything up to /cart or /checkout as
	 * the namespace; other routes use the leading segments as the namespace.
	 *
	 * @param string   $full_route Full route path, e.g. /wc/store/v1/cart.
	 * @param callable $callback   Route callback.
	 */
	private function register_test_route( string $full_route, callable $callback ): void {
		$path = ltrim( $full_route, '/' );

		if ( preg_match( '#^(.*?)/(cart|checkout)(?:/|$)#', $path, $m ) ) {
			$namespace = $m[1];
			$route     = '/' . substr( $path, strlen( $m[1] ) + 1 );
		} else {
			$offset    = (int) strrpos( $path, '/' );
			$namespace = substr( $path, 0, $offset );
			$route     = '/' . substr( $path, $offset + 1 );
		}

		register_rest_route(
			$namespace,
			$route,
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => $callback,
			)
		);
	}

	/**
	 * Create an isolated REST server with a single test route, then dispatch a
	 * GET request to it through rest_do_request() so the full dispatch lifecycle
	 * (rest_request_before_callbacks, callback, rest_request_after_callbacks) runs.
	 *
	 * @param string   $route    Full route path to register and dispatch.
	 * @param callable $callback  Route callback.
	 */
	private function dispatch_test_route( string $route, callable $callback ): void {
		$this->create_rest_server_with_routes(
			array(
				function () use ( $route, $callback ): void {
					$this->register_test_route( $route, $callback );
				},
			)
		);

		rest_do_request( new \WP_REST_Request( 'GET', $route ) );
	}

	/**
	 * Assert the dispatch stack and context map are both empty.
	 *
	 * @param string $message Assertion failure message.
	 */
	private function assert_dispatch_stack_empty( string $message ): void {
		$reflection     = new \ReflectionClass( $this->sut );
		$stack_property = $reflection->getProperty( 'dispatch_stack' );
		$stack_property->setAccessible( true );
		$contexts_property = $reflection->getProperty( 'dispatch_contexts' );
		$contexts_property->setAccessible( true );

		$this->assertSame( array(), $stack_property->getValue( $this->sut ), "Dispatch stack should be empty. {$message}" );
		$this->assertSame( array(), $contexts_property->getValue( $this->sut ), "Dispatch contexts should be empty. {$message}" );
	}

	/**
	 * @testdox Clears a nested cart context so the outer non-cart request keeps its own address.
	 *
	 * The outer route is non-cart; it nests an internal rest_do_request() to a
	 * cart route. Both dispatches push and pop their context inside
	 * respond_to_request(), so the nested cart context is cleared before the
	 * outer request calculates its own taxable address from the shipping address.
	 */
	public function test_preserves_rest_context_during_nested_rest_do_request_calls(): void {
		$this->add_product_to_cart( true );
		$inner_recorded  = null;
		$outer_recorded  = null;
		$taxable_address = array( 'US', 'CA', '90210', 'Beverly Hills' );

		$this->create_rest_server_with_routes(
			array(
				function () use ( &$inner_recorded, &$outer_recorded ): void {
					$this->register_test_route(
						'/wc/store/v1/cart/test-inner',
						function ( $request ) use ( &$inner_recorded ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							$inner_recorded = $this->customer->get_taxable_address();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
					$this->register_test_route(
						'/wc/v3/test-outer',
						function ( $request ) use ( &$outer_recorded ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/test-inner' ) );
							$outer_recorded = $this->customer->get_taxable_address();
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
				},
			)
		);

		rest_do_request( new \WP_REST_Request( 'GET', '/wc/v3/test-outer' ) );

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $inner_recorded, 'A nested cart request should use the billing address.' );
		$this->assertSame( $taxable_address, $outer_recorded, 'The outer non-cart context should be restored after a nested cart request.' );
	}

	/**
	 * @testdox Does not leave a cart context on the stack when rest_pre_dispatch short-circuits.
	 *
	 * A rest_pre_dispatch filter can hijack a request by returning a non-empty
	 * result, causing dispatch() to return before respond_to_request() — so
	 * neither rest_request_before_callbacks nor rest_request_after_callbacks
	 * fires. Registering the context from rest_request_before_callbacks (not
	 * rest_pre_dispatch) ensures no cart context is pushed for the hijacked
	 * request, so a later taxable-address lookup is not left with a stale
	 * billing-address context.
	 */
	public function test_does_not_register_context_when_pre_dispatch_short_circuits_cart_route(): void {
		$this->add_product_to_cart( true );

		$this->create_rest_server_with_routes(
			array(
				function (): void {
					$this->register_test_route(
						'/wc/store/v1/cart/test-hijack',
						function ( $request ) {
							unset( $request ); // Avoid parameter not used PHPCS errors.
							return rest_ensure_response( array( 'ok' => true ) );
						}
					);
				},
			)
		);

		$hijack = function ( $result, $server, $request ) {
			unset( $server ); // Avoid parameter not used PHPCS errors.
			if ( '/wc/store/v1/cart/test-hijack' === $request->get_route() ) {
				return rest_ensure_response( array( 'hijacked' => true ) );
			}
			return $result;
		};
		add_filter( 'rest_pre_dispatch', $hijack, 10, 3 );

		try {
			$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/test-hijack' ) );
			$this->assertSame( 200, $response->get_status(), 'The hijack filter should short-circuit the cart route.' );
		} finally {
			remove_filter( 'rest_pre_dispatch', $hijack, 10 );
		}

		$this->assert_dispatch_stack_empty( 'A short-circuited cart request should not leave a context on the stack.' );
	}

	/**
	 * @testdox Does not leave a cart context on the stack for an unmatched Store API cart route.
	 *
	 * When no route matches, dispatch() returns a 404 before reaching
	 * respond_to_request(), so rest_request_before_callbacks never fires and no
	 * cart context is pushed. A leaked context here would make a later
	 * taxable-address lookup return the billing address for a virtual-only cart.
	 */
	public function test_does_not_register_context_for_unmatched_store_api_cart_route(): void {
		$this->add_product_to_cart( true );

		$this->create_rest_server_with_routes( array() );

		$response = rest_do_request( new \WP_REST_Request( 'GET', '/wc/store/v1/cart/this-route-does-not-exist' ) );

		$this->assertSame( 404, $response->get_status(), 'An unmatched Store API cart route should return 404.' );

		$this->assert_dispatch_stack_empty( 'An unmatched cart request should not leave a context on the stack.' );
	}
}
