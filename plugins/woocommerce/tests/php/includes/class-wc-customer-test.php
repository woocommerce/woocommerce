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
}
