<?php
/**
 * Account function tests
 *
 * @package WooCommerce\Tests\Account
 */

/**
 * Class Functions.
 */
class WC_Tests_Account_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_lostpassword_url() from admin screen.
	 *
	 * @since 3.3.0
	 */
	public function test_wc_lostpassword_url() {
		do_action( 'login_form_login' ); // Simulate admin login screen.

		// Admin URL is expected.
		$expected_url = admin_url( '/wp-login.php?action=lostpassword' );

		$this->assertEquals( $expected_url, wc_lostpassword_url( $expected_url ) );
	}

	/**
	 * Test wc_lostpassword_url() from my account page.
	 */
	public function test_wc_lostpassword_url_from_account_page() {
		// Create the account page, since other tests may delete it.
		$page = wc_create_page(
			'myaccount',
			'woocommerce_myaccount_page_id',
			'My Account',
			'',
			'',
			'publish'
		);
		$this->go_to( wc_get_page_permalink( 'myaccount' ) );

		// Front-end URL is expected.
		$expected_url = wc_get_endpoint_url( 'lost-password', '', get_the_permalink( 'myaccount' ) );

		$this->assertEquals( $expected_url, wc_lostpassword_url() );
	}

	/**
	 * Test wc_customer_edit_account_url().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_customer_edit_account_url() {
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '?edit-account', wc_customer_edit_account_url() );
	}

	/**
	 * Test wc_edit_address_i18n().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_edit_address_i18n() {
		// Should return the same result, since it's using en_US in Unit Tests.
		$this->assertEquals( 'billing', wc_edit_address_i18n( 'billing' ) );
		$this->assertEquals( 'billing', wc_edit_address_i18n( 'billing', true ) );
	}

	/**
	 * Test wc_get_account_menu_items().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_menu_items() {
		$this->assertEquals(
			array(
				'dashboard'       => 'Dashboard',
				'orders'          => 'Orders',
				'downloads'       => 'Downloads',
				'edit-address'    => 'Addresses',
				'edit-account'    => 'Account details',
				'customer-logout' => 'Log out',
			),
			wc_get_account_menu_items()
		);
	}

	/**
	 * Test wc_get_account_menu_item_classes().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_menu_item_classes() {
		$this->assertEquals( 'woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--test', wc_get_account_menu_item_classes( 'test' ) );
	}

	/**
	 * Test wc_get_account_endpoint_url().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_endpoint_url() {
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '?test', wc_get_account_endpoint_url( 'test' ) );
	}

	/**
	 * Test wc_get_account_orders_columns().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_orders_columns() {
		$this->assertEquals(
			array(
				'order-number'  => 'Order',
				'order-date'    => 'Date',
				'order-status'  => 'Status',
				'order-total'   => 'Total',
				'order-actions' => 'Actions',
			),
			wc_get_account_orders_columns()
		);
	}

	/**
	 * Test wc_get_account_downloads_columns().
	 *
	 * @since 2.6.0
	 */
	public function test_wc_get_account_downloads_columns() {
		$this->assertEquals(
			array(
				'download-file'      => 'Download',
				'download-remaining' => 'Downloads remaining',
				'download-expires'   => 'Expires',
				'download-product'   => 'Product',
			),
			wc_get_account_downloads_columns()
		);
	}

	/**
	 * Test wc_get_account_payment_methods_columns().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_payment_methods_columns() {
		$this->assertEquals(
			array(
				'method'  => 'Method',
				'expires' => 'Expires',
				'actions' => '&nbsp;',
			),
			wc_get_account_payment_methods_columns()
		);
	}

	/**
	 * Test wc_get_account_payment_methods_types().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_payment_methods_types() {
		$this->assertEquals(
			array(
				'cc'     => 'Credit card',
				'echeck' => 'eCheck',
			),
			wc_get_account_payment_methods_types()
		);
	}

	/**
	 * Test wc_get_account_orders_actions().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_orders_actions() {
		$order    = WC_Helper_Order::create_order();
		$order_id = $order->get_id();

		$this->assertEquals(
			array(
				'view'   => array(
					'url'        => $order->get_view_order_url(),
					'name'       => 'View',
					'aria-label' => "View order {$order_id}",
				),
				'pay'    => array(
					'url'        => $order->get_checkout_payment_url(),
					'name'       => 'Pay',
					'aria-label' => "Pay for order {$order_id}",
				),
				'cancel' => array(
					'url'        => $order->get_cancel_order_url( wc_get_page_permalink( 'myaccount' ) ),
					'name'       => 'Cancel',
					'aria-label' => "Cancel order {$order_id}",
				),
			),
			wc_get_account_orders_actions( $order->get_id() )
		);

		$order->delete( true );
	}

	/**
	 * Test wc_get_account_orders_actions() filters out malformed entries from the filter.
	 *
	 * @since 10.9.0
	 */
	public function test_wc_get_account_orders_actions_filters_malformed_entries() {
		$order = WC_Helper_Order::create_order();

		// Add malformed entries via the filter.
		add_filter(
			'woocommerce_my_account_my_orders_actions',
			static function ( $actions ) {
				$actions['invalid_bool']    = false;
				$actions['invalid_string']  = 'not-an-array';
				$actions['missing_url']     = array( 'name' => 'No URL' );
				$actions['missing_name']    = array( 'url' => 'https://example.com' );
				$actions['non_string_url']  = array(
					'name' => 'Test',
					'url'  => 123,
				);
				$actions['non_string_name'] = array(
					'name' => true,
					'url'  => 'https://example.com',
				);
				return $actions;
			}
		);

		$result = wc_get_account_orders_actions( $order->get_id() );

		// All malformed entries should be removed, only valid actions remain.
		$this->assertArrayNotHasKey( 'invalid_bool', $result );
		$this->assertArrayNotHasKey( 'invalid_string', $result );
		$this->assertArrayNotHasKey( 'missing_url', $result );
		$this->assertArrayNotHasKey( 'missing_name', $result );
		$this->assertArrayNotHasKey( 'non_string_url', $result );
		$this->assertArrayNotHasKey( 'non_string_name', $result );

		// Valid original actions should still be present.
		$this->assertArrayHasKey( 'view', $result );
		$this->assertArrayHasKey( 'pay', $result );

		$order->delete( true );
	}

	/**
	 * Test wc_get_account_orders_actions() returns empty array when filter returns non-array.
	 *
	 * @since 10.9.0
	 */
	public function test_wc_get_account_orders_actions_returns_empty_for_non_array_filter() {
		$order = WC_Helper_Order::create_order();

		add_filter(
			'woocommerce_my_account_my_orders_actions',
			'__return_false'
		);

		$result = wc_get_account_orders_actions( $order->get_id() );

		$this->assertSame( array(), $result );

		$order->delete( true );
	}

	/**
	 * Test wc_get_account_formatted_address().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_formatted_address() {
		$customer = WC_Helper_Customer::create_customer();

		$this->assertEquals( '123 South Street<br/>Apt 1<br/>San Francisco, CA 94110<br/>United States (US)', wc_get_account_formatted_address( 'billing', $customer->get_id() ) );

		$customer->delete( true );
	}

	/**
	 * Test wc_get_account_formatted_address() formats the address type it is asked for.
	 *
	 * @since 11.3.0
	 */
	public function test_wc_get_account_formatted_address_formats_the_requested_address_type() {
		$customer = $this->create_customer_with_distinct_addresses( 'formatted-address-customer', 'formatted.address@example.com' );

		$shipping = wc_get_account_formatted_address( 'shipping', $customer->get_id() );
		$billing  = wc_get_account_formatted_address( 'billing', $customer->get_id() );

		$this->assertStringContainsString( 'Jane Shipping', $shipping );
		$this->assertStringContainsString( '456 Nevergreen Terrace', $shipping );
		$this->assertStringContainsString( 'New York', $shipping );
		$this->assertStringContainsString( '10010', $shipping );
		$this->assertStringNotContainsString( 'John Doe', $shipping );
		$this->assertStringNotContainsString( '123 Evergreen Terrace', $shipping );
		$this->assertStringNotContainsString( 'Springfield', $shipping );
		$this->assertStringNotContainsString( '10001', $shipping );

		$this->assertStringContainsString( 'John Doe', $billing );
		$this->assertStringContainsString( '123 Evergreen Terrace', $billing );
		$this->assertStringContainsString( 'Springfield', $billing );
		$this->assertStringContainsString( '10001', $billing );
		$this->assertStringNotContainsString( 'Jane Shipping', $billing );
		$this->assertStringNotContainsString( '456 Nevergreen Terrace', $billing );
		$this->assertStringNotContainsString( 'New York', $billing );
		$this->assertStringNotContainsString( '10010', $billing );
	}

	/**
	 * Test the My Account addresses page renders each saved address under its own heading.
	 *
	 * @since 11.3.0
	 */
	public function test_my_address_template_renders_each_saved_address_in_its_own_column() {
		$customer = $this->create_customer_with_distinct_addresses( 'my-address-customer', 'my.address@example.com' );

		update_option( 'woocommerce_ship_to_destination', 'shipping' );
		update_option( 'woocommerce_ship_to_countries', '' );
		wp_set_current_user( $customer->get_id() );

		$html = wc_get_template_html( 'myaccount/my-address.php' );

		$shipping_column = $this->get_rendered_address_column( $html, 'Shipping address' );
		$billing_column  = $this->get_rendered_address_column( $html, 'Billing address' );

		$this->assertStringContainsString( 'Jane Shipping', $shipping_column );
		$this->assertStringContainsString( '456 Nevergreen Terrace', $shipping_column );
		$this->assertStringContainsString( 'New York', $shipping_column );
		$this->assertStringContainsString( '10010', $shipping_column );
		$this->assertStringNotContainsString( '123 Evergreen Terrace', $shipping_column );

		$this->assertStringContainsString( 'John Doe', $billing_column );
		$this->assertStringContainsString( '123 Evergreen Terrace', $billing_column );
		$this->assertStringContainsString( 'Springfield', $billing_column );
		$this->assertStringContainsString( '10001', $billing_column );
		$this->assertStringNotContainsString( '456 Nevergreen Terrace', $billing_column );
	}

	/**
	 * Create a customer whose billing and shipping addresses share no values.
	 *
	 * @param string $username Username for the new customer.
	 * @param string $email    Email address for the new customer.
	 * @return WC_Customer
	 */
	private function create_customer_with_distinct_addresses( $username, $email ) {
		$customer = new WC_Customer();
		$customer->set_username( $username );
		$customer->set_email( $email );
		$customer->set_billing_first_name( 'John' );
		$customer->set_billing_last_name( 'Doe' );
		$customer->set_billing_address_1( '123 Evergreen Terrace' );
		$customer->set_billing_city( 'Springfield' );
		$customer->set_billing_state( 'IL' );
		$customer->set_billing_postcode( '10001' );
		$customer->set_billing_country( 'US' );
		$customer->set_shipping_first_name( 'Jane' );
		$customer->set_shipping_last_name( 'Shipping' );
		$customer->set_shipping_address_1( '456 Nevergreen Terrace' );
		$customer->set_shipping_city( 'New York' );
		$customer->set_shipping_state( 'NY' );
		$customer->set_shipping_postcode( '10010' );
		$customer->set_shipping_country( 'US' );
		$customer->save();

		return $customer;
	}

	/**
	 * Get the markup of the address rendered under a My Account addresses heading.
	 *
	 * @param string $html          Rendered My Account addresses markup.
	 * @param string $address_title Heading the address is rendered under.
	 * @return string
	 */
	private function get_rendered_address_column( $html, $address_title ) {
		$document       = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		$loaded         = $document->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$this->assertTrue( $loaded, 'The My Account addresses markup should be valid enough for DOM parsing.' );

		$xpath = new DOMXPath( $document );
		$nodes = $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' woocommerce-Address ')][.//h2[normalize-space(.)='{$address_title}']]/address" );

		$this->assertSame( 1, $nodes->length, "The page should render one address under the {$address_title} heading." );

		return (string) $document->saveHTML( $nodes->item( 0 ) );
	}

	/**
	 * Test wc_get_account_saved_payment_methods_list().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_saved_payment_methods_list() {
		$customer = WC_Helper_Customer::create_customer();

		$token = new WC_Payment_Token_CC();
		$token->set_token( '1234' );
		$token->set_gateway_id( WC_Gateway_BACS::ID );
		$token->set_card_type( 'mastercard' );
		$token->set_last4( '1234' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2020' );
		$token->set_user_id( $customer->get_id() );
		$token->save();

		$delete_url = wc_get_endpoint_url( 'delete-payment-method', $token->get_id() );
		$delete_url = wp_nonce_url( $delete_url, 'delete-payment-method-' . $token->get_id() );

		$this->assertEquals(
			array(
				'cc' => array(
					array(
						'method'     => array(
							'gateway' => WC_Gateway_BACS::ID,
							'last4'   => '1234',
							'brand'   => 'Mastercard',
						),
						'expires'    => '12/20',
						'is_default' => true,
						'actions'    => array(
							'delete' => array(
								'url'  => $delete_url,
								'name' => 'Delete',
							),
						),
					),
				),
			),
			wc_get_account_saved_payment_methods_list( array(), $customer->get_id() )
		);

		$customer->delete( true );
		$token->delete( true );
	}

	/**
	 * Test wc_get_account_saved_payment_methods_list_item_cc().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_saved_payment_methods_list_item_cc() {
		$token = new WC_Payment_Token_CC();
		$token->set_token( '1234' );
		$token->set_gateway_id( WC_Gateway_BACS::ID );
		$token->set_card_type( 'mastercard' );
		$token->set_last4( '1234' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2020' );
		$token->save();

		$this->assertEquals(
			array(
				'method'  => array(
					'last4' => '1234',
					'brand' => 'Mastercard',
				),
				'expires' => '12/20',
			),
			wc_get_account_saved_payment_methods_list_item_cc( array(), $token )
		);

		$token->delete( true );

		// Co-branded credit card.
		$token = new WC_Payment_Token_CC();
		$token->set_token( '1001' );
		$token->set_gateway_id( WC_Gateway_BACS::ID );
		$token->set_card_type( 'cartes_bancaires' );
		$token->set_last4( '1001' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2020' );
		$token->save();

		$this->assertEquals(
			array(
				'method'  => array(
					'last4' => '1001',
					'brand' => 'Cartes Bancaires',
				),
				'expires' => '12/20',
			),
			wc_get_account_saved_payment_methods_list_item_cc( array(), $token )
		);

		$token->delete( true );
	}

	/**
	 * Test wc_get_account_saved_payment_methods_list_item_echeck().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_saved_payment_methods_list_item_echeck() {
		$token = new WC_Payment_Token_ECheck();
		$token->set_token( '1234' );
		$token->set_gateway_id( WC_Gateway_BACS::ID );
		$token->set_last4( '1234' );
		$token->save();

		$this->assertEquals(
			array(
				'method' => array(
					'last4' => '1234',
					'brand' => 'eCheck',
				),
			),
			wc_get_account_saved_payment_methods_list_item_echeck( array(), $token )
		);

		$token->delete( true );
	}
}
