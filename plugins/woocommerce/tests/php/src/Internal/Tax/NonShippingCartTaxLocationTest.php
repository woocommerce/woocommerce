<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Enums\TaxBasedOn;
use Automattic\WooCommerce\Internal\Tax\CartPricingContext;
use Automattic\WooCommerce\Internal\Tax\NonShippingCartTaxLocation;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the NonShippingCartTaxLocation class.
 */
class NonShippingCartTaxLocationTest extends \WC_Unit_Test_Case {

	use StoreApiRouteTestTrait;

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
	 * The customer in place before the test replaced WC()->customer, to be restored in tearDown().
	 *
	 * @var \WC_Customer
	 */
	private $original_customer;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$cart_pricing_context = wc_get_container()->get( CartPricingContext::class );
		$cart_pricing_context->init();
		$this->sut = wc_get_container()->get( NonShippingCartTaxLocation::class );
		$this->sut->init( $cart_pricing_context );
		$this->customer = new \WC_Customer();
		$this->customer->set_billing_location( 'GB', 'LND', 'SW1A 1AA', 'London' );
		$this->customer->set_shipping_location( 'US', 'CA', '90210', 'Beverly Hills' );
		$this->original_customer = WC()->customer;
		WC()->customer           = $this->customer;

		update_option( 'woocommerce_tax_based_on', TaxBasedOn::SHIPPING );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			WC()->customer = $this->original_customer;
			remove_filter( 'woocommerce_is_checkout', '__return_true' );
			remove_filter( 'woocommerce_product_needs_shipping', '__return_false' );
			remove_filter( 'woocommerce_product_needs_shipping', '__return_true' );
			remove_filter( 'woocommerce_cart_needs_shipping', '__return_true' );
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
	 * @testdox Uses the billing address for a Store API cart request.
	 */
	public function test_uses_billing_address_for_store_api_cart_request(): void {
		$this->add_product_to_cart( true );
		$recorded = null;

		$this->dispatch_test_route(
			'/wc/store/v1/cart',
			function ( $request ) use ( &$recorded ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				$recorded = $this->customer->get_taxable_address();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $recorded, 'A Store API cart request should use the billing address.' );
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
	 * @testdox Uses the billing address for cart totals calculated outside the cart and checkout.
	 */
	public function test_uses_billing_address_for_cart_totals_outside_cart_and_checkout(): void {
		$this->create_billing_country_tax_rate();
		$this->add_product_to_cart( true );
		$total_tax     = null;
		$after_address = null;

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$total_tax, &$after_address ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				WC()->cart->calculate_totals();
				$total_tax     = WC()->cart->get_total_tax();
				$after_address = $this->customer->get_taxable_address();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertEquals( 2.0, $total_tax, 'Cart totals should include the billing country tax.' );
		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $after_address, 'The shipping address should be used again once totals are calculated.' );
	}

	/**
	 * @testdox Uses the billing address for mini-cart prices rendered outside the cart and checkout.
	 */
	public function test_uses_billing_address_for_mini_cart_outside_cart_and_checkout(): void {
		$this->create_billing_country_tax_rate();
		$this->add_product_to_cart( true );
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		$mini_cart     = '';
		$after_address = null;

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$mini_cart, &$after_address ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				ob_start();
				woocommerce_mini_cart();
				$mini_cart     = (string) ob_get_clean();
				$after_address = $this->customer->get_taxable_address();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertStringContainsString( '<span class="quantity">1 &times; ' . wc_price( 12 ) . '</span>', $mini_cart, 'Mini-cart line prices should include the billing country tax.' );
		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $after_address, 'The shipping address should be used again once the mini-cart is rendered.' );
	}

	/**
	 * @testdox Uses the billing address for a cart request loaded through Blocks hydration.
	 *
	 * The non-cart Store API request models a render outside the cart and checkout: is_checkout()
	 * is unreliable in CI because legacy tests define the WOOCOMMERCE_CHECKOUT constant for the rest of the process.
	 */
	public function test_uses_billing_address_for_cart_loaded_through_hydration(): void {
		$this->create_billing_country_tax_rate();
		$this->add_product_to_cart( true );
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		$item_price    = null;
		$after_address = null;

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$item_price, &$after_address ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				StoreApi::container();
				$hydration = new Hydration( $this->createMock( AssetDataRegistry::class ) );
				$result    = $hydration->get_rest_api_response_data( '/wc/store/v1/cart' );

				$item_price    = $result['body']['items'][0]['prices']->price ?? null;
				$after_address = $this->customer->get_taxable_address();
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		// Store API prices are strings in the currency's minor unit: 12.00 becomes '1200'.
		$this->assertSame( '1200', $item_price, 'A hydrated cart item price should include the billing country tax.' );
		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $after_address, 'The shipping address should be used again once hydration is done.' );
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
	 * @testdox Does not recurse when a woocommerce_product_needs_shipping callback resolves the taxable address.
	 */
	public function test_does_not_recurse_when_needs_shipping_callback_resolves_taxable_address(): void {
		$this->add_product_to_cart( true );
		$this->set_checkout_context();

		// The instance hooked at boot differs from the SUT in tests, so keep only the SUT hooked.
		remove_all_filters( 'woocommerce_customer_taxable_address' );
		add_filter( 'woocommerce_customer_taxable_address', array( $this->sut, 'use_billing_address_for_cart_without_shipping' ), 10, 2 );

		// WC_Cart::needs_shipping() only checks products when a shipping method exists, so count nesting rather than calls.
		$depth          = 0;
		$max_depth      = 0;
		$inner_address  = null;
		$customer       = $this->customer;
		$needs_shipping = function ( $needs_shipping ) use ( &$depth, &$max_depth, &$inner_address, $customer ) {
			$max_depth = max( $max_depth, ++$depth );
			try {
				// Stop at the second level so a missing guard fails the assertion instead of recursing without end.
				if ( 1 === $depth ) {
					$inner_address = $customer->get_taxable_address();
				}
			} finally {
				--$depth;
			}
			return $needs_shipping;
		};
		add_filter( 'woocommerce_product_needs_shipping', $needs_shipping );

		try {
			$result = $this->customer->get_taxable_address();
		} finally {
			remove_filter( 'woocommerce_product_needs_shipping', $needs_shipping );
		}

		$this->assertSame( 1, $max_depth, 'The needs_shipping callback should not be re-entered from the taxable address lookup.' );
		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $inner_address, 'A re-entrant lookup should get the unfiltered taxable address.' );
		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result, 'The outer lookup should still use the billing address.' );
	}

	/**
	 * @testdox Leaves the taxable address unchanged when the woocommerce_cart_needs_shipping filter forces shipping.
	 */
	public function test_leaves_address_unchanged_when_cart_needs_shipping_filter_forces_shipping(): void {
		$this->add_product_to_cart( true );

		// A shipping method must exist for WC_Cart::needs_shipping() to apply the filter below.
		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( 'Test Zone' );
		$zone->save();
		$zone->add_shipping_method( 'flat_rate' );
		delete_transient( 'wc_shipping_method_count' );

		add_filter( 'woocommerce_cart_needs_shipping', '__return_true' );
		$this->set_checkout_context();

		try {
			$result = $this->customer->get_taxable_address();

			$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'A cart forced to need shipping by the woocommerce_cart_needs_shipping filter should use the configured shipping address.' );
		} finally {
			$zone->delete( true );
		}
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
	 *
	 * The session keeps a chosen shipping method until it is saved for a cart without shippable products,
	 * so a local pickup choice can outlive the removal of the last shippable item within a request.
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
	 * @testdox Uses the billing address for local pickup when base tax for local pickup is disabled.
	 */
	public function test_uses_billing_address_for_local_pickup_without_base_tax(): void {
		$this->add_product_to_cart( true );
		WC()->session->set( 'chosen_shipping_methods', array( 'local_pickup:1' ) );
		add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result, 'Local pickup without base tax should fall through to the billing address.' );
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
	 * Enable taxes with a 20% standard rate for the billing country only.
	 */
	private function create_billing_country_tax_rate(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		\WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'GB',
				'tax_rate_state'    => '',
				'tax_rate'          => '20.0000',
				'tax_rate_name'     => 'VAT',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);
	}

	/**
	 * Set the request context to checkout.
	 */
	private function set_checkout_context(): void {
		add_filter( 'woocommerce_is_checkout', '__return_true' );
	}
}
