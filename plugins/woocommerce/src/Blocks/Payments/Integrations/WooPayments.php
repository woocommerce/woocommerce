<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Assets\Api;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsCheckoutBridge;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsProvider;
use Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\WooPaymentsWooPaySessionService;

/**
 * WooPayments payment method integration.
 *
 * @since 11.0.0
 */
final class WooPayments extends AbstractPaymentMethodType {

	/**
	 * Stripe.js script handle.
	 */
	private const STRIPE_SCRIPT_HANDLE = 'stripe';

	/**
	 * Stripe.js script URL.
	 */
	private const STRIPE_SCRIPT_URL = 'https://js.stripe.com/v3/';

	/**
	 * Checkout Blocks script handle.
	 */
	private const CHECKOUT_BLOCKS_SCRIPT_HANDLE = 'wc-blocks-checkout';

	/**
	 * Blocks card payment method script handle.
	 */
	private const PAYMENT_METHOD_SCRIPT_HANDLE = 'wc-payment-method-woopayments';

	/**
	 * Blocks WooPay express payment method script handle.
	 */
	private const WOOPAY_SCRIPT_HANDLE = 'wc-payment-method-woopayments-woopay';

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
	 * WooPay session service.
	 *
	 * @var WooPaymentsWooPaySessionService
	 */
	private WooPaymentsWooPaySessionService $woopay_session_service;

	/**
	 * Constructor.
	 *
	 * @param Api                             $asset_api               Asset API.
	 * @param WooPaymentsCheckoutBridge       $checkout_bridge         Checkout bridge.
	 * @param WooPaymentsProvider             $provider                Native WooPayments provider.
	 * @param WooPaymentsWooPaySessionService $woopay_session_service WooPay session service.
	 */
	public function __construct( Api $asset_api, WooPaymentsCheckoutBridge $checkout_bridge, WooPaymentsProvider $provider, WooPaymentsWooPaySessionService $woopay_session_service ) {
		$this->asset_api              = $asset_api;
		$this->checkout_bridge        = $checkout_bridge;
		$this->provider               = $provider;
		$this->woopay_session_service = $woopay_session_service;
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
		$this->register_stripe_script();

		$this->asset_api->register_script(
			self::PAYMENT_METHOD_SCRIPT_HANDLE,
			'assets/client/blocks/wc-payment-method-woopayments.js',
			array( self::STRIPE_SCRIPT_HANDLE, self::CHECKOUT_BLOCKS_SCRIPT_HANDLE )
		);
		$this->asset_api->register_style(
			self::PAYMENT_METHOD_SCRIPT_HANDLE,
			'assets/client/blocks/wc-payment-method-woopayments.css',
			array(),
			'all',
			true
		);
		wp_enqueue_style( self::PAYMENT_METHOD_SCRIPT_HANDLE );

		$handles = array( self::PAYMENT_METHOD_SCRIPT_HANDLE );
		if ( $this->should_enqueue_woopay_assets() ) {
			$this->asset_api->register_script(
				self::WOOPAY_SCRIPT_HANDLE,
				'assets/client/blocks/wc-payment-method-woopayments-woopay.js',
				array( self::CHECKOUT_BLOCKS_SCRIPT_HANDLE )
			);
			$this->asset_api->register_style(
				self::WOOPAY_SCRIPT_HANDLE,
				'assets/client/blocks/wc-payment-method-woopayments-woopay.css',
				array(),
				'all',
				true
			);
			wp_enqueue_style( self::WOOPAY_SCRIPT_HANDLE );
			$handles[] = self::WOOPAY_SCRIPT_HANDLE;
		}

		return $handles;
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array<string,mixed>
	 */
	public function get_payment_method_data() {
		return $this->checkout_bridge->get_blocks_payment_method_data();
	}

	/**
	 * Register Stripe.js for the Blocks payment element.
	 */
	private function register_stripe_script(): void {
		if ( wp_script_is( self::STRIPE_SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( self::STRIPE_SCRIPT_HANDLE, self::STRIPE_SCRIPT_URL, array(), null, true );
	}

	/**
	 * Tell whether WooPay-specific Blocks assets should be loaded.
	 *
	 * @return bool
	 */
	private function should_enqueue_woopay_assets(): bool {
		return $this->provider->can_process_payments() &&
			$this->checkout_bridge->should_expose_checkout_surface() &&
			$this->woopay_session_service->should_show_woopay_button( $this->get_woopay_context() );
	}

	/**
	 * Get the current WooPay Blocks context.
	 *
	 * @return string
	 */
	private function get_woopay_context(): string {
		return function_exists( 'is_cart' ) && is_cart() ? 'cart' : 'checkout';
	}
}
