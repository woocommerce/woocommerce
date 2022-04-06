<?php
/**
 * Contains tests for the COD Payment Gateway.
 *
 * @package WooCommerce\Tests\PaymentGateways
 */

use Automattic\Jetpack\Constants;

/**
 * Class WC_Tests_Payment_Gateway_COD
 */
class WC_Tests_Payment_Gateway_COD extends WC_Unit_Test_Case {

	/**
	 * Clean up after each test.
	 */
	public function tearDown() {
		parent::tearDown();

		Constants::clear_constants();
	}
	/**
	 * Make sure that the options for the "enable_for_methods" setting are not loaded by default.
	 */
	public function test_method_options_not_loaded_universally() {
		$gateway = new WC_Gateway_COD();

		$form_fields = $gateway->get_form_fields();

		$this->assertArrayHasKey( 'enable_for_methods', $form_fields );
		$this->assertEmpty( $form_fields['enable_for_methods']['options'] );
	}

	/**
	 * Make sure that the options for the "enable_for_methods" setting are loaded on the admin page.
	 */
	public function test_method_options_loaded_for_admin_page() {
		set_current_screen( 'woocommerce_page_wc-settings' );
		$_REQUEST['page']    = 'wc-settings';
		$_REQUEST['tab']     = 'checkout';
		$_REQUEST['section'] = 'cod';

		$gateway = new WC_Gateway_COD();

		$form_fields = $gateway->get_form_fields();

		// Clean up!
		$GLOBALS['current_screen'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		unset( $_REQUEST['page'] );
		unset( $_REQUEST['tab'] );
		unset( $_REQUEST['section'] );

		$this->assertArrayHasKey( 'enable_for_methods', $form_fields );
		$this->assertNotEmpty( $form_fields['enable_for_methods']['options'] );
	}

	/**
	 * Make sure that the options for the "enable_for_methods" setting are not loaded for API requests that don't need it.
	 */
	public function test_method_options_not_loaded_for_incorrect_api() {
		Constants::set_constant( 'REST_REQUEST', true );
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/v2/products';

		$gateway = new WC_Gateway_COD();

		$form_fields = $gateway->get_form_fields();

		$this->assertArrayHasKey( 'enable_for_methods', $form_fields );
		$this->assertEmpty( $form_fields['enable_for_methods']['options'] );
	}

	/**
	 * Make sure that the options for the "enable_for_methods" setting are loaded for API requests that need it.
	 */
	public function test_method_options_loaded_for_correct_api() {
		Constants::set_constant( 'REST_REQUEST', true );
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/v2/payment_gateways';

		$gateway = new WC_Gateway_COD();

		$form_fields = $gateway->get_form_fields();

		$this->assertArrayHasKey( 'enable_for_methods', $form_fields );
		$this->assertNotEmpty( $form_fields['enable_for_methods']['options'] );
	}
}
