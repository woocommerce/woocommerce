<?php
/**
 * Class WC_Tests_Setup_Functions.
 * @package WooCommerce\Tests\Setup
 */

/**
 * Class WC_Tests_Setup_Functions.
 */
class WC_Tests_Setup_Functions extends WC_Unit_Test_Case {

	/**
	 * Test get_wizard_in_cart_payment_gateways.
	 * Verifies contents, order, and default enabled state of offered gateways.
	 *
	 * @since 3.3
	 */
	public function test_wizard_in_cart_payment_gateways() {
		$setup_wizard = new WC_Admin_Setup_Wizard();

		/**
		 * Return wheter a given gateway is enable or not.
		 *
		 * @param array $gateway Gateway information.
		 * @return bool
		 */
		function get_enabled( $gateway ) {
			return isset( $gateway['enabled'] ) && $gateway['enabled'];
		}

		/**
		 * Helper function to call the tested method and return a simplified version
		 * of the returned values. It returns only if the gateways are enable or not
		 * which is what we are currently checking in the tests.
		 *
		 * @param WC_Admin_Setup_Wizard $setup_wizard Setup wizard object.
		 *
		 * @return array
		 */
		function gateways( $setup_wizard ) {
			return array_map( 'get_enabled', $setup_wizard->get_wizard_in_cart_payment_gateways() );
		}

		// non-admin user.
		$this->user_id = $this->factory->user->create( array( 'role' => 'shop_manager' ) );
		wp_set_current_user( $this->user_id );
		$this->assertEquals( gateways( $setup_wizard ), array(
			'paypal' => false,
		) );

		// set admin user.
		$this->user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->user_id );

		update_option( 'woocommerce_default_country', 'US' );
		$this->assertEquals( gateways( $setup_wizard ), array(
			'stripe' => true,
			'ppec_paypal' => true,
		) );

		update_option( 'woocommerce_default_country', 'CN' );
		$this->assertEquals( gateways( $setup_wizard ), array(
			'ppec_paypal' => true,
		) );

		update_option( 'woocommerce_default_country', 'SE' );
		$this->assertEquals( gateways( $setup_wizard ), array(
			'klarna_checkout' => true,
			'ppec_paypal' => true,
			'stripe' => false,
		) );

		update_option( 'woocommerce_default_country', 'DE' );
		$this->assertEquals( gateways( $setup_wizard ), array(
			'klarna_payments' => true,
			'ppec_paypal' => true,
			'stripe' => false,
		) );

		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_sell_in_person', 'yes' );
		$this->assertEquals( gateways( $setup_wizard ), array(
			'square' => true,
			'ppec_paypal' => true,
			'stripe' => false,
		) );
    }
}
