<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Blocks\StoreApi\Routes;

/**
 * Mock Agentic Payment Gateway for testing.
 *
 * This gateway supports the agentic_commerce feature and is used
 * in CheckoutSessionsComplete tests.
 */
class MockAgenticPaymentGateway extends \WC_Payment_Gateway {
	public const GATEWAY_ID = 'mock_agentic_payment_gateway';
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->enabled            = 'yes';
		$this->id                 = self::GATEWAY_ID;
		$this->has_fields         = false;
		$this->method_title       = 'Mock Agentic Gateway';
		$this->method_description = 'Mock Gateway for agentic commerce testing';
		$this->supports           = array(
			\Automattic\WooCommerce\Enums\PaymentGatewayFeature::PRODUCTS,
			\Automattic\WooCommerce\Enums\PaymentGatewayFeature::AGENTIC_COMMERCE,
		);

		$this->init_form_fields();
		$this->init_settings();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => '',
				'type'    => 'checkbox',
				'label'   => '',
				'default' => 'yes',
			),
		);
	}

	/**
	 * Get the agentic commerce provider name.
	 *
	 * @return string Provider name.
	 */
	public function get_agentic_commerce_provider() {
		return 'stripe';
	}

	/**
	 * Get supported payment methods for agentic commerce.
	 *
	 * @return array List of supported payment methods.
	 */
	public function get_agentic_commerce_payment_methods() {
		return array( 'card' );
	}

	/**
	 * Process payment for agentic commerce.
	 *
	 * @param int $order_id Order ID.
	 * @return array Payment result.
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Simulate successful payment processing.
		$order->payment_complete();
		$order->add_order_note( 'Mock agentic payment completed successfully.' );

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Validate fields before processing payment.
	 *
	 * @return bool Whether fields are valid.
	 */
	public function validate_fields() {
		return true;
	}
}
