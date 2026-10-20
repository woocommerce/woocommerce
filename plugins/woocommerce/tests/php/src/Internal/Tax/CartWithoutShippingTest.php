<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Tax;

use Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry;
use Automattic\WooCommerce\Blocks\Domain\Services\Hydration;
use Automattic\WooCommerce\Enums\TaxBasedOn;
use Automattic\WooCommerce\Internal\Tax\CartPricingContext;
use Automattic\WooCommerce\Internal\Tax\CartWithoutShipping;
use Automattic\WooCommerce\StoreApi\StoreApi;

/**
 * Tests for the CartWithoutShipping class.
 */
class CartWithoutShippingTest extends \WC_Unit_Test_Case {

	use StoreApiRouteTestTrait;

	/**
	 * The System Under Test.
	 *
	 * @var CartWithoutShipping
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
	 * Shipping zone with a flat rate method, so a cart with physical products needs shipping.
	 *
	 * @var \WC_Shipping_Zone
	 */
	private $shipping_zone;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$cart_pricing_context = wc_get_container()->get( CartPricingContext::class );
		$cart_pricing_context->init();
		$this->sut = wc_get_container()->get( CartWithoutShipping::class );
		$this->sut->init( $cart_pricing_context );
		$this->customer = new \WC_Customer();
		$this->customer->set_billing_location( 'GB', 'LND', 'SW1A 1AA', 'London' );
		$this->customer->set_shipping_location( 'US', 'CA', '90210', 'Beverly Hills' );
		$this->original_customer = WC()->customer;
		WC()->customer           = $this->customer;

		update_option( 'woocommerce_tax_based_on', TaxBasedOn::SHIPPING );

		// WC_Cart::needs_shipping() is false for every cart when no shipping method exists.
		$this->shipping_zone = new \WC_Shipping_Zone();
		$this->shipping_zone->set_zone_name( 'Test Zone' );
		$this->shipping_zone->save();
		$this->shipping_zone->add_shipping_method( 'flat_rate' );
		delete_transient( 'wc_shipping_method_count' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		try {
			WC()->customer = $this->original_customer;
			$this->clear_rest_server();
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox Does not use the billing address when tax is not based on shipping.
	 *
	 * @dataProvider provider_tax_bases_other_than_shipping
	 *
	 * @param string $tax_based_on Tax location setting.
	 */
	public function test_does_not_use_billing_address_when_tax_is_not_based_on_shipping( string $tax_based_on ): void {
		$this->add_product_to_cart( true );
		$this->set_checkout_context();
		update_option( 'woocommerce_tax_based_on', $tax_based_on );

		$this->assertFalse( $this->sut->should_use_billing_address( $this->customer ), 'The billing address should not be used when tax is not based on shipping.' );
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
	 * @testdox Keeps the local pickup location when the taxable address filter is applied without a customer.
	 *
	 * Third-party code can apply woocommerce_customer_taxable_address with only the address argument.
	 */
	public function test_keeps_local_pickup_location_when_filter_is_applied_without_customer(): void {
		$this->add_product_to_cart( true );
		$this->choose_local_pickup_in_us();
		$this->set_checkout_context();

		// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment -- Simulates a third-party call without the customer argument.
		$result = apply_filters( 'woocommerce_customer_taxable_address', array( 'US', 'CA', '90210', 'Beverly Hills' ) );

		$this->assertSame( array( 'US', 'CA', '90001', 'Los Angeles' ), $result, 'Without a customer to check the cart for, the local pickup location should still apply.' );
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
	 * @testdox Prices classic cart cross-sells using shipping tax while the cart uses billing tax.
	 */
	public function test_classic_cart_cross_sell_uses_catalog_tax(): void {
		$this->set_up_cross_sell_tax_fixture();
		add_filter( 'woocommerce_is_cart', '__return_true' );

		ob_start();
		woocommerce_cross_sell_display();
		$markup = (string) ob_get_clean();

		$this->assertStringContainsString( wc_price( 110 ), $markup, 'The physical cross-sell should use shipping tax.' );
		$this->assertStringNotContainsString( wc_price( 127 ), $markup, 'The cross-sell should not use billing tax.' );
		WC()->cart->calculate_totals();
		$this->assertEquals( 2.7, WC()->cart->get_cart_contents_tax(), 'The virtual cart item should still use billing tax.' );
	}

	/**
	 * @testdox Prices Store API cross-sells using shipping tax while virtual cart items use billing tax.
	 */
	public function test_store_api_cross_sell_uses_catalog_tax(): void {
		$cross_sell = $this->set_up_cross_sell_tax_fixture();
		$result     = null;

		$this->dispatch_test_route(
			'/wc/store/v1/products',
			function ( $request ) use ( &$result ) {
				unset( $request ); // Avoid parameter not used PHPCS errors.
				StoreApi::container();
				$hydration = new Hydration( $this->createMock( AssetDataRegistry::class ) );
				$result    = $hydration->get_rest_api_response_data( '/wc/store/v1/cart' );
				return rest_ensure_response( array( 'ok' => true ) );
			}
		);

		$this->assertSame( '1270', $result['body']['items'][0]['prices']->price, 'The virtual cart item should use billing tax.' );
		$this->assertSame( $cross_sell->get_id(), $result['body']['cross_sells'][0]['id'], 'The expected cross-sell should appear in the cart response.' );
		$this->assertSame( '11000', $result['body']['cross_sells'][0]['prices']->price, 'The physical cross-sell should use shipping tax.' );
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
	 * @testdox Follows WC_Cart::needs_shipping() rather than whether products are virtual.
	 *
	 * @dataProvider provider_needs_shipping_overrides
	 *
	 * @param bool     $virtual          Whether the cart product is virtual.
	 * @param callable $override         Changes whether the cart needs shipping.
	 * @param bool     $expected_billing Whether the billing address is expected.
	 */
	public function test_follows_cart_needs_shipping( bool $virtual, callable $override, bool $expected_billing ): void {
		$this->add_product_to_cart( $virtual );
		$override();
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$expected = $expected_billing ? array( 'GB', 'LND', 'SW1A 1AA', 'London' ) : array( 'US', 'CA', '90210', 'Beverly Hills' );
		$this->assertSame( $expected, $result, 'The taxable address should follow whether the cart needs shipping.' );
	}

	/**
	 * Provides ways to change whether a cart needs shipping, independently of whether its product is virtual.
	 *
	 * @return array<string, array{bool, callable, bool}>
	 */
	public function provider_needs_shipping_overrides(): array {
		return array(
			'physical product that does not need shipping' => array(
				false,
				static function (): void {
					add_filter( 'woocommerce_product_needs_shipping', '__return_false' );
				},
				true,
			),
			'physical product with shipping disabled'      => array(
				false,
				static function (): void {
					update_option( 'woocommerce_ship_to_countries', 'disabled' );
				},
				true,
			),
			'virtual product that needs shipping'          => array(
				true,
				static function (): void {
					add_filter( 'woocommerce_product_needs_shipping', '__return_true' );
				},
				false,
			),
			'cart forced to need shipping'                 => array(
				true,
				static function (): void {
					add_filter( 'woocommerce_cart_needs_shipping', '__return_true' );
				},
				false,
			),
		);
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
	 * @testdox Does not recurse when a woocommerce_product_needs_shipping callback resolves the taxable address.
	 */
	public function test_does_not_recurse_when_needs_shipping_callback_resolves_taxable_address(): void {
		$this->add_product_to_cart( true );
		$this->set_checkout_context();

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

		$result = $this->customer->get_taxable_address();

		$this->assertSame( 1, $max_depth, 'The needs_shipping callback should not be re-entered from the taxable address lookup.' );
		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $inner_address, 'A re-entrant lookup should get the unfiltered taxable address.' );
		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result, 'The outer lookup should still use the billing address.' );
	}

	/**
	 * @testdox Lets a later woocommerce_customer_taxable_address callback see and override the billing address.
	 *
	 * Plugins such as One Stop Shop for WooCommerce replace the address at the default priority, for example with the shop base address for VAT-exempt customers.
	 */
	public function test_later_taxable_address_callback_sees_and_overrides_billing_address(): void {
		$this->add_product_to_cart( true );
		$this->set_checkout_context();
		$received_address = null;
		$base_address     = array( 'DE', 'BE', '10115', 'Berlin' );
		$callback         = function ( $address ) use ( &$received_address, $base_address ) {
			$received_address = $address;
			return $base_address;
		};
		add_filter( 'woocommerce_customer_taxable_address', $callback );

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $received_address, 'A callback at the default priority should receive the billing address.' );
		$this->assertSame( $base_address, $result, 'The address returned by a callback at the default priority should be used.' );
	}

	/**
	 * @testdox Keeps the shipping address when the woocommerce_apply_billing_tax_for_cart_without_shipping filter returns false.
	 */
	public function test_keeps_shipping_address_when_opt_out_filter_returns_false(): void {
		$this->add_product_to_cart( true );
		$this->set_checkout_context();
		$received_args = array();
		$callback      = function ( $use_billing, $customer, $cart ) use ( &$received_args ) {
			$received_args = array(
				'use_billing' => $use_billing,
				'customer'    => $customer,
				'cart'        => $cart,
			);
			return false;
		};
		add_filter( 'woocommerce_apply_billing_tax_for_cart_without_shipping', $callback, 10, 3 );

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'Returning false should keep the shipping address.' );
		$this->assertSame(
			array(
				'use_billing' => true,
				'customer'    => $this->customer,
				'cart'        => WC()->cart,
			),
			$received_args,
			'The filter should receive true, the customer whose taxable address is being resolved, and the customer\'s cart.'
		);
	}

	/**
	 * @testdox Cannot turn the billing address fallback on for a cart with products that need shipping.
	 *
	 * The woocommerce_apply_billing_tax_for_cart_without_shipping filter only fires when the cart qualifies.
	 */
	public function test_opt_out_filter_cannot_enable_billing_address_for_cart_needing_shipping(): void {
		$this->add_product_to_cart( true );
		$this->add_product_to_cart( false );
		$this->set_checkout_context();
		add_filter( 'woocommerce_apply_billing_tax_for_cart_without_shipping', '__return_true' );

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'US', 'CA', '90210', 'Beverly Hills' ), $result, 'The filter should not turn the fallback on for a cart with products that need shipping.' );
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
	 * @testdox Uses the billing address when a local pickup choice is left in the session of a cart without shipping.
	 *
	 * The session keeps the chosen shipping method until it is saved for a cart without shippable products,
	 * so a local pickup choice can outlive the removal of the last shippable item within a request.
	 */
	public function test_uses_billing_address_when_local_pickup_is_left_in_session(): void {
		$this->add_product_to_cart( true );
		$this->choose_local_pickup_in_us();
		$this->set_checkout_context();

		$result = $this->customer->get_taxable_address();

		$this->assertSame( array( 'GB', 'LND', 'SW1A 1AA', 'London' ), $result, 'A local pickup choice left in the session should not replace the billing address.' );
	}

	/**
	 * @testdox Uses the billing address for totals once the last shippable item is removed with local pickup chosen.
	 *
	 * Removing an item recalculates the totals before the session drops the local pickup choice.
	 */
	public function test_uses_billing_address_for_totals_after_last_shippable_item_is_removed_with_local_pickup(): void {
		$this->create_billing_country_tax_rate();
		$this->add_product_to_cart( true );
		$shippable_item_key = $this->add_product_to_cart( false );
		$this->choose_local_pickup_in_us();
		$this->set_checkout_context();

		WC()->cart->remove_cart_item( $shippable_item_key );

		$this->assertEquals( 2.0, WC()->cart->get_total_tax(), 'Cart totals should include the billing country tax without waiting for the next request.' );
	}

	/**
	 * @testdox Uses the local pickup location for totals when a cart product needs shipping.
	 *
	 * Only the pickup location's postcode has a tax rate, so no other address can produce the expected tax.
	 */
	public function test_uses_local_pickup_location_for_totals_when_product_needs_shipping(): void {
		$this->create_billing_country_tax_rate();
		$this->create_tax_rate( 'US', '10.0000', '90001' );
		$this->add_product_to_cart( false );
		$this->choose_local_pickup_in_us();
		$this->set_checkout_context();

		$taxable_address = $this->customer->get_taxable_address();
		WC()->cart->calculate_totals();

		$this->assertSame( array( 'US', 'CA', '90001', 'Los Angeles' ), $taxable_address, 'A cart product that needs shipping should keep the local pickup location as the taxable address.' );
		// Shipping methods left enabled by other tests can add shipping tax, so check the item tax only.
		$this->assertEquals( 1.0, WC()->cart->get_cart_contents_tax(), 'Cart item tax should use the local pickup location rate.' );
	}

	/**
	 * Set up a virtual cart item with a physical cross-sell and different billing and shipping tax rates.
	 *
	 * @return \WC_Product Physical cross-sell product.
	 */
	private function set_up_cross_sell_tax_fixture(): \WC_Product {
		$this->customer->set_billing_location( 'HU', '', '', '' );
		$this->create_tax_rate( 'HU', '27.0000' );
		$this->create_tax_rate( 'US', '10.0000' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );

		$cross_sell = \WC_Helper_Product::create_simple_product();
		$cross_sell->set_price( 100 );
		$cross_sell->set_regular_price( 100 );
		$cross_sell->save();

		$cart_product = \WC_Helper_Product::create_simple_product();
		$cart_product->set_virtual( true );
		$cart_product->set_cross_sell_ids( array( $cross_sell->get_id() ) );
		$cart_product->save();
		WC()->cart->add_to_cart( $cart_product->get_id() );

		return $cross_sell;
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param bool $virtual Whether the product is virtual.
	 * @return string The cart item key.
	 */
	private function add_product_to_cart( bool $virtual ): string {
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_virtual( $virtual );
		$product->save();

		return (string) WC()->cart->add_to_cart( $product->get_id() );
	}

	/**
	 * Choose local pickup at a US location, which the Blocks local pickup filter uses as the taxable address.
	 */
	private function choose_local_pickup_in_us(): void {
		update_option(
			'pickup_location_pickup_locations',
			array(
				array(
					'name'    => 'US store',
					'address' => array(
						'address_1' => '1 Main St',
						'city'      => 'Los Angeles',
						'state'     => 'CA',
						'postcode'  => '90001',
						'country'   => 'US',
					),
					'details' => '',
					'enabled' => true,
				),
			)
		);
		WC()->session->set( 'chosen_shipping_methods', array( 'pickup_location:0' ) );
	}

	/**
	 * Enable taxes with a 20% standard rate for the billing country only.
	 */
	private function create_billing_country_tax_rate(): void {
		$this->create_tax_rate( 'GB', '20.0000' );
	}

	/**
	 * Enable taxes with a standard rate for a country, optionally limited to a postcode.
	 *
	 * @param string $country  Country code.
	 * @param string $rate     Tax rate percentage.
	 * @param string $postcode Postcode the rate is limited to, or empty for the whole country.
	 */
	private function create_tax_rate( string $country, string $rate, string $postcode = '' ): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => $country,
				'tax_rate_state'    => '',
				'tax_rate'          => $rate,
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => '',
			)
		);

		if ( '' !== $postcode ) {
			\WC_Tax::_update_tax_rate_postcodes( $tax_rate_id, $postcode );
		}
	}

	/**
	 * Set the request context to checkout.
	 */
	private function set_checkout_context(): void {
		add_filter( 'woocommerce_is_checkout', '__return_true' );
	}
}
