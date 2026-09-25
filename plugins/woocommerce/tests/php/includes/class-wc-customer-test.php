<?php
/**
 * Unit tests for WC_Customer class.
 *
 * @package WooCommerce\Tests.
 */

declare( strict_types = 1 );

/**
 * Tests for WC_Customer class.
 */
class WC_Customer_Test extends \WC_Unit_Test_Case {
	/**
	 * Data provider: setters that write directly to a class property instead of going through set_prop().
	 *
	 * @return array<string,array>
	 */
	public function data_provider_set_prop_bypassing_setters(): array {
		return array(
			'password'            => array(
				false,
				fn( WC_Customer $customer ) => $customer->set_password( '***' ),
				fn( int $id ) => wp_check_password( '***', get_user_by( 'id', $id )->user_pass ),
			),
			'is_vat_exempt'       => array(
				true,
				fn( WC_Customer $customer ) => $customer->set_is_vat_exempt( true ),
				fn( int $id ) => ( new WC_Customer( $id, true ) )->get_is_vat_exempt(),
			),
			'calculated_shipping' => array(
				true,
				fn( WC_Customer $customer ) => $customer->set_calculated_shipping( true ),
				fn( int $id ) => ( new WC_Customer( $id, true ) )->get_calculated_shipping(),
			),
		);
	}

	/**
	 * @testdox Setters that bypass set_prop() must still persist their value when save() is called on a logged-in customer with no other pending changes.
	 * @dataProvider data_provider_set_prop_bypassing_setters
	 *
	 * @param bool     $use_session True = session data store (Block Checkout); false = DB data store.
	 * @param callable $set         Mutates the customer (calls the setter under test).
	 * @param callable $verify      Returns true when the value was persisted after save().
	 */
	public function test_set_prop_bypass_is_persisted_on_save( bool $use_session, callable $set, callable $verify ): void {
		$user_id = WC_Helper_Customer::create_customer()->get_id();

		if ( $use_session ) {
			WC()->session->init();
		}
		$customer = new WC_Customer( $user_id, $use_session );

		// Precondition: no pending WC_Data changes — same state extensions see at checkout.
		$this->assertEmpty( $customer->get_changes() );

		$set( $customer );
		$customer->save();

		$this->assertTrue( $verify( $user_id ) );
	}

	/**
	 * Test that customer object can be initialized even if wc session is not available.
	 * There are cases when WC()->session is null but we are reading customer object with $session param set to true, for example, when calling methods from WC_Checkout object.
	 */
	public function test_can_create_customer_without_wc_session_initialized() {
		$customer    = WC_Helper_Customer::create_customer();
		$orig_session = WC()->session;
		WC()->session = null;

		$re_fetched_customer = new WC_Customer( $customer->get_id(), true );
		WC()->session        = $orig_session;

		$this->assertInstanceOf( 'WC_Customer', $re_fetched_customer );
	}

	/**
	 * @testdox has_full_shipping_address() still requires the default fields when the default locale entry is missing or not an array.
	 *
	 * @dataProvider provide_broken_default_locale_entries
	 *
	 * @param mixed $default_entry Value to put in the default locale entry, or null to remove the entry.
	 */
	public function test_has_full_shipping_address_requires_default_fields_without_a_default_locale_entry( $default_entry ): void {
		$countries = WC()->countries;
		$countries->get_country_locale();
		if ( null === $default_entry ) {
			unset( $countries->locale['default'] );
		} else {
			$countries->locale['default'] = $default_entry;
		}
		$sut = $this->get_customer_without_shipping_postcode();

		$this->assertFalse( $sut->has_full_shipping_address(), 'A missing postcode should still make the shipping address incomplete.' );
	}

	/**
	 * Broken values for the default entry of the country locale settings.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provide_broken_default_locale_entries(): array {
		return array(
			'entry removed'      => array( null ),
			'entry not an array' => array( false ),
		);
	}

	/**
	 * @testdox has_full_shipping_address() still requires the default fields when it runs while the country locale settings are being built.
	 */
	public function test_has_full_shipping_address_requires_default_fields_while_country_locale_is_built(): void {
		$sut    = $this->get_customer_without_shipping_postcode();
		$nested = null;
		add_filter(
			'woocommerce_get_country_locale',
			function ( $locale ) use ( $sut, &$nested ) {
				$nested = $sut->has_full_shipping_address();
				return $locale;
			}
		);
		WC()->countries->locale = array();

		WC()->countries->get_country_locale();

		$this->assertFalse( $nested, 'A check made while the locale settings are built should still treat a missing postcode as incomplete.' );
	}

	/**
	 * Get a customer whose US shipping address has everything but a postcode.
	 *
	 * @return WC_Customer
	 */
	private function get_customer_without_shipping_postcode(): WC_Customer {
		$customer = new WC_Customer();
		$customer->set_shipping_country( 'US' );
		$customer->set_shipping_state( 'CA' );
		$customer->set_shipping_city( 'San Francisco' );
		$customer->set_shipping_postcode( '' );

		return $customer;
	}
}
