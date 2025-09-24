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
	 * @param Closure $customer_closure The customer object being tested.
	 * @param bool    $states_should_match If the billing and shipping states should match.
	 * @param bool    $countries_should_match If the billing and shipping countries should match.
	 */
	public function test_setting_default_address_fields( Closure $customer_closure, bool $states_should_match, bool $countries_should_match ) {
		$customer     = $customer_closure();
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
	 * Ensure that customer data is only set in the session if it is not the default customer data.
	 */
	public function test_customer_data_is_set_in_session_if_is_not_the_default_customer_data() {
		$customer = new WC_Customer();
		$customer->set_billing_email( 'email@woocommerce.com' );

		$session_data = new WC_Customer_Data_Store_Session();
		$session_data->save_to_session( $customer );

		$customer_from_session = WC()->session->get( 'customer' );
		$this->assertNotEmpty( $customer_from_session );
		$this->assertEquals( 'email@woocommerce.com', $customer_from_session['email'] );
	}

	/**
	 * Ensure that customer data is not set in the session if it is the default customer data.
	 */
	public function test_customer_data_is_not_set_in_session_if_is_the_default_customer_data() {
		WC()->session->init();
		WC()->session->set_customer_session_cookie( true );

		$customer   = $this->get_default_customer();
		$data_store = new WC_Customer_Data_Store_Session();
		$data_store->save_to_session( $customer );
		WC()->session->save_data();

		$session_data = WC()->session->get_session_data();

		$this->assertArrayNotHasKey( 'customer', $session_data );
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
		$cust1_closure = function () {
			$has_billing_address_only = new WC_Customer();
			$has_billing_address_only->set_email( 'wc-customer-test-01@test.user' );
			$has_billing_address_only->set_billing_address( '1234 Quality Lane' );
			$has_billing_address_only->set_billing_city( 'Testville' );
			$has_billing_address_only->set_billing_country( 'US' );
			$has_billing_address_only->set_billing_state( 'CA' );
			$has_billing_address_only->set_billing_postcode( '90123' );
			$has_billing_address_only->save();
			return $has_billing_address_only;
		};

		$cust2_closure = function () {
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
			return $separate_billing_and_shipping_state_and_country;
		};

		$cust3_closure = function () {
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
			return $separate_billing_state_same_country;
		};

		$cust4_closure = function () {
			$shipping_address_is_effectively_empty = new WC_Customer();
			$shipping_address_is_effectively_empty->set_email( 'wc-customer-test-04@test.user' );
			$shipping_address_is_effectively_empty->set_shipping_address( ' ' );
			$shipping_address_is_effectively_empty->save();
			return $shipping_address_is_effectively_empty;
		};

		return array(
			'has_billing_address_only'              => array(
				$cust1_closure,
				true,
				true,
			),
			'separate_billing_and_shipping_state_and_country' => array(
				$cust2_closure,
				false,
				false,
			),
			'separate_billing_state_same_country'   => array(
				$cust3_closure,
				false,
				true,
			),
			'shipping_address_is_effectively_empty' => array(
				$cust4_closure,
				true,
				true,
			),
		);
	}

	/**
	 * Get a customer with the default location.
	 *
	 * @return WC_Customer
	 */
	private function get_default_customer(): WC_Customer {
		$location = wc_get_customer_default_location();

		$customer = new WC_Customer();
		$customer->set_shipping_country( $location['country'] );
		$customer->set_shipping_state( $location['state'] );
		$customer->set_billing_country( $location['country'] );
		$customer->set_billing_state( $location['state'] );
		return $customer;
	}
}
