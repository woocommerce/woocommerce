<?php
/**
 *
 */

/**
 *
 */
class WC_Tests_WC_Query extends WC_Unit_Test_Case {

	public function test_instance() {
		$this->assertInstanceOf( 'WC_Query', WC()->query );
	}

	public function test_get_errors() {
		$_GET['wc_error'] = 'test';

		WC()->query->get_errors();
		$this->assertTrue( wc_has_notice( 'test', 'error' ) );

		// Clean up.
		unset( $_GET['wc_error'] );
		wc_clear_notices();

		WC()->query->get_errors();
		$this->assertFalse( wc_has_notice( 'test', 'error' ) );

	}

	public function test_init_query_vars() {
		// Test the default options are present.
		WC()->query->init_query_vars();
		$default_vars = WC()->query->get_query_vars();
		$expected = array(
			'product-page'               => '',
			'order-pay'                  => 'order-pay',
			'order-received'             => 'order-received',
			'orders'                     => 'orders',
			'view-order'                 => 'view-order',
			'downloads'                  => 'downloads',
			'edit-account'               => 'edit-account',
			'edit-address'               => 'edit-address',
			'payment-methods'            => 'payment-methods',
			'lost-password'              => 'lost-password',
			'customer-logout'            => 'customer-logout',
			'add-payment-method'         => 'add-payment-method',
			'delete-payment-method'      => 'delete-payment-method',
			'set-default-payment-method' => 'set-default-payment-method',
		);
		$this->assertEquals( $expected, $default_vars );

		// Test updating a setting works.
		update_option( 'woocommerce_checkout_pay_endpoint', 'order-pay-new' );
		WC()->query->init_query_vars();
		$updated_vars = WC()->query->get_query_vars();
		$this->assertEquals( 'order-pay-new', $updated_vars['order-pay'] );

		// Clean up.
		update_option( 'woocommerce_checkout_pay_endpoint', 'order-pay' );
	}

}
