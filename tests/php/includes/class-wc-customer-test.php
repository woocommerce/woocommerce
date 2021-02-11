<?php
/**
 * Tests for WC_Customer class.
 */
class WC_Customer_Test extends \WC_Unit_Test_Case {
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
