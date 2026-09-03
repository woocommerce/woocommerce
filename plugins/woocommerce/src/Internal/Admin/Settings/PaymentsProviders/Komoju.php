<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\Settings\PaymentsProviders;

use Automattic\WooCommerce\Internal\Admin\Settings\Payments;
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
		// The legacy combined gateway has no settings section of its own; account connection
		// and payment method selection happen on KOMOJU's dedicated settings tab instead.
		// Per-method gateways (`komoju_*`) already have a real settings section, so defer to
		// the generic gateway settings URL logic for those.
		if ( 'komoju' !== $payment_gateway->id ) {
			return parent::get_settings_url( $payment_gateway );
		}

		return add_query_arg(
			array(
				'from' => Payments::FROM_PAYMENTS_SETTINGS,
			),
			admin_url( 'admin.php?page=wc-settings&tab=komoju_settings' )
		);
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
		return $this->is_komoju_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode( $payment_gateway );
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
		// A `sk_test_` key is a sandbox credential, so the account itself is a test account,
		// not just a live account processing test payments. KOMOJU exposes no separate
		// onboarding environment signal, so the same check answers both questions.
		return $this->is_komoju_in_sandbox_mode( $payment_gateway ) ?? parent::is_in_test_mode_onboarding( $payment_gateway );
	}

	/**
	 * Check if the KOMOJU payment gateway is in sandbox mode.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return ?bool True if the payment gateway is in sandbox mode, false otherwise.
	 *               Null if the environment could not be determined.
	 */
	private function is_komoju_in_sandbox_mode( WC_Payment_Gateway $payment_gateway ): ?bool {
		try {
			// Check for a saved key first. The extension's helper below reports a store with no
			// key at all as live, but we want that case to stay undetermined so the caller decides.
			$secret_key = $this->get_secret_key( $payment_gateway );
			if ( empty( $secret_key ) ) {
				return null;
			}

			// Prefer the extension's own helper so we keep tracking how KOMOJU determines its
			// environment, should that ever stop being a secret key check.
			if ( class_exists( 'WC_Gateway_Komoju' ) && is_callable( 'WC_Gateway_Komoju::komoju_is_test_mode' ) ) {
				return wc_string_to_bool( \WC_Gateway_Komoju::komoju_is_test_mode() );
			}

			// The helper only exists since extension version 3.2.9, so reproduce it for older
			// versions: KOMOJU has no dedicated test-mode setting and infers the environment from
			// whether the stored secret key has the `sk_test_` (vs. `sk_live_`) prefix.
			return str_starts_with( $secret_key, 'sk_test_' );
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
			// connected once a secret key is saved.
			return ! empty( $this->get_secret_key( $payment_gateway ) );
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

	/**
	 * Get the KOMOJU secret key.
	 *
	 * @param WC_Payment_Gateway $payment_gateway The payment gateway object.
	 *
	 * @return string The secret key, or an empty string if none is saved.
	 */
	private function get_secret_key( WC_Payment_Gateway $payment_gateway ): string {
		// Prefer the key the gateway resolved for itself, so we keep tracking where KOMOJU
		// sources it from. Its `get_option_compat()` reads the current global option and falls
		// back to the legacy per-gateway settings array, which is what we reproduce below.
		// Guard on non-empty rather than isset(): versions older than 2.5.0 populate the property
		// through `WC_Settings_API::get_option()`, which yields an empty string rather than null
		// when nothing is stored, and that must still fall through to the reads below.
		if ( ! empty( $payment_gateway->secretKey ) && is_string( $payment_gateway->secretKey ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			return $payment_gateway->secretKey; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		// The property only exists since extension version 2.5.0, so reproduce its resolution
		// for older versions. Note that we cannot use the gateway's own `get_option()` here:
		// it reads `woocommerce_{$id}_settings`, which for the per-method `komoju_*` gateways
		// is not where the shared key lives.
		$secret_key = get_option( 'komoju_woocommerce_secret_key' );
		if ( empty( $secret_key ) ) {
			$legacy_settings = get_option( 'woocommerce_komoju_settings' );
			$secret_key      = is_array( $legacy_settings ) ? ( $legacy_settings['secretKey'] ?? '' ) : '';
		}

		return is_string( $secret_key ) ? $secret_key : '';
	}
}
