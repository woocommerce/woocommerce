<?php
/**
 * Tests for wc_get_default_shipping_method_for_package().
 *
 * @package WooCommerce\Tests\Includes
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Shipping\ShippingMethodOriginTracker;

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
	 * Tracker for chosen shipping method origins.
	 *
	 * @var ShippingMethodOriginTracker
	 */
	private $origin_tracker;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->origin_tracker = wc_get_container()->get( ShippingMethodOriginTracker::class );

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
		WC()->session->set( 'chosen_shipping_methods', null );
		WC()->session->set( ShippingMethodOriginTracker::SESSION_KEY, null );
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

	/**
	 * Helper: simulate the auto-defaulter having selected a rate for a package
	 * by writing the session entries it would write.
	 *
	 * @param int|string $key      Package key.
	 * @param string     $rate_id  Rate id (e.g. 'local_pickup:1').
	 */
	private function record_auto_choice( $key, string $rate_id ): void {
		$chosen         = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen[ $key ] = $rate_id;
		WC()->session->set( 'chosen_shipping_methods', $chosen );
		$this->origin_tracker->set_origin( $key, 'auto', $rate_id );
	}

	/**
	 * When the previous Local Pickup choice came from the auto-defaulter and a
	 * shipping rate is now available, the auto pickup should be replaced with
	 * the shipping rate. This is the WOOPMNT-6159 / Apple Pay scenario.
	 *
	 * @testdox Replaces auto-chosen local pickup with shipping rate when one is now available.
	 */
	public function test_replaces_auto_local_pickup_when_shipping_rate_becomes_available(): void {
		$this->record_auto_choice( 0, 'local_pickup:1' );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'flat_rate:1', $result, 'Should replace auto-chosen pickup with the first non-pickup rate' );
	}

	/**
	 * When the customer explicitly selected Local Pickup (origin = manual), the
	 * sticky behavior must be preserved even when a shipping rate is available.
	 *
	 * @testdox Preserves manually chosen local pickup even when a shipping rate is available.
	 */
	public function test_preserves_manual_local_pickup_when_shipping_rate_available(): void {
		$chosen    = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen[0] = 'local_pickup:1';
		WC()->session->set( 'chosen_shipping_methods', $chosen );
		$this->origin_tracker->set_origin( 0, 'manual', 'local_pickup:1' );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'local_pickup:1', $result, 'Should preserve manually chosen pickup' );
	}

	/**
	 * Sessions that pre-date origin tracking default to 'manual' to preserve
	 * existing sticky behavior; without a recorded origin we cannot tell the
	 * choice was an auto-default, so we must not silently switch it.
	 *
	 * @testdox Defaults unrecorded origin to manual (backwards-compat).
	 */
	public function test_unrecorded_origin_preserves_local_pickup(): void {
		// Mirror the actual pre-upgrade session state: the auto-defaulter had already written pickup
		// into chosen_shipping_methods before this PR shipped, but no origin was ever recorded.
		$chosen    = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen[0] = 'local_pickup:1';
		WC()->session->set( 'chosen_shipping_methods', $chosen );
		WC()->session->set( ShippingMethodOriginTracker::SESSION_KEY, null );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'local_pickup:1', $result, 'Unrecorded origin should be treated as manual' );
	}

	/**
	 * When pickup is the only rate available, even an auto origin must keep
	 * pickup — there is nothing else to switch to.
	 *
	 * @testdox Keeps auto local pickup when no shipping alternative exists.
	 */
	public function test_keeps_auto_local_pickup_when_no_alternative(): void {
		$this->record_auto_choice( 0, 'local_pickup:1' );

		$package = $this->build_package( array( 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'local_pickup:1', $result, 'Should keep pickup when no non-pickup alternative is available' );
	}

	/**
	 * `wc_get_chosen_shipping_method_for_package()` is the path through which
	 * the auto-defaulter writes to the session. After it runs, the origin must
	 * be recorded as 'auto' so a subsequent re-evaluation can unstick if a
	 * shipping rate becomes available.
	 *
	 * @testdox Auto-defaulter records the chosen shipping method origin as 'auto'.
	 */
	public function test_auto_defaulter_records_auto_origin(): void {
		$package = $this->build_package( array( 'local_pickup:1' ) );

		wc_get_chosen_shipping_method_for_package( 0, $package );

		$this->assertSame( 'auto', $this->origin_tracker->get_origin( 0 ) );
	}

	/**
	 * ShippingMethodOriginTracker::set_origin() is the helper manual-write paths
	 * call (Store API select-shipping-rate, AJAX update_shipping_method, etc.).
	 * Origin must round-trip correctly through the session.
	 *
	 * @testdox Manual write paths can flip the origin from auto to manual.
	 */
	public function test_manual_write_overrides_auto_origin(): void {
		$this->record_auto_choice( 0, 'local_pickup:1' );
		$this->assertSame( 'auto', $this->origin_tracker->get_origin( 0 ) );

		$this->origin_tracker->set_origin( 0, 'manual', 'local_pickup:1' );
		$this->assertSame( 'manual', $this->origin_tracker->get_origin( 0 ) );
	}

	/**
	 * Third-party plugins occasionally write directly to `chosen_shipping_methods`
	 * in the WC session without going through Store API, the AJAX endpoints, or
	 * ShippingMethodOriginTracker::set_origin(). The recorded origin must invalidate
	 * itself in that case, so a stale 'auto' marker can't override a third party's
	 * deliberate choice on a subsequent re-evaluation.
	 *
	 * @testdox External write to chosen_shipping_methods invalidates the recorded auto origin.
	 */
	public function test_external_chosen_shipping_methods_write_invalidates_auto_origin(): void {
		// Simulate the auto-defaulter having recorded pickup_location:1 as 'auto'.
		$this->record_auto_choice( 0, 'local_pickup:1' );
		$this->assertSame( 'auto', $this->origin_tracker->get_origin( 0 ) );

		// Simulate a third-party plugin overwriting the chosen rate directly.
		$chosen    = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen[0] = 'local_pickup:2';
		WC()->session->set( 'chosen_shipping_methods', $chosen );

		$this->assertSame(
			'manual',
			$this->origin_tracker->get_origin( 0 ),
			'External write to chosen_shipping_methods should invalidate the stale auto marker'
		);

		// And the auto-defaulter must not unstick the externally-chosen pickup.
		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:2' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:2' );

		$this->assertSame(
			'local_pickup:2',
			$result,
			'Externally-chosen pickup should be preserved (treated as manual)'
		);
	}

	/**
	 * In shortcode (classic checkout) context the pre-sticky default is simply the first rate in
	 * the package. When Local Pickup sorts first, that default is itself a pickup rate — the
	 * unstick must still fire and switch to the first non-pickup rate found in the package.
	 *
	 * @testdox Replaces auto-chosen pickup with the delivery rate in shortcode context even when pickup sorts first.
	 */
	public function test_replaces_auto_local_pickup_in_shortcode_context_when_pickup_sorts_first(): void {
		WC()->cart->cart_context = 'shortcode';
		$this->record_auto_choice( 0, 'local_pickup:1' );

		// Pickup deliberately ordered before the delivery rate.
		$package = $this->build_package( array( 'local_pickup:1', 'flat_rate:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'flat_rate:1', $result, 'Shortcode-context unstick should switch to the first non-pickup rate even when pickup sorts first' );
	}

	/**
	 * A different code path (e.g. WC_AJAX::update_order_review clearing chosen_shipping_methods
	 * when no shipping methods are posted — see the fix for #51197) may empty the chosen-methods
	 * session entry after an 'auto' origin was recorded. The stale origin must self-invalidate:
	 * the reader falls back to 'manual' and the stale marker cannot un-stick a later pickup choice.
	 *
	 * @testdox Clearing chosen_shipping_methods after an auto origin was recorded invalidates the stale marker.
	 */
	public function test_cleared_chosen_methods_invalidates_stale_auto_origin(): void {
		$this->record_auto_choice( 0, 'local_pickup:1' );
		$this->assertSame( 'auto', $this->origin_tracker->get_origin( 0 ) );

		// Simulate another code path emptying the chosen methods entirely.
		WC()->session->set( 'chosen_shipping_methods', array() );

		$this->assertSame(
			'manual',
			$this->origin_tracker->get_origin( 0 ),
			'A cleared chosen_shipping_methods entry should invalidate the recorded auto origin'
		);

		// A subsequent explicit pickup choice (all real selection paths record 'manual') must stay
		// sticky — the earlier stale 'auto' record must not leak into the new choice.
		$chosen    = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen[0] = 'local_pickup:1';
		WC()->session->set( 'chosen_shipping_methods', $chosen );
		$this->origin_tracker->set_origin( 0, 'manual', 'local_pickup:1' );

		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_default_shipping_method_for_package( 0, $package, 'local_pickup:1' );

		$this->assertSame( 'local_pickup:1', $result, 'Pickup explicitly chosen after the clear should be preserved' );
	}

	/**
	 * Regression: when the auto-defaulter runs and the sticky-pickup path preserves a
	 * manually-chosen Local Pickup, `wc_get_chosen_shipping_method_for_package()` must not
	 * overwrite the recorded origin back to 'auto'. Otherwise a subsequent re-evaluation
	 * would treat the deliberate pickup as auto-defaulted and un-stick it — the inverse of
	 * the bug this change fixes.
	 *
	 * @testdox Auto-defaulter preserves a manual origin when it keeps the customer's chosen pickup.
	 */
	public function test_manual_origin_survives_auto_defaulter_recalculation(): void {
		// Customer explicitly chose Local Pickup.
		$chosen    = WC()->session->get( 'chosen_shipping_methods', array() );
		$chosen[0] = 'local_pickup:1';
		WC()->session->set( 'chosen_shipping_methods', $chosen );
		$this->origin_tracker->set_origin( 0, 'manual', 'local_pickup:1' );

		// A non-pickup rate is now available, forcing the auto-defaulter to re-evaluate.
		$package = $this->build_package( array( 'flat_rate:1', 'local_pickup:1' ) );
		$result  = wc_get_chosen_shipping_method_for_package( 0, $package );

		$this->assertSame( 'local_pickup:1', $result, 'Manually chosen pickup should be preserved' );
		$this->assertSame(
			'manual',
			$this->origin_tracker->get_origin( 0 ),
			'Preserving a manual choice must not overwrite the origin back to auto'
		);
	}
}
