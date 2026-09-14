<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\RestApi\Routes\V4\Settings\PaymentGateways;

use WC_Payment_Gateway;

/**
 * Mock payment gateway with password-type form fields for testing.
 */
class WCGatewayMockPassword extends WC_Payment_Gateway {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'mock_password';
		$this->method_title       = 'Mock Password Gateway';
		$this->method_description = 'A mock gateway for testing password field sanitization.';
		$this->has_fields         = false;

		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * Initialize form fields with password-type fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable Mock Password Gateway',
				'default' => 'no',
			),
			'title'        => array(
				'title'   => 'Title',
				'type'    => 'text',
				'default' => 'Mock Password Gateway',
			),
			'api_password' => array(
				'title' => 'API Password',
				'type'  => 'password',
			),
		);
	}

	/**
	 * Process payment — not used in tests.
	 *
	 * @param int $order_id Order ID.
	 */
	public function process_payment( $order_id ) {
		return array( 'result' => 'success' );
	}
}
