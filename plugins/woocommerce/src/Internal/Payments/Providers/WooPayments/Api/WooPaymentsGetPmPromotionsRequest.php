<?php
/**
 * WooPaymentsGetPmPromotionsRequest class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments\Providers\WooPayments\Api;

/**
 * Request object for the preserved WooPayments PM promotions list hook.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class WooPaymentsGetPmPromotionsRequest extends WooPaymentsApiRequest {

	protected const DEFAULT_PARAMS = array();

	private const API = 'payment_method_promotions';

	/**
	 * Create a request for payment method promotions.
	 *
	 * @param array<string,mixed> $store_context Store context parameters.
	 * @return self
	 */
	public static function from_store_context( array $store_context ): self {
		$request = new self();
		$request->set_api( self::API );
		$request->set_method( 'GET' );
		$request->set_store_context_params( $store_context );

		return $request;
	}

	/**
	 * Register the legacy WooPayments request alias when the extension is absent.
	 */
	public static function register_legacy_aliases(): void {
		parent::register_legacy_aliases();

		if ( ! class_exists( 'WCPay\Core\Server\Request\Get_PM_Promotions', false ) ) {
			class_alias( self::class, 'WCPay\Core\Server\Request\Get_PM_Promotions' );
		}
	}

	/**
	 * Preserve the legacy raw-response flag for consumers that inspect the request.
	 *
	 * @return bool
	 */
	public function should_return_raw_response(): bool {
		return true;
	}

	/**
	 * Attach store context details to the request.
	 *
	 * @param array<string,mixed> $context Store context parameters.
	 * @return void
	 */
	public function set_store_context_params( array $context ): void {
		foreach ( $context as $key => $value ) {
			if ( ! is_string( $key ) || null === $value || '' === $value || array() === $value ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$encoded_value = wp_json_encode( $value );
				if ( false === $encoded_value ) {
					continue;
				}

				$this->set_param( $key, $encoded_value );
				continue;
			}

			if ( is_scalar( $value ) ) {
				$this->set_param( $key, $value );
			}
		}
	}
}
