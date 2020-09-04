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

		// non-admin user.
		$this->user_id = $this->login_as_role( 'shop_manager' );
		$this->assertEquals(
			array(
				'paypal' => false,
			),
			$this->get_gateways_statuses( $setup_wizard )
		);

		// set admin user.
		$this->user_id = $this->login_as_administrator();

		update_option( 'woocommerce_default_country', 'US' );
		$this->assertEquals(
			array(
				'stripe'      => true,
				'ppec_paypal' => false,
			),
			$this->get_gateways_statuses( $setup_wizard )
		);

		update_option( 'woocommerce_default_country', 'CN' );
		$this->assertEquals(
			array(
				'ppec_paypal' => true,
			),
			$this->get_gateways_statuses( $setup_wizard )
		);

		update_option( 'woocommerce_default_country', 'SE' );
		$this->assertEquals(
			array(
				'klarna_checkout' => true,
				'ppec_paypal'     => false,
				'stripe'          => true,
			),
			$this->get_gateways_statuses( $setup_wizard )
		);

		update_option( 'woocommerce_default_country', 'DE' );
		$this->assertEquals(
			array(
				'klarna_payments' => true,
				'ppec_paypal'     => false,
				'stripe'          => true,
			),
			$this->get_gateways_statuses( $setup_wizard )
		);

		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_sell_in_person', 'yes' );
		$this->assertEquals(
			array(
				'square'      => false,
				'ppec_paypal' => false,
				'stripe'      => true,
			),
			$this->get_gateways_statuses( $setup_wizard )
		);
	}

	/**
	 * Helper method to call the tested method and return a simplified version
	 * of the returned values. It returns only if the gateways are enable or not
	 * which is what we are currently checking in the tests.
	 *
	 * @param WC_Admin_Setup_Wizard $setup_wizard Setup wizard object.
	 *
	 * @return array
	 */
	protected function get_gateways_statuses( $setup_wizard ) {
		return array_map( array( $this, 'get_enabled' ), $setup_wizard->get_wizard_in_cart_payment_gateways() );
	}

	/**
	 * Return wheter a given gateway is enable or not.
	 *
	 * @param array $gateway Gateway information.
	 * @return bool
	 */
	protected function get_enabled( $gateway ) {
		return isset( $gateway['enabled'] ) && $gateway['enabled'];
	}

}
