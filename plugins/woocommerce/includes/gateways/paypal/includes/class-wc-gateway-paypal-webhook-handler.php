<?php
/**
 * Class WC_Gateway_Paypal_Webhook_Handler file.
 *
 * @package WooCommerce\Gateways
 *
 * @deprecated 10.5.0 Deprecated in favor of Automattic\WooCommerce\Gateways\PayPal\WebhookHandler
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Gateways\PayPal\WebhookHandler as PayPalWebhookHandler;

if ( ! class_exists( 'WC_Gateway_Paypal_Helper' ) ) {
	require_once __DIR__ . '/class-wc-gateway-paypal-helper.php';
}

if ( ! class_exists( 'WC_Gateway_Paypal_Request' ) ) {
	require_once __DIR__ . '/class-wc-gateway-paypal-request.php';
}

/**
 * Handles webhook events.
 *
 * @deprecated 10.5.0 Deprecated in favor of Automattic\WooCommerce\Gateways\PayPal\WebhookHandler
 */
class WC_Gateway_Paypal_Webhook_Handler {

	/**
	 * The delegated webhook handler instance.
	 *
	 * @var PayPalWebhookHandler
	 */
	private PayPalWebhookHandler $webhook_handler;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->webhook_handler = new PayPalWebhookHandler();
	}

	/**
	 * Process the webhook event.
	 *
	 * @deprecated 10.5.0 Use Automattic\WooCommerce\Gateways\PayPal\WebhookHandler::process_webhook() instead. This method will be removed in 11.0.0.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return void
	 *
	 * @deprecated 10.5.0 Deprecated in favor of Automattic\WooCommerce\Gateways\PayPal\WebhookHandler::process_webhook
	 */
	public function process_webhook( WP_REST_Request $request ) {
		wc_deprecated_function(
			__METHOD__,
			'10.5.0',
			PayPalWebhookHandler::class . '::process_webhook()'
		);

		$this->webhook_handler->process_webhook( $request );
	}
}
