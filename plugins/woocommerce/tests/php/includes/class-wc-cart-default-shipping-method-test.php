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
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		$this->zone->delete( true );
		update_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		WC()->cart->cart_context = 'shortcode';
		parent::tearDown();
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
	 * Test default method selects shipping rate when setting is enabled but address exists.
	 *
	 * @testdox Returns shipping rate when hide-shipping-costs is enabled but customer has a full address.
	 */
	public function test_returns_shipping_rate_when_setting_enabled_and_address_complete(): void {
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_postcode( '90210' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, '' );

		$this->assertSame( 'flat_rate:1', $result, 'Should select shipping rate when customer has a full address' );
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
}
