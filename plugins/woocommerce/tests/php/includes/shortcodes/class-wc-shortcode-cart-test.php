<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Shortcode_Cart.
 *
 * @package WooCommerce\Tests\Shortcodes
 */

/**
 * Class WC_Shortcode_Cart_Test.
 */
class WC_Shortcode_Cart_Test extends WC_Unit_Test_Case {

	/**
	 * Location props of `WC()->customer` before the test, since the calculator writes to the shared customer.
	 *
	 * @var array<string, string>
	 */
	private $original_customer_location = array();

	/**
	 * Remember the shared customer state this test may change.
	 */
	public function setUp(): void {
		parent::setUp();

		$customer = WC()->customer;

		$this->original_customer_location = array(
			'billing_country'   => $customer->get_billing_country(),
			'billing_state'     => $customer->get_billing_state(),
			'billing_postcode'  => $customer->get_billing_postcode(),
			'billing_city'      => $customer->get_billing_city(),
			'shipping_country'  => $customer->get_shipping_country(),
			'shipping_state'    => $customer->get_shipping_state(),
			'shipping_postcode' => $customer->get_shipping_postcode(),
			'shipping_city'     => $customer->get_shipping_city(),
		);

		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_default_customer_address', 'base' );
	}

	/**
	 * Restore the request and customer state touched by this test.
	 */
	public function tearDown(): void {
		unset( $_POST['calc_shipping_country'], $_POST['calc_shipping_state'], $_POST['calc_shipping_postcode'], $_POST['calc_shipping_city'] );

		WC()->customer->set_props( $this->original_customer_location );
		WC()->customer->save();

		parent::tearDown();
	}

	/**
	 * @testdox Should use the only shipping country and keep the postcode when the calculator does not submit a country.
	 */
	public function test_single_shipping_country_is_used_when_country_is_not_submitted(): void {
		$this->set_shipping_countries( array( 'GB' ) );
		$_POST['calc_shipping_postcode'] = 'sw1a1aa';

		WC_Shortcode_Cart::calculate_shipping();

		$this->assertSame( 'GB', WC()->customer->get_shipping_country(), 'The only shipping country should be used' );
		$this->assertSame( 'SW1A 1AA', WC()->customer->get_shipping_postcode(), 'The postcode should be kept and formatted for that country' );
	}

	/**
	 * @testdox Should discard a posted state that does not belong to the only shipping country.
	 * @testWith ["CA", "CA"]
	 *           ["ON", ""]
	 *
	 * @param string $posted_state   State submitted by the calculator.
	 * @param string $expected_state State expected on the customer.
	 */
	public function test_posted_state_is_checked_against_single_shipping_country( string $posted_state, string $expected_state ): void {
		$this->set_shipping_countries( array( 'US' ) );
		$_POST['calc_shipping_state']    = $posted_state;
		$_POST['calc_shipping_postcode'] = '90210';

		WC_Shortcode_Cart::calculate_shipping();

		$this->assertSame( 'US', WC()->customer->get_shipping_country() );
		$this->assertSame( $expected_state, WC()->customer->get_shipping_state() );
		$this->assertSame( '90210', WC()->customer->get_shipping_postcode() );
	}

	/**
	 * @testdox Should let a country set by woocommerce_cart_calculate_shipping_address take precedence over the fallback.
	 */
	public function test_address_filter_country_takes_precedence_over_fallback(): void {
		$this->set_shipping_countries( array( 'PT' ) );
		$_POST['calc_shipping_postcode'] = '01310-100';
		add_filter(
			'woocommerce_cart_calculate_shipping_address',
			function ( $address ) {
				if ( ! $address['country'] && WC_Validation::is_postcode( $address['postcode'], 'BR' ) ) {
					$address['country'] = 'BR';
				}
				return $address;
			}
		);

		WC_Shortcode_Cart::calculate_shipping();

		$this->assertSame( 'BR', WC()->customer->get_shipping_country(), 'The filter should see an empty country and its choice should be kept' );
		$this->assertSame( '01310100', WC()->customer->get_shipping_postcode(), 'The postcode should not be reformatted for the fallback country' );
	}

	/**
	 * @testdox Should reset to the store base and flag incorrect usage when a store with several shipping countries gets no country.
	 */
	public function test_multiple_shipping_countries_reset_to_base_when_country_is_not_submitted(): void {
		$this->set_shipping_countries( array( 'GB', 'IE' ) );
		$_POST['calc_shipping_postcode'] = 'D02 X285';
		$this->setExpectedIncorrectUsage( 'WC_Shortcode_Cart::calculate_shipping' );

		WC_Shortcode_Cart::calculate_shipping();

		$this->assertSame( 'GB', WC()->customer->get_shipping_country(), 'The location should be reset to the store base' );
		$this->assertSame( '', WC()->customer->get_shipping_postcode() );
	}

	/**
	 * @testdox Should not apply the fallback when the calculator submits an empty country.
	 */
	public function test_empty_submitted_country_resets_to_base_without_fallback(): void {
		$this->set_shipping_countries( array( 'GB', 'IE' ) );
		$_POST['calc_shipping_country']  = '';
		$_POST['calc_shipping_postcode'] = 'D02 X285';

		WC_Shortcode_Cart::calculate_shipping();

		$this->assertSame( 'GB', WC()->customer->get_shipping_country() );
		$this->assertSame( '', WC()->customer->get_shipping_postcode() );
	}

	/**
	 * Configure the countries the store sells and ships to.
	 *
	 * @param string[] $countries Country codes.
	 */
	private function set_shipping_countries( array $countries ): void {
		update_option( 'woocommerce_allowed_countries', 'specific' );
		update_option( 'woocommerce_specific_allowed_countries', $countries );
		update_option( 'woocommerce_ship_to_countries', '' );
	}
}
