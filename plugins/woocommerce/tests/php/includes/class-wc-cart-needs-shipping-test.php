<?php
/**
 * Tests for WC_Cart::needs_shipping().
 *
 * @package WooCommerce\Tests\Cart
 */

declare( strict_types = 1 );

/**
 * Tests whether a cart asks the shopper for shipping at all.
 *
 * The expectations come from the General settings screen, whose "Shipping location(s)" select
 * offers "Disable shipping & shipping calculations" as the supported way to switch shipping off,
 * and from a product's Virtual checkbox, described as "Virtual products are intangible and are not
 * shipped."
 */
class WC_Cart_Needs_Shipping_Test extends WC_Unit_Test_Case {

	/**
	 * Put a shippable product in the cart.
	 */
	public function setUp(): void {
		parent::setUp();

		// The shipping methods are memoized on the WC_Shipping singleton, which the database
		// rollback does not undo, so an earlier test's configured method would otherwise still be
		// counted here. This file needs to start from none.
		WC()->shipping()->unregister_shipping_methods();
		WC_Cache_Helper::get_transient_version( 'shipping', true );

		$product = WC_Helper_Product::create_simple_product();
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ), 'The fixture product should reach the cart.' );
	}

	/**
	 * Offer a flat rate from the Rest of the World zone.
	 *
	 * @param bool $enabled Whether the instance is switched on.
	 * @return int The new instance id.
	 */
	private function zone_offers_a_flat_rate( bool $enabled = true ): int {
		$instance_id = WC_Shipping_Zones::get_zone( 0 )->add_shipping_method( 'flat_rate' );

		if ( ! $enabled ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_shipping_zone_methods',
				array( 'is_enabled' => 0 ),
				array( 'instance_id' => $instance_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		WC_Cache_Helper::get_transient_version( 'shipping', true );

		return $instance_id;
	}

	/**
	 * @testdox With no shipping method defined anywhere, the cart does not ask about shipping.
	 */
	public function test_with_no_method_defined_the_cart_does_not_ask_about_shipping(): void {
		$this->assertSame( 0, wc_get_shipping_method_count( true ), 'The fixture should start with no method at all.' );

		$this->assertFalse( WC()->cart->needs_shipping(), 'There is nothing to ship with, so there is nothing to ask.' );
	}

	/**
	 * @testdox With a method defined, a cart holding something physical asks about shipping.
	 */
	public function test_with_a_method_defined_a_physical_cart_asks_about_shipping(): void {
		$this->zone_offers_a_flat_rate();

		$this->assertTrue( WC()->cart->needs_shipping(), 'Something in the cart has to be shipped, and there is a method to ship it with.' );
	}

	/**
	 * "Disable shipping & shipping calculations" is the supported way to switch shipping off, and it
	 * has to win over a method still sitting in a zone.
	 *
	 * @testdox With shipping switched off, the cart does not ask about it even with a method defined.
	 */
	public function test_with_shipping_switched_off_the_cart_does_not_ask(): void {
		$this->zone_offers_a_flat_rate();
		update_option( 'woocommerce_ship_to_countries', 'disabled' );

		$this->assertFalse( wc_shipping_enabled(), 'The fixture should have shipping switched off.' );
		$this->assertFalse( WC()->cart->needs_shipping(), 'The merchant has switched shipping off, so nothing should be asked.' );
	}

	/**
	 * Records what happens today, which is not the same as endorsing it. `needs_shipping()` counts
	 * instances through `wc_get_shipping_method_count( true )`, whose first argument is
	 * `$include_legacy` and not `$enabled_only`, so a switched-off instance still counts and the
	 * shopper is asked for a shipping method the store cannot offer.
	 *
	 * woocommerce#56507 reported that and was closed as not planned, on the grounds that it is an
	 * edge case and that a merchant who wants shipping off has the setting above. Two things are
	 * worth knowing before relying on that. The reason given was that stores disable methods
	 * conditionally by user type, but this count reads `is_enabled` straight from the zone methods
	 * table behind a transient, so conditional availability, which happens later in
	 * `is_available()`, cannot reach it. And the test below shows the rule is not applied
	 * consistently in the first place.
	 *
	 * @testdox A method that exists but is switched off still makes the cart ask about shipping.
	 */
	public function test_a_switched_off_method_still_makes_the_cart_ask(): void {
		$this->zone_offers_a_flat_rate( false );

		$this->assertSame( 0, wc_get_shipping_method_count( false, true ), 'No zone instance should be counted as enabled.' );
		$this->assertTrue( WC()->cart->needs_shipping(), 'Recorded behaviour: an instance that exists counts, enabled or not.' );
	}

	/**
	 * A method that predates shipping zones is counted only while it is switched on, the opposite of
	 * the zone instance above: the count has separate branches and only the zone branch ignores the
	 * switch. This is not only about old methods. Block Local Pickup declares `local-pickup` and not
	 * `shipping-zones`, so it is counted here too, which means switching off Local Pickup is obeyed
	 * while switching off every zone method is not.
	 *
	 * @testdox A switched-off method from before shipping zones does not make the cart ask.
	 *
	 * @testWith ["yes", true]
	 *           ["no", false]
	 *
	 * @param string $enabled  What the method's own setting holds.
	 * @param bool   $expected Whether the cart should ask about shipping.
	 */
	public function test_a_method_from_before_shipping_zones_is_counted_only_while_switched_on( string $enabled, bool $expected ): void {
		update_option(
			'woocommerce_flat_rate_settings',
			array(
				'enabled'    => $enabled,
				'title'      => 'Flat rate',
				'tax_status' => 'taxable',
				'cost'       => 10,
			)
		);
		update_option( 'woocommerce_flat_rate', array() );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping()->load_shipping_methods();

		$this->assertSame( $expected, WC()->cart->needs_shipping(), 'A method from before zones, switched ' . $enabled . '.' );
	}

	/**
	 * A merchant can mark a product virtual while it is already in a shopper's cart. The cart holds
	 * the product as it was when it was added, so the change is seen only once the cart is read
	 * again. This records the mechanism rather than a promise: what a real page load does is more
	 * than the re-read below, and no screen describes the gap.
	 *
	 * @testdox A product made virtual while in the cart is only noticed when the cart is read again.
	 */
	public function test_a_product_made_virtual_while_in_the_cart_stops_the_asking(): void {
		$this->zone_offers_a_flat_rate();
		$this->assertTrue( WC()->cart->needs_shipping(), 'The cart should ask about shipping to begin with.' );

		$item    = current( WC()->cart->get_cart() );
		$product = wc_get_product( $item['product_id'] );
		$product->set_virtual( true );
		$product->save();

		$this->assertTrue(
			WC()->cart->needs_shipping(),
			'The page the shopper already has open still holds the product as it was.'
		);

		WC()->cart->get_cart_from_session();

		$this->assertFalse(
			WC()->cart->needs_shipping(),
			'Once the cart is read again, nothing in it has to be shipped.'
		);
	}

	/**
	 * An extension can also answer for a single product rather than for the whole cart, which is how
	 * things like bookings and service products drop out of shipping.
	 *
	 * @testdox An extension can say a single product needs no shipping.
	 */
	public function test_an_extension_can_say_a_single_product_needs_no_shipping(): void {
		$this->zone_offers_a_flat_rate();
		$this->assertTrue( WC()->cart->needs_shipping(), 'The cart should ask about shipping to begin with.' );

		add_filter( 'woocommerce_product_needs_shipping', '__return_false' );

		$this->assertFalse( WC()->cart->needs_shipping(), 'With nothing in the cart needing shipping, there is nothing to ask.' );
	}

	/**
	 * @testdox An extension can decide for itself whether the cart asks about shipping.
	 */
	public function test_an_extension_can_decide_whether_the_cart_asks(): void {
		$this->zone_offers_a_flat_rate();

		add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );

		$this->assertFalse( WC()->cart->needs_shipping(), 'The filter should be able to withdraw the shipping step.' );
	}
}
