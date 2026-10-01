<?php
/**
 * Controller tests for the cart select-shipping-rate route.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

use Automattic\WooCommerce\Tests\Blocks\Helpers\FixtureData;

/**
 * Behavioural tests for POST /cart/select-shipping-rate.
 *
 * The route stores which shipping rate a shopper chose and hands the cart back with that rate
 * marked selected. The blocks drive it when a shopper picks an option, and nothing exercised it
 * before: it appeared only in the route-registration lists. Expectations come from the route's own
 * contract, a required rate id and a shipping-enabled store.
 */
class CartSelectShippingRate extends ControllerTestCase {

	/**
	 * Instance ids of the two flat rates offered to the shopper.
	 *
	 * @var int[]
	 */
	private $instances = array();

	/**
	 * Give the Rest of the World zone two flat rates, a cart that needs shipping, and a US address
	 * so both rates are offered and one is selected by default.
	 */
	protected function setUp(): void {
		parent::setUp();

		$zone              = \WC_Shipping_Zones::get_zone( 0 );
		$this->instances[] = $zone->add_shipping_method( 'flat_rate' );
		$this->instances[] = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $this->instances[0] . '_settings',
			array(
				'title' => 'First',
				'cost'  => '5',
			)
		);
		update_option(
			'woocommerce_flat_rate_' . $this->instances[1] . '_settings',
			array(
				'title' => 'Second',
				'cost'  => '9',
			)
		);
		\WC_Cache_Helper::get_transient_version( 'shipping', true );

		$fixtures = new FixtureData();
		$product  = $fixtures->get_simple_product(
			array(
				'name'          => 'Shippable',
				'regular_price' => 10,
				'weight'        => 1,
			)
		);

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
	 * Reset the chosen method so it cannot leak into another test.
	 */
	protected function tearDown(): void {
		WC()->session->set( 'chosen_shipping_methods', array() );
		parent::tearDown();
	}

	/**
	 * Post a selection and return the decoded cart response.
	 *
	 * @param array $body Request body.
	 * @return array{status:int,data:array}
	 */
	private function select( array $body ): array {
		$request = new \WP_REST_Request( 'POST', '/wc/store/v1/cart/select-shipping-rate' );
		$request->set_header( 'Nonce', wp_create_nonce( 'wc_store_api' ) );
		$request->set_body_params( $body );
		$response = rest_get_server()->dispatch( $request );
		return array(
			'status' => $response->get_status(),
			'data'   => $response->get_data(),
		);
	}

	/**
	 * Whether the given rate id is marked selected in the first package of a cart response.
	 *
	 * @param array  $data    Cart response data.
	 * @param string $rate_id Rate id to look up.
	 * @return bool
	 */
	private function is_selected( array $data, string $rate_id ): bool {
		foreach ( $data['shipping_rates'][0]['shipping_rates'] as $rate ) {
			if ( $rate['rate_id'] === $rate_id ) {
				return (bool) $rate['selected'];
			}
		}
		$this->fail( 'Rate ' . $rate_id . ' was not offered.' );
	}

	/**
	 * @testdox Choosing a rate stores it and the cart comes back with that rate selected and the other not.
	 */
	public function test_selecting_a_rate_stores_it_and_marks_it_selected(): void {
		$first  = 'flat_rate:' . $this->instances[0];
		$second = 'flat_rate:' . $this->instances[1];

		$result = $this->select(
			array(
				'package_id' => 0,
				'rate_id'    => $second,
			)
		);

		$this->assertSame( 200, $result['status'], 'A valid selection should succeed.' );
		$this->assertTrue( $this->is_selected( $result['data'], $second ), 'The chosen rate should come back selected.' );
		$this->assertFalse( $this->is_selected( $result['data'], $first ), 'The rate not chosen should not be selected.' );
		$this->assertSame(
			array( $second ),
			WC()->session->get( 'chosen_shipping_methods' ),
			'The choice should be stored on the session, not only reflected in the response.'
		);
	}

	/**
	 * With no package id the route selects the rate for every package. With one package that is
	 * the same outcome, reached through the other branch of the route.
	 *
	 * @testdox Omitting the package id selects the rate for every package.
	 */
	public function test_omitting_the_package_id_selects_the_rate_for_every_package(): void {
		$second = 'flat_rate:' . $this->instances[1];

		$result = $this->select( array( 'rate_id' => $second ) );

		$this->assertSame( 200, $result['status'], 'A selection without a package id should succeed.' );
		$this->assertTrue( $this->is_selected( $result['data'], $second ), 'The chosen rate should come back selected.' );
		$this->assertSame(
			array( $second ),
			WC()->session->get( 'chosen_shipping_methods' ),
			'The single package should carry the chosen rate.'
		);
	}

	/**
	 * The rate id is a required argument, so a request without one is turned away by the route's
	 * argument contract before the handler runs.
	 *
	 * @testdox A selection with no rate id is refused as a missing required argument.
	 */
	public function test_a_request_with_no_rate_id_is_refused(): void {
		$result = $this->select( array( 'package_id' => 0 ) );

		$this->assertSame( 400, $result['status'], 'A request without a rate id should be rejected.' );
		$this->assertSame(
			'rest_missing_callback_param',
			$result['data']['code'],
			'The required-argument contract should turn it away, before the handler.'
		);
	}

	/**
	 * @testdox A selection is refused while shipping is switched off.
	 */
	public function test_a_selection_is_refused_while_shipping_is_switched_off(): void {
		$default = get_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_ship_to_countries', 'disabled' );

		try {
			$result = $this->select(
				array(
					'package_id' => 0,
					'rate_id'    => 'flat_rate:' . $this->instances[1],
				)
			);
		} finally {
			update_option( 'woocommerce_ship_to_countries', $default );
		}

		$this->assertSame( 404, $result['status'], 'With shipping disabled the route should refuse the selection.' );
		$this->assertSame( 'woocommerce_rest_shipping_disabled', $result['data']['code'], 'And say why.' );
	}
}
