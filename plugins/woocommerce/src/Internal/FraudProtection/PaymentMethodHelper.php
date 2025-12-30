<?php
/**
 * PaymentMethodHelper class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\FraudProtection;

defined( 'ABSPATH' ) || exit;

/**
 * Helper class for payment method data extraction and formatting.
 *
 * Provides shared functionality for extracting payment method information
 * including payment method IDs and their human-readable names.
 *
 * @since 10.5.0
 * @internal This class is part of the internal API and is subject to change without notice.
 */
class PaymentMethodHelper {
	/**
	 * Get readable payment method name from payment method ID.
	 *
	 * Retrieves the payment gateway title from the payment method ID by loading
	 * the payment gateway instance.
	 *
	 * @param string $payment_method_id Payment method ID (e.g., "stripe", "paypal", "bacs").
	 * @return string Payment method name or ID if name not found.
	 */
	public static function get_payment_method_name( string $payment_method_id ): string {
		try {
			// Get available payment gateways.
			$payment_gateways = WC()->payment_gateways()->payment_gateways();

			// Check if the payment method exists and has a title.
			if ( isset( $payment_gateways[ $payment_method_id ] ) ) {
				$gateway = $payment_gateways[ $payment_method_id ];
				if ( method_exists( $gateway, 'get_title' ) ) {
					return $gateway->get_title();
				} elseif ( isset( $gateway->title ) ) {
					return $gateway->title;
				}
			}

			// Return the ID as fallback if no title found.
			return $payment_method_id;
		} catch ( \Exception $e ) {
			// Graceful degradation - return the ID.
			FraudProtectionController::log(
				'warning',
				sprintf(
					'Failed to get payment method name: %s',
					$e->getMessage()
				),
				array(
					'payment_method_id' => $payment_method_id,
					'exception'         => $e,
				)
			);

			return $payment_method_id;
		}
	}
}
