<?php
/**
 * WooPaymentsExpressPaymentMethodTypes class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Provider-owned helpers for WooPayments Express Checkout Stripe payment method types.
 *
 * @since 11.0.0
 * @internal
 */
class WooPaymentsExpressPaymentMethodTypes {

	/**
	 * WooPayments Express Checkout method ID for Apple Pay / Google Pay.
	 */
	public const EXPRESS_METHOD_PAYMENT_REQUEST = 'payment_request';

	/**
	 * WooPayments Express Checkout method ID for Amazon Pay.
	 */
	public const EXPRESS_METHOD_AMAZON_PAY = 'amazon_pay';

	/**
	 * Stripe card payment method type.
	 */
	public const STRIPE_TYPE_CARD = 'card';

	/**
	 * Stripe Amazon Pay payment method type.
	 */
	public const STRIPE_TYPE_AMAZON_PAY = 'amazon_pay';

	/**
	 * Checkout field carrying Stripe payment method types used to initialize ECE.
	 */
	public const CHECKOUT_FIELD = 'wcpay-express-payment-method-types';

	/**
	 * Checkout field carrying the express checkout surface context.
	 */
	public const CONTEXT_FIELD = 'wcpay-express-checkout-context';

	/**
	 * Provider data key for validated submitted express payment method types.
	 */
	public const PROVIDER_DATA_KEY = 'express_payment_method_types';

	/**
	 * Provider data key for the submitted express checkout context.
	 */
	public const PROVIDER_CONTEXT_KEY = 'express_checkout_context';

	/**
	 * Amazon Pay feature flag option.
	 */
	private const AMAZON_PAY_FEATURE_FLAG_NAME = '_wcpay_feature_amazon_pay';

	/**
	 * Get allowed Stripe payment method types for the enabled express methods in a context.
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 * @param array<int,string>         $enabled_methods Enabled WooPayments express method IDs.
	 * @param string                    $context         Express checkout context.
	 * @param string                    $currency        Optional order/cart currency.
	 * @return array<int,string>
	 */
	public static function get_allowed_payment_method_types_for_methods( WooPaymentsAccountService $account_service, array $enabled_methods, string $context = 'checkout', string $currency = '' ): array {
		$allowed = array();
		$context = self::normalize_context( $context );

		if ( in_array( self::EXPRESS_METHOD_PAYMENT_REQUEST, $enabled_methods, true ) ) {
			$allowed[] = self::STRIPE_TYPE_CARD;
		}

		if ( in_array( self::EXPRESS_METHOD_AMAZON_PAY, $enabled_methods, true ) && self::can_use_amazon_pay( $account_service, $context, $currency ) ) {
			$allowed[] = self::STRIPE_TYPE_AMAZON_PAY;
		}

		return array_values( array_unique( $allowed ) );
	}

	/**
	 * Get allowed Stripe payment method types from account settings.
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 * @param string                    $context         Express checkout context.
	 * @param string                    $currency        Optional order/cart currency.
	 * @return array<int,string>
	 */
	public static function get_allowed_payment_method_types_for_account( WooPaymentsAccountService $account_service, string $context = 'checkout', string $currency = '' ): array {
		$context         = self::normalize_context( $context );
		$setting_context = 'pay_for_order' === $context ? 'checkout' : $context;
		$enabled_methods = array();
		$methods         = $account_service->get_gateway_setting( 'express_checkout_' . $setting_context . '_methods', null );

		if ( is_array( $methods ) ) {
			$enabled_methods = self::normalize_express_method_ids( $methods );
		}

		if ( empty( $enabled_methods ) && self::is_truthy_gateway_setting( $account_service, self::EXPRESS_METHOD_PAYMENT_REQUEST ) ) {
			$enabled_methods[] = self::EXPRESS_METHOD_PAYMENT_REQUEST;
		}

		return self::get_allowed_payment_method_types_for_methods( $account_service, array_values( array_unique( $enabled_methods ) ), $context, $currency );
	}

	/**
	 * Create provider data from the submitted checkout field value.
	 *
	 * @param mixed $value Submitted checkout field value.
	 * @return array<string,array<int,string>>
	 */
	public static function provider_data_from_checkout_value( $value ): array {
		$payment_method_types = self::normalize_payment_method_types( self::decode_payment_method_types( $value ) );

		return empty( $payment_method_types ) ? array() : array( self::PROVIDER_DATA_KEY => $payment_method_types );
	}

	/**
	 * Create provider data from the submitted express checkout context field value.
	 *
	 * @param mixed $value Submitted checkout field value.
	 * @return array<string,string>
	 */
	public static function provider_context_from_checkout_value( $value ): array {
		if ( ! is_scalar( $value ) || '' === (string) $value ) {
			return array();
		}

		return array( self::PROVIDER_CONTEXT_KEY => self::normalize_context( (string) $value ) );
	}

	/**
	 * Normalize an express checkout context.
	 *
	 * @param string $context Context.
	 * @return string
	 */
	public static function normalize_context( string $context ): string {
		$context = sanitize_key( $context );

		return in_array( $context, array( 'product', 'cart', 'checkout', 'pay_for_order' ), true ) ? $context : 'checkout';
	}

	/**
	 * Validate submitted Stripe payment method types against the server-side allowlist.
	 *
	 * @param mixed             $submitted_types Submitted payment method types.
	 * @param array<int,string> $allowed_types   Server-allowed payment method types.
	 * @return array<int,string>
	 */
	public static function validate_submitted_payment_method_types( $submitted_types, array $allowed_types ): array {
		if ( ! is_array( $submitted_types ) ) {
			return array();
		}

		$submitted_types = self::normalize_payment_method_types( $submitted_types );

		return array_values( array_intersect( $submitted_types, $allowed_types ) );
	}

	/**
	 * Normalize submitted Stripe payment method types.
	 *
	 * @param array<int,mixed> $payment_method_types Raw payment method types.
	 * @return array<int,string>
	 */
	private static function normalize_payment_method_types( array $payment_method_types ): array {
		$normalized = array();

		foreach ( $payment_method_types as $payment_method_type ) {
			if ( ! is_scalar( $payment_method_type ) ) {
				continue;
			}

			$payment_method_type = sanitize_key( (string) $payment_method_type );
			if ( in_array( $payment_method_type, array( self::STRIPE_TYPE_CARD, self::STRIPE_TYPE_AMAZON_PAY ), true ) ) {
				$normalized[] = $payment_method_type;
			}
		}

		return array_values( array_unique( $normalized ) );
	}

	/**
	 * Decode a JSON-encoded checkout field value.
	 *
	 * @param mixed $value Submitted field value.
	 * @return array<int,mixed>
	 */
	private static function decode_payment_method_types( $value ): array {
		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		$decoded = json_decode( $value, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Normalize WooPayments express method IDs.
	 *
	 * @param array<int,mixed> $methods Method IDs.
	 * @return array<int,string>
	 */
	private static function normalize_express_method_ids( array $methods ): array {
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
	 * Tell whether Amazon Pay can be used for express checkout.
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 * @param string                    $context         Express checkout context.
	 * @param string                    $currency        Optional order/cart currency.
	 * @return bool
	 */
	private static function can_use_amazon_pay( WooPaymentsAccountService $account_service, string $context, string $currency = '' ): bool {
		$context = self::normalize_context( $context );

		if ( '1' !== (string) get_option( self::AMAZON_PAY_FEATURE_FLAG_NAME, '1' ) ) {
			return false;
		}

		$account_data = $account_service->get_cached_account_data();
		if ( ! empty( $account_data['ece_confirmation_tokens_disabled'] ) ) {
			return false;
		}

		if ( function_exists( 'wc_tax_enabled' ) && wc_tax_enabled() && 'billing' === get_option( 'woocommerce_tax_based_on' ) && 'pay_for_order' !== $context ) {
			return false;
		}

		if ( ! self::is_amazon_pay_enabled_for_account( $account_service, $account_data ) ) {
			return false;
		}

		$currency = '' === $currency ? get_woocommerce_currency() : $currency;

		return self::is_amazon_pay_currency_supported( strtolower( $currency ), strtoupper( (string) ( $account_data['country'] ?? '' ) ) );
	}

	/**
	 * Tell whether Amazon Pay is available and enabled on the connected account.
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 * @param array<string,mixed>       $account_data    Cached account data.
	 * @return bool
	 */
	private static function is_amazon_pay_enabled_for_account( WooPaymentsAccountService $account_service, array $account_data ): bool {
		$available = $account_service->get_gateway_setting( 'upe_available_payment_methods', array() );

		$available = is_array( $available ) ? self::normalize_express_method_ids( $available ) : array();

		if ( ! empty( $available ) && ! in_array( self::EXPRESS_METHOD_AMAZON_PAY, $available, true ) ) {
			return false;
		}

		if ( isset( $account_data['payments_enabled'] ) && ! wc_string_to_bool( $account_data['payments_enabled'] ) ) {
			return false;
		}

		$capabilities = $account_data['capabilities'] ?? array();
		$fees         = $account_data['fees'] ?? array();

		return is_array( $capabilities )
			&& is_array( $fees )
			&& 'active' === ( $capabilities['amazon_pay_payments'] ?? null )
			&& is_array( $fees[ self::STRIPE_TYPE_AMAZON_PAY ] ?? null );
	}

	/**
	 * Tell whether a yes/no gateway setting is enabled.
	 *
	 * @param WooPaymentsAccountService $account_service WooPayments account service.
	 * @param string                    $key             Setting key.
	 * @return bool
	 */
	private static function is_truthy_gateway_setting( WooPaymentsAccountService $account_service, string $key ): bool {
		$value = $account_service->get_gateway_setting( $key, 'no' );

		return true === $value || 'yes' === $value || '1' === $value || 1 === $value;
	}

	/**
	 * Tell whether Amazon Pay supports a currency for the connected account country.
	 *
	 * @param string $currency        Currency code.
	 * @param string $account_country Connected account country.
	 * @return bool
	 */
	private static function is_amazon_pay_currency_supported( string $currency, string $account_country ): bool {
		if ( 'US' === $account_country ) {
			return 'usd' === $currency;
		}

		return in_array(
			$currency,
			array(
				'usd',
				'aud',
				'gbp',
				'dkk',
				'eur',
				'hkd',
				'jpy',
				'nzd',
				'nok',
				'sek',
				'chf',
				'zar',
			),
			true
		);
	}
}
