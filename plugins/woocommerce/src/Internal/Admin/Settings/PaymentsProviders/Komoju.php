<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * KOMOJU payment gateway provider class.
 *
 * This class handles all the custom logic for the KOMOJU payment gateway provider.
 */
class Komoju extends PaymentGateway {

	/**
	 * Get the settings URL for a payment gateway.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The settings URL for the payment gateway.
	 */
	public function get_settings_url( WC_Payment_Gateway $payment_gateway ): string {
		// KOMOJU's account connection and payment method selection happen on its own
		// dedicated settings tab, not on the legacy combined gateway's settings section.
		return admin_url( 'admin.php?page=wc-settings&tab=komoju_settings' );
	}

	/**
	 * Try to determine if the payment gateway is in test mode.
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			// KOMOJU has no dedicated test-mode setting; it infers the environment from
			// whether the stored secret key has the `sk_test_` (vs. `sk_live_`) prefix.
			if ( class_exists( '\WC_Gateway_Komoju' ) &&
				is_callable( '\WC_Gateway_Komoju::komoju_is_test_mode' ) ) {

				return wc_string_to_bool( \WC_Gateway_Komoju::komoju_is_test_mode() );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in test mode: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Check if the payment gateway has a payments processor account connected.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway account is connected, false otherwise.
	 *              If the payment gateway does not provide the information, it will return true.
	 */
	public function is_account_connected( WC_Payment_Gateway $payment_gateway ): bool {
		try {
			// KOMOJU doesn't expose a dedicated "is connected" API. It considers the merchant
			// connected once a secret key is saved, checking the current global option first
			// and falling back to the legacy per-gateway settings array, same as the plugin itself.
			$secret_key = get_option( 'komoju_woocommerce_secret_key' );
			if ( empty( $secret_key ) ) {
				$legacy_settings = get_option( 'woocommerce_komoju_settings' );
				$secret_key      = is_array( $legacy_settings ) ? ( $legacy_settings['secretKey'] ?? '' ) : '';
			}

			return ! empty( $secret_key );
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway account is connected: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return parent::is_account_connected( $payment_gateway );
	}
}
