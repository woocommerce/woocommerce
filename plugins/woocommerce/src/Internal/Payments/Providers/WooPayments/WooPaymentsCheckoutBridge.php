<?php
/**
 * WooPaymentsCheckoutBridge class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

use Automattic\Jetpack\Constants;
use Automattic\WooCommerce\Internal\Payments\OrderPaymentStore;

/**
 * Owns the transitional Core checkout surface for the WooPayments card gateway.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsCheckoutBridge {

	/**
	 * Core-owned classic checkout script handle.
	 */
	private const CLASSIC_SCRIPT_HANDLE = 'wc-woopayments-checkout';

	/**
	 * Stripe.js script handle.
	 */
	private const STRIPE_SCRIPT_HANDLE = 'stripe';

	/**
	 * WooPayments legacy runtime.
	 *
	 * @var WooPaymentsLegacyRuntime
	 */
	private WooPaymentsLegacyRuntime $legacy_runtime;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsLegacyRuntime $legacy_runtime WooPayments legacy runtime.
	 */
	final public function init( WooPaymentsLegacyRuntime $legacy_runtime ): void {
		$this->legacy_runtime = $legacy_runtime;
	}

	/**
	 * Tell whether the bridge has enough data to expose the shopper checkout UI.
	 *
	 * @return bool
	 */
	public function should_expose_checkout_surface(): bool {
		return '' !== (string) $this->get_legacy_runtime()->get_gateway_publishable_key();
	}

	/**
	 * Get the classic checkout JS config.
	 *
	 * @return array<string,mixed>
	 */
	public function get_payment_fields_js_config(): array {
		$config = array(
			'publishableKey'                => (string) $this->get_legacy_runtime()->get_gateway_publishable_key(),
			'accountId'                     => (string) $this->get_legacy_runtime()->get_gateway_account_id(),
			'locale'                        => $this->get_stripe_locale(),
			'gatewayId'                     => OrderPaymentStore::GATEWAY_ID,
			'ajaxUrl'                       => admin_url( 'admin-ajax.php' ),
			'wcAjaxUrl'                     => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'paymentMethodsConfig'          => $this->get_payment_methods_config(),
			'enabledBillingFields'          => $this->get_enabled_billing_fields(),
			'currency'                      => get_woocommerce_currency(),
			'cartTotal'                     => $this->get_cart_total(),
			'customerData'                  => $this->get_legacy_runtime()->get_gateway_prepared_customer_data(),
			'usesLegacySetupIntentBridge'   => false,
			'usesLegacyOrderStatusBridge'   => false,
			'usesNativeSetupIntentBridge'   => true,
			'usesNativeOrderStatusBridge'   => true,
			'isCheckout'                    => function_exists( 'is_checkout' ) && is_checkout(),
			'isCoreNativeCheckoutBridge'    => true,
			'isCoreNativeCheckoutAvailable' => $this->should_expose_checkout_surface(),
			'confirmationErrorMessage'      => __( 'There was a problem confirming your payment.', 'woocommerce' ),
		);

		if ( $this->should_expose_checkout_surface() ) {
			$config['createSetupIntentNonce'] = wp_create_nonce( 'wcpay_create_setup_intent_nonce' );
			$config['updateOrderStatusNonce'] = wp_create_nonce( 'wcpay_update_order_status_nonce' );
		}

		/**
		 * Allows filtering of the JS config for the WooPayments payment fields.
		 *
		 * @since 11.0.0
		 *
		 * @param array $config The JS config for the payment fields.
		 */
		return apply_filters( 'wcpay_payment_fields_js_config', $config );
	}

	/**
	 * Render the classic checkout payment fields.
	 *
	 * @return void
	 */
	public function render_payment_fields(): void {
		$config      = $this->get_payment_fields_js_config();
		$json_config = wp_json_encode( $config );

		if ( ! is_string( $json_config ) ) {
			$json_config = '{}';
		}

		$this->register_classic_assets();
		wp_localize_script( self::CLASSIC_SCRIPT_HANDLE, 'wcpay_core_checkout_config', $config );
		wp_enqueue_script( self::CLASSIC_SCRIPT_HANDLE );

		if ( $this->should_expose_checkout_surface() ) {
			wp_enqueue_script( self::STRIPE_SCRIPT_HANDLE );
		}

		echo '<div id="wcpay-core-checkout-form" class="wcpay-core-checkout-form" data-wcpay-config="' . esc_attr( $json_config ) . '">';
		echo '<div id="wcpay-core-payment-element" class="wcpay-core-payment-element"></div>';
		echo '<div id="wcpay-core-payment-errors" class="woocommerce-error wcpay-core-payment-errors" role="alert" hidden></div>';

		if ( ! $this->should_expose_checkout_surface() ) {
			echo '<p class="woocommerce-info wcpay-core-checkout-unavailable">';
			echo esc_html__( 'WooPayments checkout is not available right now. Please choose another payment method.', 'woocommerce' );
			echo '</p>';
		}

		echo '</div>';
	}

	/**
	 * Get Blocks payment method data.
	 *
	 * @return array<string,mixed>
	 */
	public function get_blocks_payment_method_data(): array {
		return array_merge(
			$this->get_payment_fields_js_config(),
			array(
				'title'       => __( 'WooPayments', 'woocommerce' ),
				'description' => __( 'Pay securely using WooPayments.', 'woocommerce' ),
				'supports'    => array( 'products' ),
			)
		);
	}

	/**
	 * Register the classic checkout assets.
	 *
	 * @return void
	 */
	public function register_classic_assets(): void {
		if ( ! wp_script_is( self::STRIPE_SCRIPT_HANDLE, 'registered' ) ) {
			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_register_script( self::STRIPE_SCRIPT_HANDLE, 'https://js.stripe.com/v3/', array(), null, true );
		}

		if ( wp_script_is( self::CLASSIC_SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		$suffix = Constants::is_true( 'SCRIPT_DEBUG' ) ? '' : '.min';

		wp_register_script(
			self::CLASSIC_SCRIPT_HANDLE,
			WC()->plugin_url() . '/assets/js/frontend/woopayments-checkout' . $suffix . '.js',
			array( 'jquery', 'wc-checkout', self::STRIPE_SCRIPT_HANDLE ),
			WC_VERSION,
			true
		);
	}

	/**
	 * Get the WooPayments legacy runtime.
	 *
	 * @return WooPaymentsLegacyRuntime
	 */
	private function get_legacy_runtime(): WooPaymentsLegacyRuntime {
		if ( ! isset( $this->legacy_runtime ) ) {
			$this->legacy_runtime = wc_get_container()->get( WooPaymentsLegacyRuntime::class );
		}

		return $this->legacy_runtime;
	}

	/**
	 * Get the card-only payment method config for this slice.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function get_payment_methods_config(): array {
		$enabled_method_ids = $this->get_legacy_runtime()->get_gateway_upe_enabled_payment_method_ids();
		if ( ! empty( $enabled_method_ids ) && ! in_array( 'card', $enabled_method_ids, true ) ) {
			return array();
		}

		return array(
			'card' => array(
				'id'             => 'card',
				'label'          => __( 'Credit card / debit card', 'woocommerce' ),
				'showSaveOption' => true,
				'supports'       => array( 'products' ),
			),
		);
	}

	/**
	 * Get enabled billing field requirements.
	 *
	 * @return array<string,array{required:bool}>
	 */
	private function get_enabled_billing_fields(): array {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->checkout() ) {
			return array();
		}

		$enabled_fields = array();
		$billing_fields = WC()->checkout()->get_checkout_fields( 'billing' );
		foreach ( $billing_fields as $field_key => $field_options ) {
			if ( isset( $field_options['enabled'] ) && ! $field_options['enabled'] ) {
				continue;
			}

			$enabled_fields[ (string) $field_key ] = array(
				'required' => ! empty( $field_options['required'] ),
			);
		}

		return $enabled_fields;
	}

	/**
	 * Get cart total in the current WooCommerce minor unit convention.
	 *
	 * @return int
	 */
	private function get_cart_total(): int {
		if ( ! function_exists( 'WC' ) || ! WC() || ! WC()->cart ) {
			return 0;
		}

		$total    = (float) WC()->cart->get_total( '' );
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;

		return (int) round( $total * ( 10 ** $decimals ) );
	}

	/**
	 * Get a Stripe-compatible locale.
	 *
	 * @return string
	 */
	private function get_stripe_locale(): string {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
		$locale = strtolower( str_replace( '_', '-', (string) $locale ) );

		return '' !== $locale ? $locale : 'auto';
	}
}
