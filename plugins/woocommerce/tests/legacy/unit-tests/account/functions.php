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
	 * Test wc_lostpassword_url().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_lostpassword_url() {
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN . '?lost-password', wc_lostpassword_url() );
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
		$order = WC_Helper_Order::create_order();

		$this->assertEquals(
			array(
				'view'   => array(
					'url'  => $order->get_view_order_url(),
					'name' => 'View',
				),
				'pay'    => array(
					'url'  => $order->get_checkout_payment_url(),
					'name' => 'Pay',
				),
				'cancel' => array(
					'url'  => $order->get_cancel_order_url( wc_get_page_permalink( 'myaccount' ) ),
					'name' => 'Cancel',
				),
			),
			wc_get_account_orders_actions( $order->get_id() )
		);

		$order->delete( true );
	}

	/**
	 * Test wc_get_account_formatted_address().
	 *
	 * @since 3.3.0
	 */
	public function test_wc_get_account_formatted_address() {
		$customer = WC_Helper_Customer::create_customer();

		$this->assertEquals( '123 South Street<br/>Apt 1<br/>San Francisco, CA 94110', wc_get_account_formatted_address( 'billing', $customer->get_id() ) );

		$customer->delete( true );
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
		$token->set_gateway_id( 'bacs' );
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
							'gateway'           => 'bacs',
							'last4'             => '1234',
							'brand'             => 'Mastercard',
							'is_co_branded'     => false,
							'networks'          => '',
							'preferred_network' => '',
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
		$token->set_gateway_id( 'bacs' );
		$token->set_card_type( 'mastercard' );
		$token->set_last4( '1234' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2020' );
		$token->save();

		$this->assertEquals(
			array(
				'method'  => array(
					'last4'             => '1234',
					'brand'             => 'Mastercard',
					'is_co_branded'     => false,
					'networks'          => null,
					'preferred_network' => null,
				),
				'expires' => '12/20',
			),
			wc_get_account_saved_payment_methods_list_item_cc( array(), $token )
		);

		$token->delete( true );

		// Co-branded credit card.
		$token = new WC_Payment_Token_CC();
		$token->set_token( '1001' );
		$token->set_gateway_id( 'bacs' );
		$token->set_card_type( 'visa' );
		$token->set_last4( '1001' );
		$token->set_expiry_month( '12' );
		$token->set_expiry_year( '2020' );
		$token->set_available_networks( array( 'visa', 'cartes_bancaires' ) );
		$token->set_preferred_network( 'cartes_bancaires' );
		$token->save();

		$this->assertEquals(
			array(
				'method'  => array(
					'last4'             => '1001',
					'brand'             => 'Visa',
					'is_co_branded'     => true,
					'networks'          => array( 'visa', 'cartes_bancaires' ),
					'preferred_network' => 'cartes_bancaires'
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
		$token->set_gateway_id( 'bacs' );
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
