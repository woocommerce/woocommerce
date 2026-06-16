<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;

/**
 * WooPayments payment method integration.
 *
 * @since 11.0.0
 */
final class WooPayments extends AbstractPaymentMethodType {

	/**
	 * Payment method name defined by payment methods extending this class.
	 *
	 * @var string
	 */
	protected $name = OrderPaymentStore::GATEWAY_ID;

	/**
	 * Asset API.
	 *
	 * @var Api
	 */
	private Api $asset_api;

	/**
	 * Checkout bridge.
	 *
	 * @var WooPaymentsCheckoutBridge
	 */
	private WooPaymentsCheckoutBridge $checkout_bridge;

	/**
	 * Native WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * Constructor.
	 *
	 * @param Api                       $asset_api       Asset API.
	 * @param WooPaymentsCheckoutBridge $checkout_bridge Checkout bridge.
	 * @param WooPaymentsProvider       $provider        Native WooPayments provider.
	 */
	public function __construct( Api $asset_api, WooPaymentsCheckoutBridge $checkout_bridge, WooPaymentsProvider $provider ) {
		$this->asset_api       = $asset_api;
		$this->checkout_bridge = $checkout_bridge;
		$this->provider        = $provider;
	}

	/**
	 * Initializes the payment method type.
	 */
	public function initialize(): void {}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->provider->can_process_payments() && $this->checkout_bridge->should_expose_checkout_surface();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		$this->asset_api->register_script(
			'wc-payment-method-woopayments',
			'assets/client/blocks/wc-payment-method-woopayments.js'
		);

		return array( 'wc-payment-method-woopayments' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array<string,mixed>
	 */
	public function get_payment_method_data() {
		return $this->checkout_bridge->get_blocks_payment_method_data();
	}
}
