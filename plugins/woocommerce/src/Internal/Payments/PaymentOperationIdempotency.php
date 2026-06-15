<?php
/**
 * PaymentOperationIdempotency class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Payments;

use WC_Order;

/**
 * Builds deterministic idempotency keys for native payment operations.
 *
 * @since 11.0.0
 * @internal Transitional internal component for the native payments runtime.
 */
class PaymentOperationIdempotency {

	/**
	 * Derive a deterministic idempotency key for an order-scoped provider operation.
	 *
	 * @since 11.0.0
	 *
	 * @param WC_Order   $order       Order being acted on.
	 * @param string     $provider_id Provider/gateway ID.
	 * @param string     $operation   Operation name.
	 * @param float|null $amount      Operation amount.
	 * @param string     $currency    Operation currency.
	 * @param string     $reason      Operation reason.
	 * @return string
	 */
	public function derive_key( WC_Order $order, string $provider_id, string $operation, ?float $amount = null, string $currency = '', string $reason = '' ): string {
		$site_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;
		$parts   = array(
			'site'      => (string) $site_id,
			'order'     => (string) $order->get_id(),
			'provider'  => $provider_id,
			'operation' => $operation,
			'amount'    => null === $amount ? '' : wc_format_decimal( $amount, wc_get_price_decimals() ),
			'currency'  => strtoupper( '' === $currency ? (string) $order->get_currency() : $currency ),
			'reason'    => $reason,
		);

		$encoded = wp_json_encode( $parts );

		return 'wc_native_payments_' . md5( false === $encoded ? implode( '|', $parts ) : $encoded );
	}
}
