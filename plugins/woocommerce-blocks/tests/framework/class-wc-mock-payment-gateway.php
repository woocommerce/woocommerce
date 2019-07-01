<?php
class WC_Mock_Payment_Gateway extends WC_Payment_Gateway {
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'mock';
		$this->has_fields         = false;
		$this->order_button_text  = __( 'Proceed to PayPal', 'woocommerce' );
		$this->method_title       = 'Mock Gateway';
		$this->method_description = 'Mock Gateway for unit tests';
		$this->supports           = array(
			'products',
		);

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
	}
}

