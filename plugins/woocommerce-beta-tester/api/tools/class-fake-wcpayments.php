<?php
/**
 * Fake WooPayments class.
 *
 * @package WC_Beta_Tester
 */
class Fake_WCPayments extends WC_Payment_Gateway_WCPay {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'woocommerce_payments';
		$this->has_fields         = true;
		$this->method_title       = 'WooPayments';
		$this->method_description = $this->get_method_description();

		$this->description = '';
		$this->supports    = array(
			'products',
			'refunds',
		);
	}

	/**
	 * Returns true if the gateway needs additional configuration, false if it's ready to use.
	 *
	 * @see WC_Payment_Gateway::needs_setup
	 * @return bool
	 */
	public function needs_setup() {
		return false;
	}

	/**
	 * Check if the payment gateway is connected. This method is also used by
	 * external plugins to check if a connection has been established.
	 */
	public function is_connected() {
		return true;
	}

	/**
	 * Get the connection URL.
	 * Called directly by WooCommerce Core.
	 *
	 * @return string Connection URL.
	 */
	public function get_connection_url() {
		return '';
	}

	/**
	 * Checks if the gateway is enabled, and also if it's configured enough to accept payments from customers.
	 *
	 * Use parent method value alongside other business rules to make the decision.
	 *
	 * @return bool Whether the gateway is enabled and ready to accept payments.
	 */
	public function is_available() {
		return true;
	}
}
