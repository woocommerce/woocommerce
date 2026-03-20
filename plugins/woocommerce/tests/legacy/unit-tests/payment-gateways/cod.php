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
	public function tearDown(): void {
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
		$_REQUEST['section'] = WC_Gateway_COD::ID;

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

	/**
	 * Make sure is_available() returns early for disabled gateways.
	 */
	public function test_is_available_returns_early_when_disabled() {
		$gateway          = new WC_Gateway_COD();
		$gateway->enabled = 'no';

		$cart                    = WC()->cart;
		$has_order_pay_query_var = array_key_exists( 'order-pay', $GLOBALS['wp']->query_vars );
		$order_pay_query_var     = $has_order_pay_query_var ? $GLOBALS['wp']->query_vars['order-pay'] : null;

		try {
			WC()->cart = new class() {
				/**
				 * Number of times needs_shipping() is called.
				 *
				 * @var int
				 */
				public $needs_shipping_call_count = 0;

				/**
				 * Track calls to needs_shipping().
				 *
				 * @return bool
				 */
				public function needs_shipping() {
					++$this->needs_shipping_call_count;
					return false;
				}
			};
			unset( $GLOBALS['wp']->query_vars['order-pay'] );

			$this->assertFalse( $gateway->is_available() );
			$this->assertSame( 0, WC()->cart->needs_shipping_call_count );
		} finally {
			WC()->cart = $cart;

			if ( $has_order_pay_query_var ) {
				$GLOBALS['wp']->query_vars['order-pay'] = $order_pay_query_var;
			}
		}
	}
}
