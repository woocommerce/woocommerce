<?php
/**
 * WooPaymentsExpressCheckoutService class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Native WooPayments express checkout helpers for platform payment methods.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsExpressCheckoutService {

	private const PAYMENT_REQUEST_METHOD = 'payment_request';

	/**
	 * WooPayments account service.
	 *
	 * @var WooPaymentsAccountService
	 */
	private WooPaymentsAccountService $account_service;

	/**
	 * WooPayments provider.
	 *
	 * @var WooPaymentsProvider
	 */
	private WooPaymentsProvider $provider;

	/**
	 * Initialize the class instance.
	 *
	 * @internal
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 * @param WooPaymentsProvider       $provider        WooPayments provider.
	 */
	final public function init( WooPaymentsAccountService $account_service, WooPaymentsProvider $provider ): void {
		$this->account_service = $account_service;
		$this->provider        = $provider;
	}

	/**
	 * Tell whether the payment-request express checkout button should be shown.
	 *
	 * @param string $context Express checkout context.
	 * @return bool
	 */
	public function should_show_payment_request_button( string $context = 'checkout' ): bool {
		return $this->provider->can_process_payments()
			&& in_array( self::PAYMENT_REQUEST_METHOD, $this->get_enabled_methods_for_context( $context ), true );
	}

	/**
	 * Get express checkout params for Apple Pay and Google Pay.
	 *
	 * @param string $context Express checkout context.
	 * @return array<string,mixed>
	 */
	public function get_express_checkout_params( string $context = 'checkout' ): array {
		$currency = strtolower( get_woocommerce_currency() );
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;

		return array(
			'ajax_url'           => admin_url( 'admin-ajax.php' ),
			'wc_ajax_url'        => \WC_AJAX::get_endpoint( '%%endpoint%%' ),
			'nonce'              => array(
				'platform_tracker'             => wp_create_nonce( 'platform_tracks_nonce' ),
				'tokenized_cart_nonce'         => wp_create_nonce( 'woopayments_tokenized_cart_nonce' ),
				'tokenized_cart_session_nonce' => wp_create_nonce( 'woopayments_tokenized_cart_session_nonce' ),
				'store_api_nonce'              => wp_create_nonce( 'wc_store_api' ),
			),
			'checkout'           => array(
				'currency_code'              => $currency,
				'currency_decimals'          => $decimals,
				'stripe_minor_unit'          => 10 ** $decimals,
				'country_code'               => $this->get_store_base_country(),
				'needs_shipping'             => function_exists( 'WC' ) && WC() && WC()->cart ? WC()->cart->needs_shipping() : false,
				'needs_payer_phone'          => 'required' === get_option( 'woocommerce_checkout_phone_field', 'required' ),
				'allowed_shipping_countries' => function_exists( 'WC' ) && WC() && WC()->countries ? array_keys( WC()->countries->get_shipping_countries() ?? array() ) : array(),
				'display_prices_with_tax'    => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			),
			'has_subscription'   => class_exists( '\WC_Subscriptions_Cart' ) && is_callable( array( '\WC_Subscriptions_Cart', 'cart_contains_subscription' ) ) && \WC_Subscriptions_Cart::cart_contains_subscription(),
			'is_manual_capture'  => $this->is_truthy_gateway_setting( 'manual_capture' ),
			'button'             => $this->get_button_settings( $context ),
			'login_confirmation' => false,
			'button_context'     => $this->normalize_button_context( $context ),
			'has_block'          => has_block( 'woocommerce/cart' ) || has_block( 'woocommerce/checkout' ),
			'product'            => array(),
			'store_name'         => get_bloginfo( 'name' ),
			'enabled_methods'    => $this->get_enabled_methods_for_context( $context ),
			'stripe'             => array(
				'publishableKey' => $this->account_service->get_publishable_key(),
				'accountId'      => $this->account_service->get_account_id(),
				'locale'         => $this->get_stripe_locale(),
			),
			'flags'              => array(
				'isEceUsingConfirmationTokens' => true,
			),
		);
	}

	/**
	 * Get enabled express checkout platform methods for a context.
	 *
	 * @param string $context Express checkout context.
	 * @return array<int,string>
	 */
	public function get_enabled_methods_for_context( string $context = 'checkout' ): array {
		$context = $this->normalize_button_context( $context );
		$methods = $this->get_configured_methods_for_context( $context );

		/**
		 * Filters native WooPayments platform express checkout methods for a context.
		 *
		 * @param array<int,string>                 $methods Enabled method IDs.
		 * @param string                            $context Express checkout context.
		 * @param WooPaymentsExpressCheckoutService $service Native express checkout service.
		 *
		 * @since 11.0.0
		 */
		$filtered_methods = apply_filters( 'woocommerce_native_woopayments_express_checkout_enabled_methods', $methods, $context, $this );

		return is_array( $filtered_methods ) ? $this->normalize_method_list( $filtered_methods ) : $methods;
	}

	/**
	 * Tell whether the gateway-level payment-request switch is enabled.
	 *
	 * @return bool
	 */
	private function is_payment_request_enabled(): bool {
		return $this->is_truthy_gateway_setting( self::PAYMENT_REQUEST_METHOD );
	}

	/**
	 * Get configured express checkout methods for a context.
	 *
	 * @param string $context Express checkout context.
	 * @return array<int,string>
	 */
	private function get_configured_methods_for_context( string $context ): array {
		$setting_key = 'express_checkout_' . $context . '_methods';
		$methods     = $this->account_service->get_gateway_setting( $setting_key, null );

		if ( is_array( $methods ) ) {
			return $this->normalize_method_list( $methods );
		}

		return $this->is_payment_request_enabled() ? array( self::PAYMENT_REQUEST_METHOD ) : array();
	}

	/**
	 * Normalize express checkout method IDs.
	 *
	 * @param array<int,mixed> $methods Method IDs.
	 * @return array<int,string>
	 */
	private function normalize_method_list( array $methods ): array {
		$normalized = array();

		foreach ( $methods as $method ) {
			if ( ! is_scalar( $method ) ) {
				continue;
			}

			$method = sanitize_key( (string) $method );
			if ( '' !== $method ) {
				$normalized[] = $method;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Get button settings shared by Apple Pay and Google Pay.
	 *
	 * @param string $context Express checkout context.
	 * @return array<string,string>
	 */
	private function get_button_settings( string $context ): array {
		$type = $this->get_string_gateway_setting( 'payment_request_button_type', 'default' );

		return array(
			'type'         => $type,
			'theme'        => $this->get_string_gateway_setting( 'payment_request_button_theme', 'dark' ),
			'height'       => $this->get_button_height(),
			'radius'       => $this->get_string_gateway_setting_allow_empty( 'payment_request_button_border_radius', '' ),
			'size'         => $this->get_string_gateway_setting( 'payment_request_button_size', 'default' ),
			'context'      => $this->normalize_button_context( $context ),
			'locale'       => substr( get_locale(), 0, 2 ),
			'branded_type' => 'default' === $type ? 'short' : 'long',
		);
	}

	/**
	 * Get the button height from the express checkout size setting.
	 *
	 * @return string
	 */
	private function get_button_height(): string {
		$size = $this->get_string_gateway_setting( 'payment_request_button_size', 'medium' );

		if ( 'medium' === $size ) {
			return '48';
		}

		if ( 'large' === $size ) {
			return '55';
		}

		return '40';
	}

	/**
	 * Normalize an express checkout context.
	 *
	 * @param string $context Context.
	 * @return string
	 */
	private function normalize_button_context( string $context ): string {
		$context = sanitize_key( $context );

		return in_array( $context, array( 'product', 'cart', 'checkout' ), true ) ? $context : 'checkout';
	}

	/**
	 * Get a string gateway setting.
	 *
	 * @param string $key      Setting key.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private function get_string_gateway_setting( string $key, string $fallback ): string {
		$value = $this->account_service->get_gateway_setting( $key, $fallback );

		return is_scalar( $value ) && '' !== (string) $value ? sanitize_text_field( (string) $value ) : $fallback;
	}

	/**
	 * Get a string gateway setting while preserving empty string values.
	 *
	 * @param string $key      Setting key.
	 * @param string $fallback Fallback value.
	 * @return string
	 */
	private function get_string_gateway_setting_allow_empty( string $key, string $fallback ): string {
		$value = $this->account_service->get_gateway_setting( $key, $fallback );

		return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $fallback;
	}

	/**
	 * Tell whether a gateway setting is truthy.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	private function is_truthy_gateway_setting( string $key ): bool {
		$value = $this->account_service->get_gateway_setting( $key, 'no' );

		return true === $value || 'yes' === $value || '1' === $value || 1 === $value;
	}

	/**
	 * Get the store base country.
	 *
	 * @return string
	 */
	private function get_store_base_country(): string {
		$country = (string) get_option( 'woocommerce_default_country', 'US' );
		if ( function_exists( 'WC' ) && WC() && WC()->countries ) {
			$country = (string) WC()->countries->get_base_country();
		}

		if ( false !== strpos( $country, ':' ) ) {
			$base_country = strtok( $country, ':' );
			$country      = is_string( $base_country ) ? $base_country : '';
		}

		$country = strtoupper( $country );

		return '' !== $country ? $country : 'US';
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
