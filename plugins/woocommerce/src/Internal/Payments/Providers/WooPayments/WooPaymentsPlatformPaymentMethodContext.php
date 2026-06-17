<?php
/**
 * WooPaymentsPlatformPaymentMethodContext class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments;

/**
 * Provider-owned context for WooPayments platform-created payment methods.
 *
 * @since 11.0.0
 * @internal
 */
class WooPaymentsPlatformPaymentMethodContext {

	/**
	 * Checkout field that identifies a platform-created payment method.
	 */
	public const CHECKOUT_FIELD = 'wcpay-is-platform-payment-method';

	/**
	 * Provider data key that identifies a platform-created payment method.
	 */
	public const PROVIDER_DATA_KEY = 'is_platform_payment_method';

	/**
	 * Whether the submitted payment method was created on the Stripe platform account.
	 *
	 * @var bool
	 */
	private bool $is_platform_payment_method;

	/**
	 * Constructor.
	 *
	 * @param bool $is_platform_payment_method Whether the submitted payment method was created on the Stripe platform account.
	 */
	private function __construct( bool $is_platform_payment_method ) {
		$this->is_platform_payment_method = $is_platform_payment_method;
	}

	/**
	 * Create the context from WooPayments provider data.
	 *
	 * @param array<string,mixed> $provider_data WooPayments provider-scoped data.
	 * @return self
	 */
	public static function from_provider_data( array $provider_data ): self {
		return new self( filter_var( $provider_data[ self::PROVIDER_DATA_KEY ] ?? false, FILTER_VALIDATE_BOOLEAN ) );
	}

	/**
	 * Create provider data from a submitted checkout field value.
	 *
	 * @param mixed $value Submitted checkout field value.
	 * @return array<string,bool>
	 */
	public static function provider_data_from_checkout_value( $value ): array {
		return array(
			self::PROVIDER_DATA_KEY => filter_var( $value, FILTER_VALIDATE_BOOLEAN ),
		);
	}

	/**
	 * Tell whether the submitted payment method was created on the Stripe platform account.
	 *
	 * @return bool
	 */
	public function is_platform_payment_method(): bool {
		return $this->is_platform_payment_method;
	}

	/**
	 * Add platform-payment-method fields to a WCPay request payload.
	 *
	 * @param array<string,mixed> $request_data WCPay request data.
	 * @return array<string,mixed>
	 */
	public function apply_to_request_data( array $request_data ): array {
		if ( $this->is_platform_payment_method() ) {
			$request_data['is_platform_payment_method'] = true;
		}

		return $request_data;
	}
}
