<?php

/**
 * Tests relating to the WC_Customer_Data_Store_Session class.
 */
class WC_Customer_Data_Store_Session_Test extends WC_Unit_Test_Case {

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		WC()->session->set( 'customer', null );
		// Most tests here cover how a saved shipping address is loaded, so keep it as the default destination.
		update_option( 'woocommerce_ship_to_destination', 'shipping' );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		WC()->session->set( 'customer', null );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

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
	 * @testdox Should override a persisted billing field with an empty value stored in the session.
	 */
	public function test_empty_session_value_overrides_persisted_billing_field(): void {
		$customer_id = WC_Helper_Customer::create_customer( 'session_empty_billing', 'password', 'session-empty-billing@example.com' )->get_id();

		$session_customer = new WC_Customer( $customer_id, true );
		$session_customer->set_billing_address_2( '' );
		$session_customer->save();

		$reloaded_customer = new WC_Customer( $customer_id, true );

		$this->assertSame( '', $reloaded_customer->get_billing_address_2(), 'A billing field emptied in the session should not be restored from the persisted customer data' );
	}

	/**
	 * @testdox Should leave persisted values untouched for keys that are absent from the session data.
	 */
	public function test_keys_absent_from_session_data_keep_persisted_values(): void {
		$customer_id   = WC_Helper_Customer::create_customer( 'session_absent_keys', 'password', 'session-absent-keys@example.com' )->get_id();
		$date_modified = (string) ( new WC_Customer( $customer_id ) )->get_date_modified( 'edit' );

		WC()->session->set(
			'customer',
			array(
				'id'            => (string) $customer_id,
				'date_modified' => $date_modified,
				'first_name'    => 'Session-Name',
			)
		);

		$customer = new WC_Customer( $customer_id, true );

		$this->assertSame( 'Session-Name', $customer->get_billing_first_name(), 'Keys present in the session data should be applied' );
		$this->assertSame( 'Apt 1', $customer->get_shipping_address_2(), 'Keys absent from the session data should keep the persisted values' );
	}

	/**
	 * @testdox Should ignore the session data entirely when the date modified does not match the persisted customer.
	 */
	public function test_stale_session_data_is_ignored(): void {
		$customer_id = WC_Helper_Customer::create_customer( 'session_stale_data', 'password', 'session-stale-data@example.com' )->get_id();

		WC()->session->set(
			'customer',
			array(
				'id'                 => (string) $customer_id,
				'date_modified'      => 'stale-date',
				'address_2'          => '',
				'shipping_address_2' => '',
			)
		);

		$customer = new WC_Customer( $customer_id, true );

		$this->assertSame( 'Apt 1', $customer->get_billing_address_2(), 'Stale session data should not override the persisted billing values' );
		$this->assertSame( 'Apt 1', $customer->get_shipping_address_2(), 'Stale session data should not override the persisted shipping values' );
	}

	/**
	 * @testdox Should fall back to the default location when the billing country is emptied in the session.
	 */
	public function test_emptied_billing_country_falls_back_to_default_location(): void {
		$customer_id = WC_Helper_Customer::create_customer( 'session_empty_country', 'password', 'session-empty-country@example.com' )->get_id();

		$session_customer = new WC_Customer( $customer_id, true );
		$session_customer->set_billing_country( '' );
		$session_customer->save();

		$reloaded_customer = new WC_Customer( $customer_id, true );
		$default_location  = wc_get_customer_default_location();

		$this->assertSame( $default_location['country'], $reloaded_customer->get_billing_country(), 'An emptied billing country should be replaced with the default location country' );
	}

	/**
	 * @testdox Should fall back to the account email when the billing email is emptied in the session for a logged in user.
	 */
	public function test_emptied_billing_email_falls_back_to_account_email_for_logged_in_user(): void {
		$customer_id = WC_Helper_Customer::create_customer( 'session_empty_email', 'password', 'session-empty-email@example.com' )->get_id();
		wp_set_current_user( $customer_id );

		$session_customer = new WC_Customer( $customer_id, true );
		$session_customer->set_billing_email( '' );
		$session_customer->save();

		$reloaded_customer = new WC_Customer( $customer_id, true );

		$this->assertSame( 'session-empty-email@example.com', $reloaded_customer->get_billing_email(), 'An emptied billing email should be replaced with the account email for logged in users' );
	}

	/**
	 * @testdox Should default the shipping address to the billing address when the store ships to the billing address by default.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/29744
	 * @testWith [ "billing" ]
	 *           [ "billing_only" ]
	 *
	 * @param string $ship_to_destination Value of the "Shipping destination" setting.
	 */
	public function test_shipping_address_defaults_to_billing_address_when_store_ships_to_billing( string $ship_to_destination ): void {
		update_option( 'woocommerce_ship_to_destination', $ship_to_destination );
		$customer_id = $this->create_customer_with_different_addresses( 'session_ship_to_' . $ship_to_destination );

		$customer = new WC_Customer( $customer_id, true );

		$this->assertSame( 'Stationsplein 23', $customer->get_shipping_address_1(), 'The shipping street should be taken from the billing address' );
		$this->assertSame( 'Amsterdam', $customer->get_shipping_city(), 'The shipping city should be taken from the billing address' );
		$this->assertSame( '1010 AA', $customer->get_shipping_postcode(), 'The shipping postcode should be taken from the billing address' );
		$this->assertSame( 'NL', $customer->get_shipping_country(), 'The shipping country should be taken from the billing address' );
		$this->assertSame( '', $customer->get_shipping_state(), 'The shipping state should be taken from the billing address, not the store base state' );
		$this->assertSame( '020-123456789', $customer->get_shipping_phone(), 'The shipping phone should be taken from the billing address' );
		$this->assertSame( 'Stationsplein 23', ( new WC_Customer( $customer_id ) )->get_billing_address_1(), 'The persisted billing address should be unchanged' );
		$this->assertSame( 'Brandarisstraat 2', ( new WC_Customer( $customer_id ) )->get_shipping_address_1(), 'The persisted shipping address should be unchanged' );
	}

	/**
	 * @testdox Should keep the saved shipping address when the store ships to the shipping address by default.
	 */
	public function test_saved_shipping_address_is_kept_when_store_ships_to_shipping_address(): void {
		$customer_id = $this->create_customer_with_different_addresses( 'session_ship_to_shipping' );

		$customer = new WC_Customer( $customer_id, true );

		$this->assertSame( 'Brandarisstraat 2', $customer->get_shipping_address_1(), 'The saved shipping address should be used' );
		$this->assertSame( 'West-Terschelling', $customer->get_shipping_city(), 'The saved shipping city should be used' );
	}

	/**
	 * @testdox Should not overwrite a shipping address set during the session with the billing address.
	 */
	public function test_shipping_address_from_session_is_not_overwritten_by_billing_address(): void {
		update_option( 'woocommerce_ship_to_destination', 'billing' );
		$customer_id = $this->create_customer_with_different_addresses( 'session_ship_to_billing_session' );

		$session_customer = new WC_Customer( $customer_id, true );
		$session_customer->set_shipping_address_1( 'Keizersgracht 100' );
		$session_customer->set_shipping_city( 'Rotterdam' );
		$session_customer->save();

		$reloaded_customer = new WC_Customer( $customer_id, true );

		$this->assertSame( 'Keizersgracht 100', $reloaded_customer->get_shipping_address_1(), 'A shipping address set during the session should be kept' );
		$this->assertSame( 'Rotterdam', $reloaded_customer->get_shipping_city(), 'A shipping city set during the session should be kept' );
	}

	/**
	 * @testdox Should not default the shipping address to an empty billing address.
	 */
	public function test_saved_shipping_address_is_kept_when_billing_address_is_empty(): void {
		update_option( 'woocommerce_ship_to_destination', 'billing' );

		$customer = new WC_Customer();
		$customer->set_email( 'session-empty-billing-address@example.com' );
		$customer->set_shipping_address_1( 'Brandarisstraat 2' );
		$customer->set_shipping_city( 'West-Terschelling' );
		$customer->set_shipping_country( 'NL' );
		$customer->save();

		$session_customer = new WC_Customer( $customer->get_id(), true );

		$this->assertSame( 'Brandarisstraat 2', $session_customer->get_shipping_address_1(), 'The saved shipping address should be kept when there is no billing address to default to' );
		$this->assertSame( 'NL', $session_customer->get_shipping_country(), 'The saved shipping country should be kept when there is no billing address to default to' );
	}

	/**
	 * @testdox Should not default the billing state to the store base state when the billing country is already set.
	 */
	public function test_billing_state_is_not_defaulted_when_billing_country_is_set(): void {
		$customer = new WC_Customer();
		$customer->set_email( 'session-billing-country-only@example.com' );
		$customer->set_billing_country( 'NL' );
		$customer->save();

		$session_customer = new WC_Customer( $customer->get_id(), true );

		$this->assertSame( 'NL', $session_customer->get_billing_country(), 'The saved billing country should be kept' );
		$this->assertSame( '', $session_customer->get_billing_state(), 'A saved billing country without a state should not pick up the store base state' );
	}

	/**
	 * @testdox Should default the billing state together with the billing country when neither is set.
	 */
	public function test_billing_state_is_defaulted_together_with_billing_country(): void {
		$customer         = new WC_Customer();
		$default_location = wc_get_customer_default_location();

		( new WC_Customer_Data_Store_Session() )->read( $customer );

		$this->assertSame( $default_location['country'], $customer->get_billing_country(), 'An empty billing country should be replaced with the default location country' );
		$this->assertSame( $default_location['state'], $customer->get_billing_state(), 'An empty billing state should be replaced with the default location state along with the country' );
	}

	/**
	 * Creates a persisted customer whose billing and shipping addresses differ.
	 *
	 * @param string $username Username for the customer.
	 * @return int Customer ID.
	 */
	private function create_customer_with_different_addresses( string $username ): int {
		$customer = new WC_Customer();
		$customer->set_username( $username );
		$customer->set_password( 'password' );
		$customer->set_email( $username . '@example.com' );
		$customer->set_billing_first_name( 'Issue' );
		$customer->set_billing_last_name( 'Tester' );
		$customer->set_billing_address_1( 'Stationsplein 23' );
		$customer->set_billing_city( 'Amsterdam' );
		$customer->set_billing_postcode( '1010 AA' );
		$customer->set_billing_country( 'NL' );
		$customer->set_billing_phone( '020-123456789' );
		$customer->set_shipping_first_name( 'Issue' );
		$customer->set_shipping_last_name( 'Tester' );
		$customer->set_shipping_address_1( 'Brandarisstraat 2' );
		$customer->set_shipping_city( 'West-Terschelling' );
		$customer->set_shipping_postcode( '8881 AW' );
		$customer->set_shipping_country( 'NL' );
		$customer->set_shipping_phone( '088-123456789' );
		$customer->save();

		return $customer->get_id();
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

	/**
	 * Ensure that empty string values can be persisted in customer session.
	 */
	public function test_empty_field_can_be_persisted_in_session() {
		WC()->session->init();

		$customer = new WC_Customer();
		$customer->set_email( 'test@example.com' );
		$customer->set_shipping_address_2( 'Apt 1' );
		$customer->save();

		// Session says address_2 is now empty (user cleared it).
		WC()->session->set(
			'customer',
			array(
				'id'                 => (string) $customer->get_id(),
				'date_modified'      => (string) $customer->get_date_modified( 'edit' ),
				'shipping_address_2' => '',
			)
		);

		$data_store = new WC_Customer_Data_Store_Session();
		$data_store->read( $customer );

		$this->assertSame( '', $customer->get_shipping_address_2() );
	}

	/**
	 * Ensure backslashes in customer fields survive a session save/read round-trip.
	 *
	 * Covers the multi-request flow where one request stores the address in the session and a
	 * follow-up request reads it back: the session store must not wp_unslash() the data on read, or
	 * real backslashes the buyer typed get stripped (session data is stored raw, never magic-quoted).
	 *
	 * @see https://github.com/woocommerce/woocommerce/pull/65900
	 */
	public function test_backslashes_survive_session_round_trip() {
		WC()->session->init();

		$customer = new WC_Customer();
		$customer->set_email( 'backslash@example.com' );
		$customer->set_shipping_address_1( 'Apt 4\\B' );
		$customer->set_shipping_city( 'C:\\Users' );
		$customer->save();

		$data_store = new WC_Customer_Data_Store_Session();
		$data_store->save_to_session( $customer );

		// Overwrite in memory, then read back from the session as the next request would.
		$customer->set_shipping_address_1( 'overwritten' );
		$customer->set_shipping_city( 'overwritten' );
		$data_store->read( $customer );

		$this->assertSame( 'Apt 4\\B', $customer->get_shipping_address_1(), 'Backslashes in the shipping address should survive the session round-trip.' );
		$this->assertSame( 'C:\\Users', $customer->get_shipping_city(), 'Backslashes in the shipping city should survive the session round-trip.' );
	}
}
