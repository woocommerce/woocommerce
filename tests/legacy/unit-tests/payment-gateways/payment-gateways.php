<?php
/**
 * @package WooCommerce\Tests\PaymentGateways
 */

/**
 * Class WC_Tests_Payment_Gateway.
 */
class WC_Tests_Payment_Gateway extends WC_Unit_Test_Case {

	/**
	 * Setup, enable payment gateways Cash on delivery and direct bank deposit.
	 */
	public function setUp() {
		parent::setUp();
		WC()->session = null;
		$wc_payment_gateways = WC_Payment_Gateways::instance();
		$wc_payment_gateways->init();
		foreach ( $wc_payment_gateways->payment_gateways() as $name => $gateway ) {
			if ( in_array( $name, array( 'cod', 'bacs' ) ) ) {
				$gateway->enabled = 'yes';
			}
		}
	}

	/**
	 * Initialize session that some tests might have removed.
	 */
	public function tearDown() {
		parent::tearDown();
		WC()->initialize_session();
	}

	/**
	 * Test that we can set a current gateway from session. Basic smoke test.
	 */
	public function test_wc_set_current_gateway_from_session() {
		WC()->initialize_session();
		wp_set_current_user( 1 );

		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$gateways['bacs']->chosen = false;
		WC()->session->set( 'chosen_payment_method', 'bacs' );
		WC()->payment_gateways()->set_current_gateway( $gateways );
		$this->assertTrue( $gateways['bacs']->chosen );
	}

	/**
	 * Test that we can set a current gateway without session.
	 */
	public function test_wc_set_current_gateway_without_session() {
		$gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$current_gateway = current( $gateways );
		$current_gateway->chosen = false;
		WC()->payment_gateways()->set_current_gateway( $gateways );
		$this->assertTrue( $current_gateway->chosen );
	}

}
