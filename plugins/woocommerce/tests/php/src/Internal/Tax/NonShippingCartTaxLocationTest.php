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
		$request = new \WP_REST_Request( 'GET', $route );
		$server  = new \WP_REST_Server();
		$this->sut->handle_rest_pre_dispatch( null, $server, $request );

		try {
			$result = $this->sut->use_billing_address_for_cart_without_shipping( array( 'US', 'CA', '90210', 'Beverly Hills' ), $this->customer );

			$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result, 'A Store API cart request should use the billing address.' );
		} finally {
			$this->sut->handle_rest_post_dispatch( null, $server, $request );
		}
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
		$this->set_checkout_context();
		$request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$server  = new \WP_REST_Server();
		$this->sut->handle_rest_pre_dispatch( null, $server, $request );

		try {
			$result = $this->sut->use_billing_address_for_cart_without_shipping( array( 'US', 'CA', '90210', 'Beverly Hills' ), $this->customer );

			$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'Other Store API requests should not use the billing address.' );
		} finally {
			$this->sut->handle_rest_post_dispatch( null, $server, $request );
		}
	}

	/**
	 * @testdox Restores the Store API cart request context after nested requests.
	 */
	public function test_restores_store_api_cart_request_context_after_nested_request(): void {
		$this->add_product_to_cart( true );
		$cart_request    = new \WP_REST_Request( 'GET', '/wc/store/v1/cart' );
		$product_request = new \WP_REST_Request( 'GET', '/wc/store/v1/products' );
		$taxable_address = array( 'US', 'CA', '90210', 'Beverly Hills' );
		$server         = new \WP_REST_Server();

		$this->sut->handle_rest_pre_dispatch( null, $server, $cart_request );
		$this->sut->handle_rest_pre_dispatch( null, $server, $product_request );

		try {
			$result_during_nested_request = $this->sut->use_billing_address_for_cart_without_shipping( $taxable_address, $this->customer );
			$this->sut->handle_rest_post_dispatch( null, $server, $product_request );
			$result_after_nested_request = $this->sut->use_billing_address_for_cart_without_shipping( $taxable_address, $this->customer );

			$this->assertSame( $taxable_address, $result_during_nested_request, 'Nested non-cart Store API requests should not use the billing address.' );
			$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result_after_nested_request, 'The cart request context should be restored after a nested request.' );
		} finally {
			$this->sut->handle_rest_post_dispatch( null, $server, $cart_request );
		}
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
	 * @testdox Preserves the REST context during nested rest_do_request() calls through WP_REST_Server::dispatch().
	 *
	 * This test simulates what happens when rest_do_request() is called nested through
	 * WP_REST_Server::dispatch(), verifying that each dispatch removes only its own context.
	 */
	public function test_preserves_rest_context_during_nested_rest_do_request_calls(): void {
		// The key thing we're testing is that nested calls to handle_rest_pre_dispatch
		// and handle_rest_post_dispatch properly clean up their context without affecting outer contexts.
		// We'll simulate this by manually calling the methods as they would be called during nested dispatches.

		$server = new \WP_REST_Server();

		// Simulate an outer dispatch: pre_dispatch for a non-cart route.
		$outer_request = new \WP_REST_Request( 'GET', '/wc/v3/some-route' );
		$this->sut->handle_rest_pre_dispatch( null, $server, $outer_request );

		try {
			// Verify stack has 1 entry now.
			$reflection = new \ReflectionClass( $this->sut );
			$stack_property = $reflection->getProperty( 'dispatch_stack' );
			$stack_property->setAccessible( true );
			$context_property = $reflection->getProperty( 'dispatch_contexts' );
			$context_property->setAccessible( true );

			$stack_after_outer_pre = $stack_property->getValue( $this->sut );
			$this->assertCount( 1, $stack_after_outer_pre, 'Stack should have 1 entry after outer pre_dispatch.' );

			// Simulate a nested dispatch: pre_dispatch for another non-cart route.
			$inner_request = new \WP_REST_Request( 'GET', '/wc/v3/another-route' );
			$this->sut->handle_rest_pre_dispatch( null, $server, $inner_request );

			$stack_after_inner_pre = $stack_property->getValue( $this->sut );
			$this->assertCount( 2, $stack_after_inner_pre, 'Stack should have 2 entries after inner pre_dispatch.' );

			// Simulate nested dispatch completing: post_dispatch for the inner request.
			// This should remove ONLY the inner request's context.
			$this->sut->handle_rest_post_dispatch( null, $server, $inner_request );

			$stack_after_inner_post = $stack_property->getValue( $this->sut );
			$contexts_after_inner_post = $context_property->getValue( $this->sut );

			// The stack should still have 1 entry (the outer request).
			$this->assertCount( 1, $stack_after_inner_post, 'Stack should still have 1 entry after inner post_dispatch (outer context preserved).' );

			// The specific context for the inner request should be removed.
			$inner_request_id = spl_object_id( $inner_request );
			$this->assertArrayNotHasKey( $inner_request_id, $contexts_after_inner_post, 'Inner request context should be removed.' );

			// But the outer request context should still be there.
			$outer_request_id = spl_object_id( $outer_request );
			$this->assertArrayHasKey( $outer_request_id, $contexts_after_inner_post, 'Outer request context should still exist.' );

			// Simulate outer dispatch completing: post_dispatch for the outer request.
			$this->sut->handle_rest_post_dispatch( null, $server, $outer_request );

			$stack_after_outer_post = $stack_property->getValue( $this->sut );
			$contexts_after_outer_post = $context_property->getValue( $this->sut );

			// Now the stack should be empty.
			$this->assertCount( 0, $stack_after_outer_post, 'Stack should be empty after outer post_dispatch.' );

			// And all contexts should be removed.
			$this->assertCount( 0, $contexts_after_outer_post, 'All contexts should be removed after outer post_dispatch.' );
		} finally {
			// Ensure cleanup.
			global $wp_rest_server;
			$wp_rest_server = null;
		}
	}
}
