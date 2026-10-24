<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Logging\SafeGlobalFunctionProxy;
use Throwable;
use WC_Payment_Gateway;

defined( 'ABSPATH' ) || exit;

/**
 * Mastercard Merchant Cloud payment gateway provider class.
 *
 * This class handles all the custom logic for the Mastercard Merchant Cloud payment gateway provider.
 *
 * The gateway exposes none of the method names, properties, or option keys that the generic
 * provider probes for, so without these overrides an unconfigured gateway reports itself as
 * having a connected account, and its sandbox mode never surfaces.
 *
 * Everything here is read through the standard WC_Settings_API get_option() contract and is
 * best-effort: a gateway that does not answer as expected falls back to the parent class
 * rather than failing.
 *
 * @internal
 *
 * @since 11.1.0
 */
class Mastercard extends PaymentGateway {

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
			$sandbox_mode = $this->is_mastercard_in_sandbox_mode( $payment_gateway );
			// Let null results bubble up to the parent class.
			if ( null !== $sandbox_mode ) {
				// The gateway keeps a separate credential pair per environment.
				$merchant_id_key = $sandbox_mode ? 'test_merchant_id' : 'merchant_id';
				$password_key    = $sandbox_mode ? 'test_password' : 'password';

				return '' !== $this->get_string_option( $payment_gateway, $merchant_id_key )
					&& '' !== $this->get_string_option( $payment_gateway, $password_key );
			}
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway has an account connected: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		return parent::is_account_connected( $payment_gateway );
	}

	/**
	 * Determine if the payment gateway is in test mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode, false otherwise.
	 */
	public function is_in_test_mode( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mastercard_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
	}

	/**
	 * Try to determine if the payment gateway is in test mode onboarding (aka sandbox).
	 *
	 * This is a best-effort attempt, as there is no standard way to determine this.
	 * Trust the true value, but don't consider a false value as definitive.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return bool True if the payment gateway is in test mode onboarding, false otherwise.
	 */
	public function is_in_test_mode_onboarding( WC_Payment_Gateway $payment_gateway ): bool {
		return $this->is_mastercard_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Check if the Mastercard Merchant Cloud payment gateway is in sandbox mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_mastercard_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			// The gateway stores sandbox mode as a checkbox option ('yes'|'no'), read through the
			// gateway's own get_option() so we follow whatever precedence the extension uses
			// internally, including its form field default.
			$sandbox = $payment_gateway->get_option( 'sandbox' );
			if ( ! is_scalar( $sandbox ) || '' === trim( (string) $sandbox ) ) {
				return null;
			}

			return \wc_string_to_bool( (string) $sandbox );
		} catch ( Throwable $e ) {
			// Do nothing but log so we can investigate.
			SafeGlobalFunctionProxy::wc_get_logger()->debug(
				'Failed to determine if gateway is in sandbox mode: ' . $e->getMessage(),
				array(
					'gateway'   => $payment_gateway->id,
					'source'    => 'settings-payments',
					'exception' => $e,
				)
			);
		}

		// Let the caller know that we couldn't determine the environment.
		return null;
	}

	/**
	 * Read a gateway option as a trimmed string.
	 *
	 * Anything that is not a scalar — an array from a malformed settings entry, for example —
	 * is treated as absent rather than cast, since casting an array would both emit a warning
	 * and yield a non-empty string, which would read as a configured credential.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 * @param string             $key             The option key to read.
	 *
	 * @return string The trimmed option value, or an empty string if it is absent or unusable.
	 */
	private function get_string_option( WC_Payment_Gateway $payment_gateway, string $key ): string {
		$value = $payment_gateway->get_option( $key, '' );

		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
