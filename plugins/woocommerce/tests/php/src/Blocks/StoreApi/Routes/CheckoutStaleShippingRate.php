<?php
/**
 * Tests for refusing a stale shipping selection at checkout.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\StoreApi\Utilities\OrderController;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

/**
 * A shopper who forces a shipping rate that no longer exists must be refused at payment, not
 * charged for it. OrderController::validate_selected_shipping_methods() is the gate: it throws when
 * a chosen rate id is not among the rates the package actually offers. Nothing pinned the stale-id
 * case before; the existing checkout test disables every method instead.
 */
class CheckoutStaleShippingRate extends ControllerTestCase {

	/**
	 * Instance id of the flat rate the shopper really can choose.
	 *
	 * @var int
	 */
	private $instance;

	/**
	 * Give the Rest of the World zone a flat rate, a cart that needs shipping, and a US address, so
	 * the package offers exactly one real rate to validate against.
	 */
	protected function setUp(): void {
		parent::setUp();

		$zone           = \WC_Shipping_Zones::get_zone( 0 );
		$this->instance = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $this->instance . '_settings',
			array(
				'title' => 'Flat rate',
				'cost'  => '5',
			)
		);
		\WC_Cache_Helper::get_transient_version( 'shipping', true );

		$product = \WC_Helper_Product::create_simple_product();
		wc_empty_cart();
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'CA' );
		WC()->customer->set_shipping_city( 'Beverly Hills' );
		WC()->customer->set_shipping_postcode( '90210' );
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_shipping();
		WC()->cart->calculate_totals();
	}

	/**
	 * Clear the chosen method so it cannot leak.
	 */
	protected function tearDown(): void {
		WC()->session->set( 'chosen_shipping_methods', array() );
		parent::tearDown();
	}

	/**
	 * @testdox The real rate the package offers passes validation.
	 */
	public function test_the_offered_rate_passes_validation(): void {
		$packages = WC()->shipping()->get_packages();
		$rate_ids = wp_list_pluck( $packages[0]['rates'], 'id' );

		// Prove setUp really put the rate on offer, so the no-throw below means something.
		$this->assertContains(
			'flat_rate:' . $this->instance,
			$rate_ids,
			'The cart built in setUp should offer the flat rate under test.'
		);

		// Throws on a rate the package does not offer; the offered rate must pass silently.
		( new OrderController() )->validate_selected_shipping_methods( true, array( 'flat_rate:' . $this->instance ) );
	}

	/**
	 * @testdox A chosen rate the package does not offer is refused.
	 */
	public function test_a_stale_rate_is_refused(): void {
		$controller = new OrderController();

		// A method the zone never offers, with a prefix that cannot be a substring of the real
		// flat_rate id, so the refusal cannot hinge on the auto-increment instance id.
		$stale_rate = 'local_pickup:' . ( $this->instance + 999 );

		$code = '';
		try {
			$controller->validate_selected_shipping_methods( true, array( $stale_rate ) );
		} catch ( RouteException $e ) {
			$code = $e->getErrorCode();
		}

		$this->assertSame(
			'woocommerce_rest_invalid_shipping_option',
			$code,
			'Forcing a rate the package does not offer should be refused with the invalid-shipping-option error.'
		);
	}
}
