<?php
/**
 * Contains tests for the COD Payment Gateway.
 *
 * @package WooCommerce\Tests\PaymentGateways
 */

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Enums\OrderStatus;

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

	/**
	 * Render the COD email instructions for an order with the given status.
	 *
	 * @param string $order_status  Status to set on the order.
	 * @param bool   $sent_to_admin Whether the email is sent to the admin.
	 * @return string The rendered output.
	 */
	private function get_email_instructions_output( $order_status, $sent_to_admin = false ) {
		$gateway               = new WC_Gateway_COD();
		$gateway->instructions = 'Please have exact change ready.';

		$order = WC_Helper_Order::create_order();
		$order->set_payment_method( WC_Gateway_COD::ID );
		$order->set_status( $order_status );
		$order->save();

		ob_start();
		$gateway->email_instructions( $order, $sent_to_admin );
		return ob_get_clean();
	}

	/**
	 * @testdox Instructions are shown for orders where COD payment is still outstanding.
	 *
	 * @dataProvider provider_unpaid_order_statuses
	 * @param string $order_status Status to set on the order.
	 */
	public function test_email_instructions_are_included_for_unpaid_orders( $order_status ) {
		$output = $this->get_email_instructions_output( $order_status );

		$this->assertStringContainsString(
			'Please have exact change ready.',
			$output,
			"COD instructions should be shown for orders with the '{$order_status}' status"
		);
	}

	/**
	 * Order statuses for which COD payment has not been collected yet.
	 *
	 * @return array
	 */
	public function provider_unpaid_order_statuses() {
		return array(
			'pending'    => array( OrderStatus::PENDING ),
			'on-hold'    => array( OrderStatus::ON_HOLD ),
			'processing' => array( OrderStatus::PROCESSING ),
		);
	}

	/**
	 * @testdox Instructions are hidden once the order is settled and no longer awaiting payment.
	 *
	 * @dataProvider provider_settled_order_statuses
	 * @param string $order_status Status to set on the order.
	 */
	public function test_email_instructions_are_omitted_for_settled_orders( $order_status ) {
		$output = $this->get_email_instructions_output( $order_status );

		$this->assertSame(
			'',
			$output,
			"COD instructions should be hidden for orders with the '{$order_status}' status"
		);
	}

	/**
	 * Order statuses for which COD instructions are no longer relevant.
	 *
	 * @return array
	 */
	public function provider_settled_order_statuses() {
		return array(
			'completed' => array( OrderStatus::COMPLETED ),
			'cancelled' => array( OrderStatus::CANCELLED ),
			'refunded'  => array( OrderStatus::REFUNDED ),
			'failed'    => array( OrderStatus::FAILED ),
		);
	}
}
