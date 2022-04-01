<?php

/**
 * Tests relating to the WC_Customer_Data_Store_Session class.
 */
class WC_Customer_Data_Store_Session_Test extends WC_Unit_Test_Case {
	/**
	 * Ensure that the country and state shipping address fields only inherit
	 * the corresponding billing address values if a shipping address is not set.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/28759
	 * @dataProvider provide_customers_with_different_addresses
	 *
	 * @param WC_Customer $customer               The customer object being tested.
	 * @param bool        $states_should_match    If the billing and shipping states should match.
	 * @param bool        $countries_should_match If the billing and shipping countries should match.
	 */
	public function test_setting_default_address_fields( WC_Customer $customer, bool $states_should_match, bool $countries_should_match ) {
		$session_data = new WC_Customer_Data_Store_Session();
		$session_data->read( $customer );

		if ( $states_should_match ) {
			$this->assertEquals( $customer->get_shipping_state(), $customer->get_billing_state() );
		} else {
			$this->assertNotEquals( $customer->get_shipping_state(), $customer->get_billing_state() );
		}

		if ( $countries_should_match ) {
			$this->assertEquals( $customer->get_shipping_country(), $customer->get_billing_country() );
		} else {
			$this->assertNotEquals( $customer->get_shipping_country(), $customer->get_billing_country() );
		}
	}

	/**
	 * Customer objects with a mixture of billing and shipping addresses.
	 *
	 * Each inner dataset is organized as follows:
	 *
	 *     [
	 *         (WC_Customer) $customer_object,
	 *         (bool) $states_should_match,
	 *         (bool) $countries_should_match,
	 *     ]
	 *
	 * @return array[]
	 */
	public function provide_customers_with_different_addresses() {
		$has_billing_address_only = new WC_Customer();
		$has_billing_address_only->set_email( 'wc-customer-test-01@test.user' );
		$has_billing_address_only->set_billing_address( '1234 Quality Lane' );
		$has_billing_address_only->set_billing_city( 'Testville' );
		$has_billing_address_only->set_billing_country( 'US' );
		$has_billing_address_only->set_billing_state( 'CA' );
		$has_billing_address_only->set_billing_postcode( '90123' );
		$has_billing_address_only->save();

		$separate_billing_and_shipping_state_and_country = new WC_Customer();
		$separate_billing_and_shipping_state_and_country->set_email( 'wc-customer-test-02@test.user' );
		$separate_billing_and_shipping_state_and_country->set_billing_address( '4567 Scenario Street' );
		$separate_billing_and_shipping_state_and_country->set_billing_city( 'Unitly' );
		$separate_billing_and_shipping_state_and_country->set_billing_country( 'UK' );
		$separate_billing_and_shipping_state_and_country->set_billing_state( 'Computershire' );
		$separate_billing_and_shipping_state_and_country->set_billing_postcode( 'ZX1 2PQ' );
		$separate_billing_and_shipping_state_and_country->set_shipping_address( '8901 Situation Court' );
		$separate_billing_and_shipping_state_and_country->set_shipping_city( 'Endtoendly' );
		$separate_billing_and_shipping_state_and_country->set_shipping_country( 'CA' );
		$separate_billing_and_shipping_state_and_country->set_shipping_state( 'BC' );
		$separate_billing_and_shipping_state_and_country->set_shipping_postcode( 'A1B 2C3' );
		$separate_billing_and_shipping_state_and_country->save();

		$separate_billing_state_same_country = new WC_Customer();
		$separate_billing_state_same_country->set_email( 'wc-customer-test-03@test.user' );
		$separate_billing_state_same_country->set_billing_address( '4567 Scenario Street' );
		$separate_billing_state_same_country->set_billing_city( 'Unitly' );
		$separate_billing_state_same_country->set_billing_country( 'UK' );
		$separate_billing_state_same_country->set_billing_state( 'Computershire' );
		$separate_billing_state_same_country->set_billing_postcode( 'ZX1 2PQ' );
		$separate_billing_state_same_country->set_shipping_address( '8901 Situation Court' );
		$separate_billing_state_same_country->set_shipping_city( 'Endtoendly' );
		$separate_billing_state_same_country->set_shipping_country( 'UK' );
		$separate_billing_state_same_country->set_shipping_state( 'Byteshire' );
		$separate_billing_state_same_country->set_shipping_postcode( 'RS1 2TU' );
		$separate_billing_state_same_country->save();

		$shipping_address_is_effectively_empty = new WC_Customer();
		$shipping_address_is_effectively_empty->set_email( 'wc-customer-test-04@test.user' );
		$shipping_address_is_effectively_empty->set_shipping_address( ' ' );
		$shipping_address_is_effectively_empty->save();

		return array(
			'has_billing_address_only' => array(
				$has_billing_address_only,
				true,
				true,
			),
			'separate_billing_and_shipping_state_and_country' => array(
				$separate_billing_and_shipping_state_and_country,
				false,
				false,
			),
			'separate_billing_state_same_country' => array(
				$separate_billing_state_same_country,
				false,
				true,
			),
			'shipping_address_is_effectively_empty' => array(
				$shipping_address_is_effectively_empty,
				true,
				true,
			),
		);
	}
}
