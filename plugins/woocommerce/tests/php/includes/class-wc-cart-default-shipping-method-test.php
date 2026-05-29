<?php
/**
 * Tests for wc_get_default_shipping_method_for_package().
 *
 * @package WooCommerce\Tests\Includes
 */

declare( strict_types = 1 );

/**
 * Tests for wc_get_default_shipping_method_for_package().
 */
class WC_Cart_Default_Shipping_Method_Test extends WC_Unit_Test_Case {

	/**
	 * Shipping zone used across tests.
	 *
	 * @var WC_Shipping_Zone
	 */
	private $zone;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a shipping zone with a flat rate so CartCheckoutUtils::shipping_methods_exist() returns true.
		$this->zone = new WC_Shipping_Zone();
		$this->zone->set_zone_name( 'Test Zone' );
		$this->zone->save();
		$this->zone->add_shipping_method( 'flat_rate' );

		// Flush the shipping method count transient so the new zone is picked up.
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		delete_transient( 'wc_shipping_method_count' );

		// Set block checkout context (not shortcode).
		WC()->cart->cart_context = 'store-api';
	}

	/**
	 * Block-based page IDs created on demand to simulate a block store.
	 *
	 * @var int[]
	 */
	private $block_page_ids = array();

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->zone->delete( true );
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		delete_option( 'woocommerce_pickup_location_settings' );
		WC()->cart->cart_context = 'shortcode';

		foreach ( $this->block_page_ids as $key => $page_id ) {
			wp_delete_post( $page_id, true );
			delete_option( 'woocommerce_' . $key . '_page_id' );
		}
		$this->block_page_ids = array();

		parent::tearDown();
	}

	/**
	 * Create a page containing the requested WooCommerce block and assign it as the active
	 * cart/checkout page so CartCheckoutUtils::is_*_block_default() returns true.
	 *
	 * @param string $key   Either 'cart' or 'checkout'.
	 * @param string $block Block name to embed in the page content.
	 */
	private function make_page_block_based( string $key, string $block ): void {
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => ucfirst( $key ) . ' (block)',
				'post_content' => '<!-- wp:' . $block . ' /-->',
			)
		);
		update_option( 'woocommerce_' . $key . '_page_id', $page_id );
		$this->block_page_ids[ $key ] = $page_id;
	}

	/**
	 * Persist the Local Pickup settings used by the SUT.
	 *
	 * @param string $auto_select_pickup_tab Either 'yes' or 'no'.
	 */
	private function set_auto_select_pickup_tab( string $auto_select_pickup_tab ): void {
		update_option(
			'woocommerce_pickup_location_settings',
			array(
				'enabled'                => 'yes',
				'title'                  => 'Pickup',
				'tax_status'             => 'taxable',
				'cost'                   => '',
				'auto_select_pickup_tab' => $auto_select_pickup_tab,
			)
		);
	}

	/**
	 * Build a test shipping package with the given rate keys.
	 *
	 * @param array $rate_keys e.g. ['flat_rate:1', 'local_pickup:1'].
	 * @return array
	 */
	private function build_package( array $rate_keys ): array {
		$rates = array();
		foreach ( $rate_keys as $rate_key ) {
			$method_id          = current( explode( ':', $rate_key ) );
			$rates[ $rate_key ] = new WC_Shipping_Rate( $rate_key, ucfirst( $method_id ), '10', array(), $method_id );
		}
		return array( 'rates' => $rates );
	}

	/**
	 * Clear the customer shipping address.
	 */
	private function clear_customer_address(): void {
		WC()->customer->set_shipping_country( '' );
		WC()->customer->set_shipping_state( '' );
		WC()->customer->set_shipping_postcode( '' );
		WC()->customer->set_shipping_city( '' );
	}

	/**
	 * Set a full customer shipping address so WC_Customer::has_full_shipping_address() returns true.
	 */
	private function set_full_customer_address(): void {
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_postcode( '90210' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );
	}

	/**
	 * Test default method with only pickup rates and no address.
	 *
	 * @testdox Returns empty string when only pickup rates remain and hide-shipping-costs is enabled with no address.
	 */
	public function test_returns_empty_when_only_pickup_and_no_address(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->clear_customer_address();

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( '', $result, 'Should not auto-select pickup when shipping costs are hidden and no address entered' );
	}

	/**
	 * The "Hide shipping costs until an address is entered" setting takes precedence over the
	 * "Auto-select local pickup tab" setting: when both are enabled but the customer has no
	 * address, no default rate should be surfaced.
	 *
	 * @testdox Returns empty when only pickup is available and hide-shipping-costs is enabled, even if the merchant opted in to auto-select pickup.
	 */
	public function test_returns_empty_when_hide_shipping_costs_overrides_auto_select_pickup(): void {
		$this->set_auto_select_pickup_tab( 'yes' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->clear_customer_address();

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( '', $result, 'Hide-shipping-costs should win over the pickup auto-default when no address is set' );
	}

	/**
	 * Test default method with both shipping and pickup rates.
	 *
	 * @testdox Returns a shipping rate when both shipping and pickup rates exist.
	 */
	public function test_returns_shipping_rate_when_shipping_and_pickup_available(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->clear_customer_address();

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( 'flat_rate:1', $result, 'Should select the first non-pickup shipping rate' );
	}

	/**
	 * A real shipping rate always wins as the default, even when hide-shipping-costs is enabled and
	 * the customer has a full address. Shipping is chosen in the rate loop before the address gate is
	 * ever evaluated, so this locks in shipping priority rather than the address-gate behaviour.
	 *
	 * @testdox A shipping rate is selected when both rates exist, hide-shipping-costs is enabled and the customer has a full address.
	 */
	public function test_shipping_rate_wins_when_hide_shipping_costs_enabled_and_address_complete(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->set_full_customer_address();

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( 'flat_rate:1', $result, 'A shipping rate should win regardless of the address gate' );
	}

	/**
	 * Once the customer has entered a full address, the "Hide shipping costs until an address is
	 * entered" gate no longer applies, so a pickup-only package falls through to the pickup
	 * auto-default when the merchant opted in. This exercises the negative branch of the address
	 * gate ( has_full_shipping_address() === true ), which is otherwise uncovered.
	 *
	 * @testdox Auto-selects pickup for a pickup-only package when hide-shipping-costs is enabled and the customer has a full address.
	 */
	public function test_auto_selects_pickup_when_hide_shipping_costs_enabled_and_address_complete(): void {
		$this->set_auto_select_pickup_tab( 'yes' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->set_full_customer_address();

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( 'local_pickup:1', $result, 'With a full address the hide-shipping-costs gate is cleared, so pickup is auto-selected for a pickup-only package' );
	}

	/**
	 * Test default method preserves previously chosen pickup.
	 *
	 * @testdox Preserves local pickup when it was previously chosen by the customer.
	 */
	public function test_preserves_chosen_local_pickup(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'local_pickup:1', $result, 'Should preserve previously chosen local pickup' );
	}

	/**
	 * Test shortcode context is unaffected.
	 *
	 * @testdox Shortcode context always selects first rate regardless of settings.
	 */
	public function test_shortcode_context_unaffected(): void {
		WC()->cart->cart_context = 'shortcode';
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->clear_customer_address();

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( 'local_pickup:1', $result, 'Shortcode context should always select the first rate' );
	}

	/**
	 * @testdox Shipping rate takes priority over pickup even when auto-select pickup tab is enabled.
	 */
	public function test_shipping_takes_priority_over_pickup_when_both_available_and_auto_select_pickup_tab_yes(): void {
		$this->set_auto_select_pickup_tab( 'yes' );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( 'flat_rate:1', $result, 'Shipping should always win over pickup as the auto-default when a non-pickup rate is available' );
	}

	/**
	 * @testdox Selects pickup as default when only pickup is available and auto-select pickup tab is enabled.
	 */
	public function test_selects_pickup_when_only_pickup_available_and_auto_select_pickup_tab_yes(): void {
		$this->set_auto_select_pickup_tab( 'yes' );

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( 'local_pickup:1', $result, 'Should auto-select pickup when no shipping rate is available and merchant opted in' );
	}

	/**
	 * @testdox Returns empty when only pickup is available and auto-select pickup tab is disabled.
	 */
	public function test_returns_empty_when_only_pickup_available_and_auto_select_pickup_tab_no(): void {
		$this->set_auto_select_pickup_tab( 'no' );

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( '', $result, 'Should not auto-select pickup when merchant opted out' );
	}

	/**
	 * @testdox Preserves chosen pickup regardless of the auto-select pickup tab setting.
	 */
	public function test_preserves_chosen_pickup_regardless_of_auto_select_pickup_tab(): void {
		$this->set_auto_select_pickup_tab( 'no' );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'local_pickup:1', $result, 'An explicit pickup choice should not be reverted to shipping on reload' );
	}

	/**
	 * @testdox Does not auto-select pickup when the shopper had previously chosen a shipping rate that is no longer available.
	 */
	public function test_does_not_auto_select_pickup_when_prior_shipping_choice_vanished(): void {
		$this->set_auto_select_pickup_tab( 'yes' );

		// Shopper previously chose flat_rate:1, but it's no longer in the package after recalculation.
		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'flat_rate:1' );

		$this->assertSame( '', $result, 'Should leave the default empty rather than silently flipping a vanished shipping choice to pickup' );
	}

	/**
	 * Block cart/checkout runs shipping calculation during server-side render where the cart context
	 * is still 'shortcode'. The default-tab-aware path must apply to block-based stores in that case.
	 *
	 * @testdox Block-based stores route through the auto-select-aware path even when cart_context is 'shortcode' (server-side render).
	 */
	public function test_block_based_store_uses_auto_select_path_when_cart_context_is_shortcode(): void {
		$this->make_page_block_based( 'cart', 'woocommerce/cart' );
		$this->make_page_block_based( 'checkout', 'woocommerce/checkout' );

		// Reproduce the SSR condition: block-based store, but cart_context still reads 'shortcode'.
		WC()->cart->cart_context = 'shortcode';
		$this->set_auto_select_pickup_tab( 'no' );

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		// Legacy shortcode path would return current($rate_keys) = 'local_pickup:1' here.
		// The block-aware SSR path must honour the merchant opt-out and return ''.
		$this->assertSame(
			'',
			$result,
			'Block-based stores should honour the auto-select pickup tab opt-out even when cart_context is still the SSR-time shortcode value'
		);
	}

	/**
	 * @testdox Pickup is auto-selected for a pickup-only package when an existing store upgrades and the option lacks the auto_select_pickup_tab key.
	 */
	public function test_pickup_auto_selected_when_option_is_missing_the_auto_select_key(): void {
		// Simulate an existing store that has saved Local Pickup settings before this feature shipped:
		// the option exists but does not contain the new key. The isset() backfill in LocalPickupUtils
		// must preserve the prior behavior of auto-selecting pickup when it's the only available rate.
		update_option(
			'woocommerce_pickup_location_settings',
			array(
				'enabled'    => 'yes',
				'title'      => 'Pickup',
				'tax_status' => 'taxable',
				'cost'       => '',
			)
		);

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame(
			'local_pickup:1',
			$result,
			'Existing stores upgrading without the auto_select_pickup_tab key should keep auto-selecting pickup for pickup-only packages'
		);
	}
}
